@extends('minishop::storefront.layout')

@section('title', 'Products')

@section('content')
    <h1 class="mb-6 text-2xl font-bold tracking-tight text-gray-900">Products</h1>
    <livewire:minishop.product-list />
@endsection
