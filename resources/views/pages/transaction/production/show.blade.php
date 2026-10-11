@extends('layouts.app')
@section('title',$run->number)
@section('breadcrumb')<x-breadcrumb :pageTitle="$run->number" :links="[['name'=>'Gudang','url'=>route('manajemen-gudang.index')]]" />@endsection
@section('content')
@include('pages.transaction.production._detail')
@endsection
