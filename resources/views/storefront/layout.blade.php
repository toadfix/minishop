<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Shop'))</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/storefront.css'])
    @else
        {{-- Fallback so the storefront is styled before assets are built. --}}
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    @livewireStyles
    @include('minishop::analytics.head')
</head>
<body class="flex min-h-full flex-col bg-gray-50 text-gray-900 antialiased">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-4 py-4">
            <a href="{{ url('/') }}" class="text-xl font-bold tracking-tight text-brand-600">
                {{ config('app.name', 'Shop') }}
            </a>

            <nav class="flex items-center gap-6 text-sm font-medium text-gray-600">
                <a href="{{ route('storefront.products.index') }}" class="hover:text-brand-600">Products</a>
                @auth
                    <a href="{{ route('account.dashboard') }}" class="hover:text-brand-600">Account</a>
                @else
                    <a href="{{ route('login') }}" class="hover:text-brand-600">Sign in</a>
                @endauth
                <livewire:minishop.cart-badge />
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-gray-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-6 text-sm text-gray-500">
            &copy; {{ date('Y') }} {{ config('app.name', 'Shop') }}. All rights reserved.
        </div>
    </footer>

    @livewireScripts
</body>
</html>
