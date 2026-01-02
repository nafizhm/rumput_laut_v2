@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
    <x-breadcrumb pageTitle="Dashboard" :links="[['name' => 'Dashboard', 'url' => route('dashboard')]]" />
@endsection

@section('content')

@endsection
