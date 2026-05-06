@extends('workshop.layout.app')

@section('title', 'تعديل المنتج')

@section('content')
<h1 class="h4 fw-bold mb-4">تعديل المنتج</h1>

@include('shared.products.form', [
'formAction' => route('workshop.products.update', $product),
'method' => 'PUT',
'product' => $product,
'supplierId' => $supplierId,
'categories' => $categories,
'productionYears' => $productionYears,
'units' => $units,
'variantTypes' => $variantTypes,
'cancelRoute' => route('workshop.products.index'),
'submitLabel' => 'تحديث',
'formKey' => 'workshop-edit',
])
@endsection