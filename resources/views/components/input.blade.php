@props([
    'label' => null,
    'type' => 'text',
    'id' => null,
    'name' => null,
    'placeholder' => null,
    'prefix' => null,
    'suffix' => null,
    'isCurrency' => false,
    'required' => false,
    'error' => null,
    'alpineError' => null,
])

@php
    $id = $id ?? ($name ?? 'input-' . Str::random(8));
    $realType = $isCurrency ? 'text' : $type;
    $hasSsrError = $name && $errors->has($name);
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ $label }}
            @if ($required)
                <span class="text-error-500">*</span>
            @endif
        </label>
    @endif

    <div @class([
        'relative' =>
            $type === 'password' ||
            $prefix ||
            $suffix ||
            in_array($type, ['date', 'time']) ||
            $isCurrency ||
            $hasSsrError ||
            $alpineError,
    ]) @if ($type === 'password') x-data="{ showPassword: false }" @endif
        @if ($isCurrency) x-data="{ 
                formatValue(val) {
                    if (!val) return '';
                    let number = val.toString().replace(/[^0-9]/g, '');
                    if (!number) return '';
                    return new Intl.NumberFormat('id-ID').format(number);
                }
            }" @endif>

        @if ($prefix)
            <span
                class="absolute top-1/2 left-0 inline-flex h-11 -translate-y-1/2 items-center justify-center border-r border-gray-200 py-3 pr-3 pl-3.5 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                {{ $prefix }}
            </span>
        @endif

        <input
            {{ $attributes->merge([
                'type' => $realType,
                'id' => $id,
                'name' => $name,
                'placeholder' => $placeholder,
                'class' =>
                    'dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-2 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 ' .
                    ($hasSsrError
                        ? 'border-error-300 focus:border-error-300 focus:ring-error-500/10 dark:border-error-700 dark:focus:border-error-800 dark:focus:ring-error-500/5'
                        : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:focus:border-brand-800 dark:focus:ring-brand-500/5') .
                    ($prefix ? ' pl-16' : '') .
                    ($suffix || $type === 'password' || in_array($type, ['date', 'time']) || $hasSsrError ? ' pr-11' : ''),
            ]) }}
            @if ($alpineError) :class="{
                'border-error-300! focus:border-error-300! dark:border-error-700! dark:focus:border-error-800! pr-11!': {!! $alpineError !!}
            }" @endif
            @if ($type === 'password') :type="showPassword ? 'text' : 'password'" @endif
            @if ($type === 'time') onclick="this.showPicker()" @endif
            @if ($isCurrency) x-on:input="$el.value = formatValue($el.value)" @endif>

        @if ($type === 'password')
            <span @click="showPassword = !showPassword"
                class="absolute top-1/2 right-4 z-30 -translate-y-1/2 cursor-pointer">
                <svg x-show="!showPassword" class="fill-gray-500 dark:fill-gray-400" width="20" height="20"
                    viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.61690 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z" />
                </svg>
                <svg x-show="showPassword" class="fill-gray-500 dark:fill-gray-400" width="20" height="20"
                    viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z" />
                </svg>
            </span>
        @elseif($hasSsrError)
            <span class="absolute top-1/2 right-3 -translate-y-1/2 text-error-500 pointer-events-none">
                <i data-lucide="circle-alert" class="w-5 h-5"></i>
            </span>
        @endif

        @if ($alpineError)
            <span x-show="{!! $alpineError !!}"
                class="absolute top-1/2 right-3 -translate-y-1/2 text-error-500 pointer-events-none z-20">
                <i data-lucide="circle-alert" class="w-5 h-5"></i>
            </span>
        @endif

        @if (!$alpineError && ($suffix || in_array($type, ['date', 'time'])))
            <span
                class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-500 dark:text-gray-400 pointer-events-none">
                @if ($suffix)
                    {{ $suffix }}
                @elseif($type === 'date')
                    <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M6.66659 1.5415C7.0808 1.5415 7.41658 1.87729 7.41658 2.2915V2.99984H12.5833V2.2915C12.5833 1.87729 12.919 1.5415 13.3333 1.5415C13.7475 1.5415 14.0833 1.87729 14.0833 2.2915V2.99984L15.4166 2.99984C16.5212 2.99984 17.4166 3.89527 17.4166 4.99984V7.49984V15.8332C17.4166 16.9377 16.5212 17.8332 15.4166 17.8332H4.58325C3.47868 17.8332 2.58325 16.9377 2.58325 15.8332V7.49984V4.99984C2.58325 3.89527 3.47868 2.99984 4.58325 2.99984L5.91659 2.99984V2.2915C5.91659 1.87729 6.25237 1.5415 6.66659 1.5415ZM6.66659 4.49984H4.58325C4.30711 4.49984 4.08325 4.7237 4.08325 4.99984V6.74984H15.9166V4.99984C15.9166 4.7237 15.6927 4.49984 15.4166 4.49984H13.3333H6.66659ZM15.9166 8.24984H4.08325V15.8332C4.08325 16.1093 4.30711 16.3332 4.58325 16.3332H15.4166C15.6927 16.3332 15.9166 16.1093 15.9166 15.8332V8.24984Z"
                            fill="" />
                    </svg>
                @elseif($type === 'time')
                    <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M3.04175 9.99984C3.04175 6.15686 6.1571 3.0415 10.0001 3.0415C13.8431 3.0415 16.9584 6.15686 16.9584 9.99984C16.9584 13.8428 13.8431 16.9582 10.0001 16.9582C6.1571 16.9582 3.04175 13.8428 3.04175 9.99984ZM10.0001 1.5415C5.32867 1.5415 1.54175 5.32843 1.54175 9.99984C1.54175 14.6712 5.32867 18.4582 10.0001 18.4582C14.6715 18.4582 18.4584 14.6712 18.4584 9.99984C18.4584 5.32843 14.6715 1.5415 10.0001 1.5415ZM9.99998 10.7498C9.58577 10.7498 9.24998 10.4141 9.24998 9.99984V5.4165C9.24998 5.00229 9.58577 4.6665 9.99998 4.6665C10.4142 4.6665 10.75 5.00229 10.75 5.4165V9.24984H13.3334C13.7476 9.24984 14.0834 9.58562 14.0834 9.99984C14.0834 10.4141 13.7476 10.7498 13.3334 10.7498H10.0001H9.99998Z"
                            fill="" />
                    </svg>
                @endif
            </span>
        @endif
    </div>

    @if ($hasSsrError)
        <p class="text-theme-xs text-error-500 mt-1.5">
            {{ $errors->first($name) }}
        </p>
    @endif

    @if ($alpineError)
        <p x-show="{!! $alpineError !!}" x-text="{!! $alpineError !!}"
            class="text-theme-xs text-error-500 mt-1.5">
        </p>
    @endif
</div>
