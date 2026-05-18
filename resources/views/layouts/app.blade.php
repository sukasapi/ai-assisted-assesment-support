<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @auth
        <script>
            (function () {
                try {
                    if (window.localStorage.getItem('sidebar-collapsed') === '1') {
                        document.documentElement.dataset.sidebarCollapsed = 'true';
                        document.addEventListener('DOMContentLoaded', function () {
                            var icon = document.getElementById('sidebar-collapse-icon');
                            if (icon) {
                                icon.textContent = 'chevron_right';
                            }
                        }, { once: true });
                    }
                } catch (e) {}
            })();
        </script>
    @endauth
</head>
<body class="min-h-screen bg-surface text-on-surface antialiased">
@guest
    <div class="flex min-h-screen flex-col bg-surface">
        <header class="border-b border-outline-variant/40 bg-surface-container-lowest">
            <div class="mx-auto flex max-w-5xl items-center gap-2 px-4 py-3">
                <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">analytics</span>
                <span class="font-display font-semibold text-on-surface">{{ config('app.name') }}</span>
            </div>
        </header>
        <main class="flex flex-1 flex-col items-center px-4 py-10">
            @yield('content')
        </main>
    </div>
@endguest

@auth
    <div class="app-shell">
        @include('layouts.partials.sidebar')
        <div id="app-main" class="app-main flex min-h-screen flex-col">
            @include('layouts.partials.topbar')
            <main class="flex-1 overflow-y-auto px-4 py-8 lg:px-gutter">
                <div class="mx-auto max-w-container-max">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
@endauth

@php
    $alertPayload = [
        'success' => session('status') ? ['text' => session('status')] : null,
        'error' => session('error') ? ['text' => session('error')] : null,
        'validation' => isset($errors) && $errors->any() ? $errors->toArray() : null,
    ];
@endphp
<script id="app-alerts" type="application/json">@json($alertPayload)</script>
</body>
</html>
