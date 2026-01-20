@props([
    'label' => null,
    'placeholder' => 'Select option',
    'options' => [],
    'name' => null,
    'multiple' => false,
    'searchable' => null,
    'selected' => null,
    'remoteUrl' => null,
    'error' => null,
    'alpineError' => null,
])

@php
    $id = $attributes->get('id') ?? 'select-' . Str::random(8);
    $isSearchable = $remoteUrl ? $searchable ?? true : $searchable ?? false;
    $isSearchable = filter_var($isSearchable, FILTER_VALIDATE_BOOLEAN);
    $hasSsrError = $name && $errors->has($name);

    if ($multiple) {
        $initialSelected = is_array($selected) ? $selected : ($selected ? [$selected] : []);
    } else {
        $initialSelected = is_array($selected) ? $selected[0] ?? null : $selected;
    }
@endphp

<div x-data="{
    open: false,
    options: @js($options),
    selected: @js($multiple ? $initialSelected : ($initialSelected !== null ? [$initialSelected] : [])),
    search: '',
    multiple: @js($multiple),
    searchable: @js($isSearchable),
    remoteUrl: @js($remoteUrl),
    loading: false,

    init() {
        this.selected = this.selected.filter(v => v !== null && v !== '' && v !== undefined);

        if (this.remoteUrl) {
            this.fetchOptions();

            this.$watch('search', (value) => {
                if (value.length >= 2 || value.length === 0) {
                    this.fetchOptions();
                }
            });
        }

        window.addEventListener('set-selected-{{ $name }}', (e) => {
            let val = e.detail;

            if (this.multiple) {
                this.selected = Array.isArray(val) ? val : [val];
            } else {
                this.selected = (val !== null && val !== '') ? [val] : [];
            }

            this.selected = this.selected.filter(v => v !== null && v !== '' && v !== undefined);
        });

        this.$watch('selected', (value) => {
            this.$dispatch('change', this.multiple ? value : (value.length > 0 ? value[0] : null));
        });
    },

    async fetchOptions(isInitial = false) {
        if (!this.remoteUrl) return;

        this.loading = true;
        try {
            const url = new URL(this.remoteUrl, window.location.origin);

            if (isInitial && this.selected.length > 0) {
                url.searchParams.append('values', this.selected.join(','));
            } else {
                url.searchParams.append('search', this.search);
            }

            const response = await fetch(url);
            const data = await response.json();

            if (isInitial) {
                this.options = data;
            } else {
                const newOptions = data;
                const selectedOptions = this.options.filter(opt => this.selected.includes(opt.value));
                this.options = [...newOptions, ...selectedOptions.filter(so => !newOptions.find(no => no.value == so.value))];
            }
        } catch (error) {
            console.error('Fetch error:', error);
        } finally {
            this.loading = false;
        }
    },

    get filteredOptions() {
        if (this.remoteUrl) return this.options;
        if (!this.search) return this.options.slice(0, 100);
        return this.options
            .filter(opt => opt.label.toLowerCase().includes(this.search.toLowerCase()))
            .slice(0, 100);
    },

    get selectedLabels() {
        return this.options.filter(opt => this.selected.includes(opt.value)).map(opt => opt.label);
    },

    toggleItem(value) {
        if (this.multiple) {
            if (this.selected.includes(value)) {
                this.selected = this.selected.filter(item => item !== value);
            } else {
                this.selected.push(value);
            }
        } else {
            this.selected = [value];
            this.open = false;
        }
    },

    removeItem(value) {
        this.selected = this.selected.filter(item => item !== value);
    },

    isSelected(value) {
        return this.selected.includes(value);
    }
}" {{ $attributes->merge(['class' => 'w-full']) }}>
    @if ($label)
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <template x-for="val in selected" :key="val">
            <input type="hidden" :name="multiple ? '{{ $name }}[]' : '{{ $name }}'"
                :value="val">
        </template>

        <div @click="open = !open"
            class="flex min-h-11 w-full cursor-pointer items-center justify-between rounded-lg border bg-transparent px-4 py-2 text-sm text-gray-800 transition-all focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @if ($hasSsrError) border-error-300 ring-error-500/10 dark:border-error-700 @else shadow-theme-xs hover:border-gray-400 dark:hover:border-gray-600 border-gray-300 dark:border-gray-700 @endif"
            :class="{
                'border-brand-300 ring-2 ring-brand-500/10 dark:border-brand-800 dark:ring-brand-500/5': open && !
                    {!! $alpineError ? $alpineError : 'false' !!},
                'border-error-300! dark:border-error-700!': {!! $alpineError ? $alpineError : 'false' !!}
            }">

            <div class="flex flex-auto flex-wrap gap-2 overflow-hidden">
                <template x-if="multiple">
                    <div class="flex flex-auto flex-wrap gap-2">
                        <template x-for="itemValue in selected" :key="itemValue">
                            <div
                                class="group flex items-center justify-center rounded-full border border-gray-200 bg-gray-100 py-1 pr-2 pl-2.5 text-sm text-gray-800 hover:border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white/90 dark:hover:border-gray-700">
                                <div class="max-w-full flex-initial"
                                    x-text="options.find(o => o.value == itemValue)?.label"></div>
                                <div class="flex flex-auto flex-row-reverse">
                                    <div @click.stop="removeItem(itemValue)"
                                        class="cursor-pointer pl-2 text-gray-500 group-hover:text-gray-400 dark:text-gray-400">
                                        <svg class="fill-current" role="button" width="14" height="14"
                                            viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M3.40717 4.46881C3.11428 4.17591 3.11428 3.70104 3.40717 3.40815C3.70006 3.11525 4.17494 3.11525 4.46783 3.40815L6.99943 5.93975L9.53095 3.40822C9.82385 3.11533 10.2987 3.11533 10.5916 3.40822C10.8845 3.70112 10.8845 4.17599 10.5916 4.46888L8.06009 7.00041L10.5916 9.53193C10.8845 9.82482 10.8845 10.2997 10.5916 10.5926C10.2987 10.8855 9.82385 10.8855 9.53095 10.5926L6.99943 8.06107L4.46783 10.5927C4.17494 10.8856 3.70006 10.8856 3.40717 10.5927C3.11428 10.2998 3.11428 9.8249 3.40717 9.53201L5.93877 7.00041L3.40717 4.46881Z"
                                                fill="" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="selected.length == 0" class="flex-1">
                            <span class="text-gray-400 dark:text-white/30">{{ $placeholder }}</span>
                        </div>
                    </div>
                </template>

                <template x-if="!multiple">
                    <div class="flex items-center">
                        <template x-if="selected.length === 0">
                            <span class="text-gray-400 dark:text-white/30">{{ $placeholder }}</span>
                        </template>
                        <template x-if="selected.length > 0">
                            <span class="truncate" x-text="selectedLabels[0]"></span>
                        </template>
                    </div>
                </template>
            </div>

            <div class="ml-2 flex shrink-0 items-center text-gray-500 dark:text-gray-400">
                <svg class="h-5 w-5 transition-transform duration-200" :class="open ? 'rotate-180' : ''" width="20"
                    height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </div>
        </div>

        <div x-show="open" x-cloak @click.outside="open = false" x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 mt-2 w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">

            <template x-if="searchable">
                <div class="border-b border-gray-100 p-2 dark:border-gray-800">
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <template x-if="!loading">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </template>
                            <template x-if="loading">
                                <svg class="h-4 w-4 animate-spin text-brand-500" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </template>
                        </div>
                        <input type="text" x-model.debounce.500ms="search" placeholder="Search..."
                            class="h-9 w-full rounded-lg border-gray-200 bg-gray-50 pl-9 pr-3 text-sm focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-800 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500 transition-all outline-hidden"
                            @click.stop>
                    </div>
                </div>
            </template>

            <div class="max-h-60 overflow-y-auto p-1 custom-scrollbar">
                <template x-for="opt in filteredOptions" :key="opt.value">
                    <div @click="toggleItem(opt.value)"
                        class="group flex cursor-pointer items-center justify-between rounded-lg px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800/50"
                        :class="isSelected(opt.value) ?
                            'bg-brand-50/30 text-brand-600 dark:bg-brand-900/10 dark:text-brand-400' : ''">
                        <span x-text="opt.label" :class="isSelected(opt.value) ? 'font-semibold' : ''"></span>
                        <template x-if="isSelected(opt.value)">
                            <svg class="h-4 w-4 animate-in fade-in zoom-in duration-200" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </template>
                    </div>
                </template>

                <template x-if="filteredOptions.length === 0 && !loading">
                    <div class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400 italic">
                        <template x-if="search">
                            <span>No results found for <span class="font-bold"
                                    x-text="'\'' + search + '\''"></span></span>
                        </template>
                        <template x-if="!search">
                            <span>No options available</span>
                        </template>
                    </div>
                </template>

                <template x-if="loading && filteredOptions.length === 0">
                    <div
                        class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400 flex items-center justify-center gap-2">
                        <svg class="h-4 w-4 animate-spin text-brand-500" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span>Loading...</span>
                    </div>
                </template>
            </div>
        </div>
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

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(156, 163, 175, 0.3);
        border-radius: 20px;
    }

    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(75, 85, 99, 0.3);
    }
</style>
