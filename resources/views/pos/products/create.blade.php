@extends('pos.layout.app')

@section('title', 'إضافة منتج')

@section('content')
<h1 class="h4 fw-bold mb-4">إضافة منتج</h1>

@include('shared.products.form', [
'formAction' => route('pos.products.store'),
'method' => 'POST',
'supplierId' => $supplierId,
'categories' => $categories,
'productionYears' => $productionYears,
'units' => $units,
'variantTypes' => $variantTypes,
'cancelRoute' => route('pos.products.index'),
'submitLabel' => 'حفظ',
'formKey' => 'pos-create',
])
@endsection