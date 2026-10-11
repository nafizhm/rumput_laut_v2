@extends('layouts.app')

@section('title', 'Profile Perusahaan')

@section('breadcrumb')
    <x-breadcrumb pageTitle="Profile Perusahaan" :links="[['name' => 'Setting', 'url' => '#']]" />
@endsection

@section('content')
    <form method="POST" action="{{ route('company-profile.update') }}" class="mx-auto max-w-5xl">
        @csrf
        @method('PUT')
        @if(session('success'))
            <p role="status" class="mb-5 rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-300">{{ session('success') }}</p>
        @endif
        @if($errors->any())
            <div role="alert" class="mb-5 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300">
                @foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach
            </div>
        @endif
        <x-form-section title="Identitas Perusahaan" description="Identitas ini digunakan pada kop surat jalan, invoice, dan dokumen perusahaan lainnya.">
            <div class="sm:col-span-2 flex items-center gap-5">
                <img src="{{ asset('images/branding/albagro.png') }}" alt="Logo Albagro" class="w-44 rounded-lg bg-white p-3">
                <x-info-help>Logo perusahaan pada dokumen.</x-info-help>
            </div>
            <x-input label="Nama Perusahaan" name="company_name" :value="old('company_name', $profile->company_name)" required maxlength="255" />
            <x-input label="No. Telepon" name="phone" :value="old('phone', $profile->phone)" maxlength="60" />
            <div class="sm:col-span-2">
                <label for="company-address" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Alamat Perusahaan</label>
                <textarea id="company-address" name="address" rows="3" maxlength="2000" class="w-full rounded-lg border border-gray-300 p-3 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">{{ old('address', $profile->address) }}</textarea>
            </div>
            <x-input label="Keterangan Usaha" name="tagline" :value="old('tagline', $profile->tagline)" maxlength="255" />
            <x-input label="NPWP (opsional)" name="tax_number" :value="old('tax_number', $profile->tax_number)" maxlength="60" />
        </x-form-section>
        <x-form-section title="Rekening & Penanggung Jawab" description="Data opsional untuk informasi pembayaran dan tanda tangan pada invoice.">
            <x-input label="Nama Bank" name="bank_name" :value="old('bank_name', $profile->bank_name)" maxlength="100" />
            <x-input label="Nomor Rekening" name="bank_account" :value="old('bank_account', $profile->bank_account)" maxlength="100" />
            <x-input label="Rekening Atas Nama" name="bank_account_name" :value="old('bank_account_name', $profile->bank_account_name)" maxlength="255" />
            <x-input label="Nama Penandatangan" name="signatory_name" :value="old('signatory_name', $profile->signatory_name)" maxlength="255" />
        </x-form-section>
        @foreach(['delivery_note' => 'Surat Jalan', 'invoice' => 'Invoice'] as $type => $label)
            @php($rule = $rules[$type])
            <x-form-section :title="'Aturan Nomor '.$label" description="Nomor otomatis dibuat saat dokumen disimpan. Perubahan aturan hanya berlaku untuk dokumen baru.">
                <x-input label="Format Nomor" :name="'rules['.$type.'][format]'" :value="old('rules.'.$type.'.format', $rule->format)" required maxlength="80" />
                <div>
                    <label for="reset-{{ $type }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Reset Urutan</label>
                    <select id="reset-{{ $type }}" name="rules[{{ $type }}][reset_period]" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        @foreach(['never' => 'Tidak direset', 'year' => 'Setiap tahun', 'month' => 'Setiap bulan'] as $value => $text)
                            <option value="{{ $value }}" @selected(old('rules.'.$type.'.reset_period', $rule->reset_period) === $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                <x-input label="Jumlah Digit Urutan" :name="'rules['.$type.'][padding]'" type="number" :value="old('rules.'.$type.'.padding', $rule->padding)" min="1" max="10" required />
                <x-input label="Nomor Awal / Batas Minimum Urutan" :name="'rules['.$type.'][start_number]'" type="number" :value="old('rules.'.$type.'.start_number', $rule->start_number)" min="1" max="999999999" required />
                <div class="sm:col-span-2 space-y-1 text-xs text-gray-500 dark:text-gray-400">
                    <x-info-help label="Panduan nomor dokumen">
                        <p>Gunakan {SEQ} untuk nomor urut, {MM} untuk bulan, dan {YYYY} untuk tahun dokumen. Contoh: {{ \App\Services\Setting\DocumentNumberService::format($rule, now(), $rule->start_number) }}.</p>
                        <p>Urutan mengikuti tanggal dokumen dan tidak mundur dari nomor yang sudah diterbitkan. Nomor awal berlaku pada periode baru atau untuk menaikkan urutan berikutnya. Nomor yang sudah dipakai akan dilewati.</p>
                    </x-info-help>
                </div>
            </x-form-section>
        @endforeach
        @can('company-profile.update')
            <div class="flex justify-end pb-6">
                <button type="submit" class="rounded-lg bg-brand-500 px-6 py-3 text-sm font-medium text-white hover:bg-brand-600">Simpan Profile</button>
            </div>
        @endcan
    </form>
@endsection
