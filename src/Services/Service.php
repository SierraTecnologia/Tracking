<?php

namespace Tracking\Services;

class Service
{
    public static function getSingleton()
    {
        //@todo Ativar cache de acordo com business
        return new static();
    }
}