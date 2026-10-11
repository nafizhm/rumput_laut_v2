@extends('layouts.app')
@section('title',$entry->number)
@section('breadcrumb')<x-breadcrumb :pageTitle="$entry->number" :links="[['name'=>'Keuangan','url'=>'#'],['name'=>$entry->kind==='opening'?'Saldo Awal':'Jurnal Umum','url'=>route($entry->kind==='opening'?'saldo-awal.index':'jurnal.index')]]" />@endsection
@section('content')
    @include('pages.finance.journal._detail')
@endsection
