@extends('layouts.app')

@section('title', 'Payment Methods')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-semibold mb-4">Payment Methods</h1>

    <div class="p-4 bg-white shadow rounded">
        <p class="text-gray-600">This is a placeholder for managing saved payment methods. Selcom payments do not require storing card details here.</p>
        <form action="{{ route('billing.payment-methods.add') }}" method="POST" class="mt-4">
            @csrf
            <button class="px-3 py-2 bg-indigo-600 text-white rounded">Add Method (demo)</button>
        </form>
    </div>
</div>
@endsection