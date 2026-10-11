@extends('layouts.app')
@section('title','Edit Proses Harian')
@section('breadcrumb')<x-breadcrumb pageTitle="Edit Proses Harian" :links="[['name'=>'Proses Harian','url'=>route('produksi.index')]]" />@endsection
@section('content')
<div class="space-y-4 rounded-xl bg-white p-6 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
    <h2 class="text-xl font-semibold">Edit {{ $run->number }}</h2>
    <x-info-help><p class="text-sm text-gray-500">PIC, jenis pekerjaan, dan catatan dapat diperbaiki selama proses aktif. Tanggal, area, batch sumber, dan jumlah karung sudah dikunci saat alokasi stok.</p></x-info-help>
    @if($errors->any())<ul class="rounded-lg bg-red-50 p-4 text-red-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    <form method="POST" action="{{ route('produksi.update',$run) }}" class="space-y-4">
        @csrf @method('PUT')
        <label class="block text-sm">PIC<input name="pic" value="{{ old('pic',$run->pic) }}" required maxlength="255" class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
        <label class="block text-sm">Jenis pekerjaan<input name="work_type" value="{{ old('work_type',$run->work_type) }}" required maxlength="255" class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
        <label class="block text-sm">Catatan<textarea name="notes" maxlength="2000" class="mt-2 w-full rounded-lg border bg-transparent p-3">{{ old('notes',$run->notes) }}</textarea></label>
        <div class="flex items-center gap-4"><button class="rounded-lg bg-brand-600 px-5 py-3 text-white">Simpan Perubahan</button><a href="{{ route('produksi.index') }}" class="text-sm text-brand-600">Kembali ke Riwayat</a></div>
    </form>
</div>
@endsection
