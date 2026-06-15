@extends('minishop::storefront.layout')

@section('title', 'Verify your email')

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Verify your email</h1>

        <div class="mt-6 rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-600">
            <p>
                Thanks for signing up! Before getting started, please confirm your
                email address by clicking the link we just sent you. If you didn't
                receive it, we'll gladly send another.
            </p>

            @if (session('status') === 'verification-link-sent')
                <p class="mt-4 rounded-md bg-green-50 px-4 py-3 text-green-800">
                    A fresh verification link has been sent to your email address.
                </p>
            @endif

            <div class="mt-6 flex items-center gap-4">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit"
                            class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                        Resend verification email
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
