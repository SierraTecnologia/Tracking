@extends('layouts.app')

@section('pageTitle') Dashboard @stop

@section('content')

    <div class="col-md-12">
        <div class="row">
            <canvas id="dashboardChart" class="raw100"></canvas>
        </div>

        <div class="row raw-margin-top-24">
            <div class="col-md-4">
                <p class="lead">Browsers</p>
                <table class="table table-striped">
                    <thead>
                        <th>Browser</th>
                        <th>Sessions</th>
                    </thead>
                    @foreach (Analytics::fetchTopBrowsers($period, 10) as $word)
                        <tr>
                            <td>{{ $word['browser'] }}</td>
                            <td>{{ $word['sessions'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
            <div class="col-md-4">
                <p class="lead">Most Visited Pages</p>
                <table class="table table-striped">
                    <thead>
                        <th>URL</th>
                        <th>Views</th>
                    </thead>
                    @foreach (Analytics::fetchMostVisitedPages($period, 10) as $browser)
                        <tr>
                            <td>{{ str_limit($browser['url'], 30) }}</td>
                            <td>{{ $browser['pageViews'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
            <div class="col-md-4">
                <p class="lead">Top Referers</p>
                <table class="table table-striped">
                    <thead>
                        <th>URL</th>
                        <th>Views</th>
                    </thead>
                    @foreach (Analytics::fetchTopReferrers($period, 10) as $referers)
                        <tr>
                            <td>{{ str_limit($referers['url'], 30) }}</td>
                            <td>{{ $referers['pageViews'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>

    </div>

@stop

@section('javascript')
    @parent
    <script type="text/javascript">
        var _chartData = {
            _labels : {!! json_encode($visitStats['date']) !!},
            _visits : {!! json_encode($visitStats['pageViews']) !!}
        };
        var options = {};
    </script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.bundle.js"></script>
    <script type="text/javascript">
        /*
        |--------------------------------------------------------------------------
        | Charts
        |--------------------------------------------------------------------------
        */

        $(function () {

        var _chartDataConfig = {
            type: 'line',
            data: {
                labels: _chartData._labels,
                datasets: [{
                    label: "Visits",
                    backgroundColor: [
                        "#36A2EB"
                    ],
                    hoverBackgroundColor: [
                        "#36A2EB"
                    ],
                    pointStrokeColor: "#fff",
                    pointHighlightFill: "#fff",
                    pointHighlightStroke: "rgba(220,220,220,1)",
                    data: _chartData._visits
                }]
            }
        }

        var ctx = $("#dashboardChart").get(0).getContext("2d");
        window.dashboard = new Chart(ctx, _chartDataConfig);

        });

    </script>
@stop