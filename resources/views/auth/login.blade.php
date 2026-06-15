@extends('minishop::storefront.layout')

@section('title', 'Sign in')

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Sign in</h1>

        @if (session('status'))
            <p class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5 rounded-lg border border-gray-200 bg-white p-6">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-600 hover:underline">Forgot your password?</a>
                </div>
                <input type="password" name="password" required
                       class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                Remember me
            </label>

            <button type="submit" class="w-full rounded-md bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                Sign in
            </button>

            <p class="text-center text-sm text-gray-500">
                Don't have an account? <a href="{{ route('register') }}" class="font-medium text-brand-600 hover:underline">Create one</a>
            </p>
        </form>
    </div>
@endsection
