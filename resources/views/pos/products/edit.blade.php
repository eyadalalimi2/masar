@extends('pos.layout.app')

@section('title', 'تعديل المنتج')

@section('content')
<h1 class="h4 fw-bold mb-4">تعديل المنتج</h1>

@include('shared.products.form', [
'formAction' => route('pos.products.update', $product),
'method' => 'PUT',
'product' => $product,
'supplierId' => $supplierId,
'categories' => $categories,
'productionYears' => $productionYears,
'units' => $units,
'variantTypes' => $variantTypes,
'cancelRoute' => route('pos.products.index'),
'submitLabel' => 'تحديث',
'formKey' => 'pos-edit',
])
@endsection