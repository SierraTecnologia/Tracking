@extends('layouts.app')

<!-- @section('css')
    <link rel="stylesheet" href="{{ asset('dist/css/vendor.css')}} ">
@stop

@section('js')
    @parent
    <script type="text/javascript">
        var _token = '{!! csrf_token() !!}';
        var _url = '{!! url("/") !!}';
    </script>
    <script src="{{ asset('dist/js/vendor.js')}}"></script>
    @stack('javascript')
    @yield('javascript')
@stop -->

@section('content')

    <div class="page">
        <div class="page-main">
            @include('larametrics::common.partials.header')
            <div class="my-3 my-md-5">
                <div class="container">
                    <div class="page-header">
                        <h1 class="page-title">{{ $pageTitle ?: 'Page Title Goes Here' }}</h1>
                        @if(isset($pageSubtitle))
                            <div class="text-muted mb-0 ml-4">{{ $pageSubtitle }}</div>
                        @endif
                    </div>
                    @yield('content')
                </div>
            </div>
        </div>
        @include('larametrics::common.partials.footer')
    </div>
@endsection