@extends('layouts.app')

@section('css')
    <!-- Dashboard Core -->
    <link href="/vendor/larametrics/css/tabler.css" rel="stylesheet" />
@stop

@section('js')
    @parent
    
<script src="https://cdn.jsdelivr.net/npm/d3@5.5.0/dist/d3.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/c3@0.6.6/c3.min.js"></script>

    <!-- Vue.js -->
    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.16/dist/vue.js"></script>

    @stack('javascript')
    @yield('javascript')
@stop

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