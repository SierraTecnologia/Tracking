/**
 * SierraTecnologia / Rica Soluções — Universal Lead & Behavioral Tracker
 * Tracking v2.3.0
 *
 * Rastreia a jornada do visitante antes e depois de virar lead:
 *  - antes: os passos ficam no navegador e vão junto com o formulário (`steps`);
 *  - depois: cada evento vai em lote para /leads/{uuid}/eventos.
 *
 * Uso: <script defer src="https://central.exemplo.com.br/js/tracker.js"
 *              data-site="meusite.com.br"
 *              data-api="https://central.exemplo.com.br/api/v1"></script>
 *
 * Atributos opcionais: data-events-path, data-lead-paths, data-batch-ms,
 * data-respect-dnt, data-debug. API pública em window.RicaTracker.
 */
(function () {
  'use strict';

  if (window.__RICA_TRACKER_INITIALIZED__) return;
  window.__RICA_TRACKER_INITIALIZED__ = true;

  var VERSION = '2.3.0';

  var currentScript = document.currentScript || (function () {
    var scripts = document.getElementsByTagName('script');
    return scripts[scripts.length - 1];
  })();

  function attr(name, fallback) {
    var value = currentScript && currentScript.getAttribute(name);
    return value === null || value === undefined || value === '' ? fallback : value;
  }

  function list(value) {
    return String(value).split(',').map(function (s) { return s.trim(); }).filter(Boolean);
  }

  var config = {
    version: VERSION,
    site: attr('data-site', window.location.hostname),
    api: attr('data-api', 'https://central.ricasolucoes.com.br/api/v1'),
    eventsPath: attr('data-events-path', '/leads/{uuid}/eventos'),
    leadPaths: list(attr('data-lead-paths', '/leads,/funil/contato')),
    batchMs: parseInt(attr('data-batch-ms', '800'), 10) || 800,
    respectDnt: attr('data-respect-dnt', 'false') === 'true',
    debug: attr('data-debug', 'false') === 'true',
    storageKey: '_rica_steps',
    uuidKey: '_rica_lead_uuid',
    sessionKey: '_rica_sid',
    visitorKey: '_rica_vid',
    maxBufferedEvents: 50,
    maxBatch: 50
  };

  var startTime = Date.now();
  var queue = [];
  var flushTimer = null;
  var exitSent = false;

  function log() {
    if (config.debug && window.console) {
      console.log.apply(console, ['[tracker]'].concat(Array.prototype.slice.call(arguments)));
    }
  }

  /* ------------------------------------------------------------ storage */
  function storageGet(storage, key) {
    try { return window[storage].getItem(key); } catch (e) { return null; }
  }

  function storageSet(storage, key, value) {
    try {
      if (value === null) window[storage].removeItem(key);
      else window[storage].setItem(key, value);
    } catch (e) {}
  }

  function randomId(prefix) {
    return prefix + '_' + Math.random().toString(36).substring(2, 10) + '_' + Date.now().toString(36);
  }

  function getSessionId() {
    var sid = storageGet('sessionStorage', config.sessionKey);
    if (!sid) {
      sid = randomId('s');
      storageSet('sessionStorage', config.sessionKey, sid);
    }
    return sid;
  }

  function getVisitorId() {
    var vid = storageGet('localStorage', config.visitorKey) || storageGet('sessionStorage', config.visitorKey);
    if (!vid) {
      vid = randomId('v');
      storageSet('localStorage', config.visitorKey, vid);
      storageSet('sessionStorage', config.visitorKey, vid);
    }
    return vid;
  }

  function getLeadUuid() {
    return storageGet('sessionStorage', config.uuidKey) || window.__RICA_LEAD_UUID__ || null;
  }

  function setLeadUuid(uuid) {
    if (!uuid) return;
    window.__RICA_LEAD_UUID__ = uuid;
    storageSet('sessionStorage', config.uuidKey, uuid);
  }

  function getBufferedSteps() {
    try {
      var raw = storageGet('sessionStorage', config.storageKey);
      var steps = raw ? JSON.parse(raw) : [];
      return Array.isArray(steps) ? steps : [];
    } catch (e) {
      return [];
    }
  }

  function saveBufferedSteps(steps) {
    storageSet('sessionStorage', config.storageKey, JSON.stringify(steps.slice(-config.maxBufferedEvents)));
  }

  function clearBufferedSteps() {
    storageSet('sessionStorage', config.storageKey, null);
  }

  /* ------------------------------------------------------------ transporte */
  function eventsUrl(leadUuid) {
    var path = config.eventsPath.replace('{uuid}', encodeURIComponent(leadUuid));
    return config.api.replace(/\/+$/, '') + (path.charAt(0) === '/' ? path : '/' + path);
  }

  function send(url, payload) {
    // text/plain não dispara preflight: o beacon sai mesmo com a aba fechando.
    if (navigator.sendBeacon) {
      try {
        var blob = new Blob([payload], { type: 'text/plain;charset=UTF-8' });
        if (navigator.sendBeacon(url, blob)) return;
      } catch (e) {}
    }

    try {
      fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'text/plain;charset=UTF-8', 'Accept': 'application/json' },
        body: payload,
        credentials: 'omit',
        mode: 'cors',
        keepalive: true
      }).catch(function () {});
    } catch (e) {}
  }

  function flush() {
    if (flushTimer) {
      clearTimeout(flushTimer);
      flushTimer = null;
    }

    var uuid = getLeadUuid();
    if (!uuid || queue.length === 0) return;

    while (queue.length > 0) {
      var lote = queue.splice(0, config.maxBatch);
      log('enviando', lote.length, 'evento(s)');
      send(eventsUrl(uuid), JSON.stringify({ events: lote }));
    }
  }

  function scheduleFlush() {
    if (flushTimer) return;
    flushTimer = setTimeout(flush, config.batchMs);
  }

  /* ------------------------------------------------------------ núcleo */
  function track(type, description, meta) {
    if (!type || !description) return;

    meta = meta || {};
    var elapsed = Date.now() - startTime;
    meta.elapsed_ms = elapsed;
    meta.session_id = getSessionId();
    meta.visitor_id = getVisitorId();
    meta.site = config.site;
    if (!meta.url) meta.url = window.location.href.slice(0, 512);

    var event = {
      type: String(type).slice(0, 50),
      description: String(description).slice(0, 255),
      meta: meta,
      elapsed_ms: elapsed,
      created_at: new Date().toISOString()
    };

    log(event.type, event.description);

    if (getLeadUuid()) {
      queue.push(event);
      scheduleFlush();
    } else {
      var steps = getBufferedSteps();
      steps.push(event);
      saveBufferedSteps(steps);
    }
  }

  /**
   * Liga o navegador a um lead. Passos acumulados antes disso são enviados
   * como eventos, a menos que já tenham ido junto com o formulário.
   */
  function identify(uuid, sendBuffered) {
    if (!uuid) return;
    setLeadUuid(uuid);

    if (sendBuffered !== false) {
      var steps = getBufferedSteps();
      if (steps.length > 0) {
        queue = queue.concat(steps);
      }
    }

    clearBufferedSteps();
    flush();
  }

  /* ------------------------------------------------------------ coleta */
  function getUtms() {
    var utm = {};
    try {
      var params = new URLSearchParams(window.location.search);
      ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach(function (k) {
        var v = params.get(k);
        if (v) utm[k] = v.slice(0, 190);
      });
    } catch (e) {}
    return utm;
  }

  function getDeviceType() {
    var width = window.innerWidth || screen.width;
    if (width < 768) return 'mobile';
    if (width < 1024) return 'tablet';
    return 'desktop';
  }

  function trackPageView() {
    var utms = getUtms();
    var meta = {
      url: window.location.href.slice(0, 512),
      referrer: (document.referrer || '').slice(0, 512),
      device: getDeviceType(),
      language: (navigator.language || '').slice(0, 10),
      resolution: window.screen.width + 'x' + window.screen.height,
      viewport: window.innerWidth + 'x' + window.innerHeight,
      title: (document.title || '').slice(0, 120)
    };
    Object.keys(utms).forEach(function (k) { meta[k] = utms[k]; });

    var desc = 'Acessou ' + window.location.pathname;
    if (meta.utm_source) {
      desc += ' via ' + meta.utm_source;
      if (meta.utm_campaign) desc += ' (' + meta.utm_campaign + ')';
    } else if (meta.referrer) {
      try {
        var refHost = new URL(meta.referrer).hostname;
        if (refHost !== window.location.hostname) desc += ' vindo de ' + refHost;
      } catch (e) {
        desc += ' vindo de referrer externo';
      }
    }

    track('navegacao', desc, meta);
  }

  function setupScrollTracking() {
    var milestones = [25, 50, 75, 100];
    var reached = {};

    var checkScroll = function () {
      var scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
      var docHeight = Math.max(
        document.body.scrollHeight, document.documentElement.scrollHeight,
        document.body.offsetHeight, document.documentElement.offsetHeight,
        document.body.clientHeight, document.documentElement.clientHeight
      );
      var winHeight = window.innerHeight || document.documentElement.clientHeight;
      if (docHeight <= winHeight) return;

      var scrollPercent = Math.min(100, Math.round(((scrollTop + winHeight) / docHeight) * 100));

      milestones.forEach(function (m) {
        if (scrollPercent >= m && !reached[m]) {
          reached[m] = true;
          track('rolagem', 'Rolou ' + m + '% da página', { profundidade: m });
        }
      });
    };

    var ticking = false;
    window.addEventListener('scroll', function () {
      if (!ticking) {
        window.requestAnimationFrame(function () {
          checkScroll();
          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });
  }

  function setupSectionTracking() {
    if (!('IntersectionObserver' in window)) return;

    var seenSections = {};
    var timers = {};

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        var target = entry.target;
        var sectionId = target.id || target.getAttribute('data-track-section') || target.tagName.toLowerCase();
        var key = target.tagName.toLowerCase() + '#' + sectionId;

        if (entry.isIntersecting && entry.intersectionRatio >= 0.3) {
          if (!seenSections[key] && !timers[key]) {
            timers[key] = setTimeout(function () {
              if (!seenSections[key]) {
                seenSections[key] = true;
                var heading = target.querySelector('h1, h2, h3');
                var headingText = heading && heading.textContent ? heading.textContent.trim().slice(0, 50) : '';
                var label = target.getAttribute('data-section-label') || headingText || sectionId;
                track('secao', 'Visualizou seção: ' + label, { secao_id: sectionId, rotulo: label });
              }
              delete timers[key];
            }, 1200);
          }
        } else if (timers[key]) {
          clearTimeout(timers[key]);
          delete timers[key];
        }
      });
    }, { threshold: [0.3] });

    var sections = document.querySelectorAll('section[id], [data-track-section], header, footer, #planos, #servicos, #sobre, #depoimentos, #faq, #contato');
    Array.prototype.forEach.call(sections, function (sec) { observer.observe(sec); });
  }

  function isExternal(href) {
    try {
      var url = new URL(href, window.location.href);
      return (url.protocol === 'http:' || url.protocol === 'https:') && url.hostname !== window.location.hostname;
    } catch (e) {
      return false;
    }
  }

  function setupInteractionTracking() {
    document.addEventListener('click', function (ev) {
      var target = ev.target;
      if (!target || !target.closest) return;

      // Elemento marcado à mão: <a data-track="Baixar proposta" data-track-type="interacao">
      var custom = target.closest('[data-track]');
      if (custom) {
        var customLabel = custom.getAttribute('data-track') || (custom.textContent || '').trim().slice(0, 60);
        track(custom.getAttribute('data-track-type') || 'interacao', customLabel, {
          tipo: 'marcado',
          rotulo: customLabel
        });
        return;
      }

      var waLink = target.closest('a[href*="wa.me"], a[href*="whatsapp.com"], .lead-talk-now, .lead-wa-btn');
      if (waLink) {
        var href = waLink.getAttribute('href') || '';
        var text = (waLink.textContent || '').trim().slice(0, 60);
        track('clique_wa', 'Clicou no botão do WhatsApp: ' + (text || 'Falar agora'), {
          link: href.slice(0, 200),
          texto: text
        });
        return;
      }

      var contato = target.closest('a[href^="tel:"], a[href^="mailto:"]');
      if (contato) {
        var contatoHref = contato.getAttribute('href') || '';
        track('clique_contato', 'Clicou em ' + (contatoHref.indexOf('tel:') === 0 ? 'telefone' : 'e-mail') + ': ' + contatoHref.slice(0, 80), {
          link: contatoHref.slice(0, 200)
        });
        return;
      }

      var chip = target.closest('.lead-chip, [data-need]');
      if (chip) {
        var need = chip.getAttribute('data-need') || '';
        var needText = (chip.textContent || '').trim().slice(0, 60);
        track('interacao', 'Selecionou interesse: ' + (needText || need), { tipo: 'chip', need: need, rotulo: needText });
        return;
      }

      var faq = target.closest('.faq-question, summary, [data-faq-toggle]');
      if (faq) {
        var faqTitle = (faq.textContent || '').trim().slice(0, 80);
        track('interacao', 'Abriu pergunta do FAQ: ' + faqTitle, { tipo: 'faq', pergunta: faqTitle });
        return;
      }

      var link = target.closest('a[href]');
      if (link && isExternal(link.getAttribute('href'))) {
        var externo = link.getAttribute('href');
        track('clique_externo', 'Abriu link externo: ' + externo.slice(0, 120), {
          link: externo.slice(0, 200),
          texto: (link.textContent || '').trim().slice(0, 60)
        });
      }
    }, true);

    var formTouched = false;
    document.addEventListener('focusin', function (ev) {
      if (formTouched) return;
      var input = ev.target;
      if (input && (input.tagName === 'INPUT' || input.tagName === 'TEXTAREA' || input.tagName === 'SELECT')) {
        var form = input.closest('form');
        if (form) {
          formTouched = true;
          var fieldName = input.name || input.id || input.getAttribute('data-lead-field') || 'campo';
          track('formulario', 'Iniciou preenchimento do formulário no campo ' + fieldName, {
            campo: fieldName,
            formulario: form.id || form.getAttribute('name') || ''
          });
        }
      }
    }, true);

    document.addEventListener('submit', function (ev) {
      var form = ev.target;
      if (!form || form.tagName !== 'FORM') return;
      var nome = form.id || form.getAttribute('name') || form.getAttribute('data-track-form') || 'formulário';
      track('formulario', 'Enviou o formulário ' + nome, { formulario: nome });
    }, true);
  }

  function isLeadRequest(urlStr, options) {
    if (!options || !options.method || options.method.toUpperCase() !== 'POST') return false;
    if (urlStr.indexOf('/eventos') !== -1) return false;
    return config.leadPaths.some(function (p) { return urlStr.indexOf(p) !== -1; });
  }

  /**
   * Intercepta o POST que cria o lead para: anexar os passos acumulados
   * (`steps`) e guardar o uuid devolvido (`uuid` ou `lead`).
   */
  function setupFormHook() {
    var originalFetch = window.fetch;
    if (typeof originalFetch !== 'function') return;

    window.fetch = function (url, options) {
      var urlStr = typeof url === 'string' ? url : (url && url.url ? url.url : '');
      if (!isLeadRequest(urlStr, options)) {
        return originalFetch.apply(this, arguments);
      }

      var anexou = false;
      try {
        if (options.body && typeof options.body === 'string') {
          var bodyObj = JSON.parse(options.body);
          if (bodyObj && typeof bodyObj === 'object' && !Array.isArray(bodyObj) && !bodyObj.steps) {
            var steps = getBufferedSteps();
            if (steps.length > 0) {
              bodyObj.steps = steps;
              options.body = JSON.stringify(bodyObj);
              anexou = true;
            }
          }
        }
      } catch (e) {}

      return originalFetch.apply(this, arguments).then(function (res) {
        try {
          res.clone().json().then(function (json) {
            var uuid = json && json.ok ? (json.uuid || json.lead) : null;
            if (uuid) {
              if (anexou) clearBufferedSteps();
              identify(uuid, !anexou);
            }
          }).catch(function () {});
        } catch (e) {}
        return res;
      });
    };
  }

  function setupExitTracking() {
    var sair = function () {
      if (exitSent) return;
      exitSent = true;
      var segundos = Math.round((Date.now() - startTime) / 1000);
      track('saida', 'Saiu da página após ' + segundos + 's', { segundos: segundos });
      flush();
    };

    window.addEventListener('pagehide', sair);
    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'hidden') sair();
    });
  }

  /* ------------------------------------------------------------ init */
  function init() {
    if (config.respectDnt && (navigator.doNotTrack === '1' || window.doNotTrack === '1' || navigator.globalPrivacyControl)) {
      log('desligado por Do Not Track');
      return;
    }

    trackPageView();
    setupScrollTracking();

    var ready = function () {
      setupSectionTracking();
      setupInteractionTracking();
      setupFormHook();
      setupExitTracking();
    };

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', ready);
    } else {
      ready();
    }
  }

  var tracker = {
    version: VERSION,
    config: config,
    init: init,
    track: track,
    pageview: trackPageView,
    identify: identify,
    flush: flush,
    getSteps: getBufferedSteps,
    clearSteps: clearBufferedSteps,
    getLeadUuid: getLeadUuid,
    setLeadUuid: setLeadUuid,
    sessionId: getSessionId,
    visitorId: getVisitorId
  };

  window.RicaTracker = tracker;
  window.Tracking = tracker;

  init();
})();
