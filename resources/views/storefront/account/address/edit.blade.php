@extends('minishop::storefront.layout')

@section('title', 'My address')

@section('content')
    <h1 class="mb-6 text-2xl font-bold tracking-tight text-gray-900">My account</h1>
    @include('minishop::storefront.account._nav')

    <h2 class="mt-6 text-lg font-semibold text-gray-900">Billing address</h2>
    <div class="mt-4 max-w-2xl">
        <livewire:minishop.address-form />
    </div>
@endsection
