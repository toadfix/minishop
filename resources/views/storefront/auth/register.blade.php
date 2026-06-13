@extends('minishop::storefront.layout')

@section('title', 'Create an account')

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Create your account</h1>

        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5 rounded-lg border border-gray-200 bg-white p-6">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus
                       class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" required
                       class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Confirm password</label>
                <input type="password" name="password_confirmation" required
                       class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
            </div>

            <button type="submit" class="w-full rounded-md bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                Create account
            </button>

            <p class="text-center text-sm text-gray-500">
                Already have an account? <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:underline">Sign in</a>
            </p>
        </form>
    </div>
@endsection
