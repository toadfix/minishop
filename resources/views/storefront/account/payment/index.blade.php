@extends('minishop::storefront.layout')

@section('title', 'Payment methods')

@section('content')
    <h1 class="mb-6 text-2xl font-bold tracking-tight text-gray-900">My account</h1>
    @include('minishop::storefront.account._nav')

    <h2 class="mt-6 text-lg font-semibold text-gray-900">Payment methods</h2>
    <div class="mt-4 rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
        Saved payment methods will appear here. Payments are collected securely at checkout.
    </div>
@endsection
