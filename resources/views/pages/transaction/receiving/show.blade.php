@extends('layouts.app')
@section('title',$receipt->number)
@section('breadcrumb')<x-breadcrumb :pageTitle="$receipt->number" :links="[['name'=>'Penerimaan Barang','url'=>route('penerimaan-barang.index')]]" />@endsection
@section('content')
@include('pages.transaction.receiving._detail')
@endsection
