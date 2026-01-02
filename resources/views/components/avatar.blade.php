@php
    $initials = user_initials($name);
@endphp

<span x-data="{ error: false }"
    class="inline-flex items-center justify-center overflow-hidden rounded-full bg-brand-500 text-white font-medium"
    style="width: {{ $size }}px; height: {{ $size }}px;">
    @if ($src)
        <img x-show="!error" x-on:error="error = true" src="{{ $src }}" alt="{{ $name }}"
            class="h-full w-full object-cover" />
    @endif
    <span x-show="error || {{ $src ? 'false' : 'true' }}">
        {{ $initials }}
    </span>
</span>
