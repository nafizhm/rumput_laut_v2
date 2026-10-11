<header class="doc-header">
    <div class="brand">
        <img class="logo" src="{{ asset('images/branding/albagro.png') }}" alt="Logo {{ $profile->company_name }}">
        <div class="company">
            <div class="company-name">{{ mb_strtoupper($profile->company_name) }}</div>
            @if($profile->tagline)<p>{{ $profile->tagline }}</p>@endif
            @if($profile->address)<p>{{ $profile->address }}</p>@endif
            @if($profile->phone)<p>Telp {{ $profile->phone }}</p>@endif
            @if($showTax && $profile->tax_number)<p>NPWP {{ $profile->tax_number }}</p>@endif
        </div>
    </div>
    <div class="doc-heading">
        <h1>{{ $documentTitle }}</h1>
        <div class="doc-ref"><span class="label">No.</span><span class="value">{{ $documentNumber }}</span></div>
        <div class="doc-ref"><span class="label">Tanggal</span><span class="value">{{ $documentDate->format('d/m/Y') }}</span></div>
        @isset($dueDate)<div class="doc-ref"><span class="label">Jatuh Tempo</span><span class="value">{{ $dueDate->format('d/m/Y') }}</span></div>@endisset
    </div>
</header>
