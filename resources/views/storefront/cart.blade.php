@extends('minishop::storefront.layout')

@section('title', 'Your cart')

@section('content')
    <h1 class="mb-6 text-2xl font-bold tracking-tight text-gray-900">Your cart</h1>
    <livewire:minishop.cart-page />
@endsection
