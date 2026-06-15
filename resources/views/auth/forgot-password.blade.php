@extends('minishop::storefront.layout')

@section('title', 'Forgot password')

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Forgot your password?</h1>

        <p class="mt-2 text-sm text-gray-600">
            Enter your email and we'll send you a link to reset it.
        </p>

        @if (session('status'))
            <p class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5 rounded-lg border border-gray-200 bg-white p-6">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full rounded-md bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                Email password reset link
            </button>

            <p class="text-center text-sm text-gray-500">
                <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:underline">Back to sign in</a>
            </p>
        </form>
    </div>
@endsection
