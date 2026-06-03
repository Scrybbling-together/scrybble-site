@php use App\Enums\DeploymentEnvironment; @endphp
    <!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#BEC36B">
    <meta name="msapplication-TileColor" content="#BEC36B">

    <title>{{ $title ?? "Scrybble Sync - Think analog, organize digital"}}</title>

    @if( DeploymentEnvironment::current()->isCommercial() )
        <script src="https://analytics.ahrefs.com/analytics.js" data-key="y6foK17D1uxnfx43uJn9pw" async></script>
    @endif

    @yield('head')

    @vite(['resources/sass/app.scss', ])
    @stack('head')
</head>
<body class="min-vh-100 d-flex flex-column justify-content-between">

@include('components.layout.navigation')
<div class="flex-grow-1">
    @yield('content')
</div>
@include('components.layout.footer')

@vite(['resources/js/app.js'])
</body>
</html>
