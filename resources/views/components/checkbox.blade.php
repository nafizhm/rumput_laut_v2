@props(['name', 'label' => '', 'checked' => false, 'model' => null, 'value' => null])

<div x-data="{
    checkboxToggle: {{ $checked ? 'true' : 'false' }},
    get isChecked() {
        if (this.{{ $model }}) {
            if (Array.isArray(this.{{ $model }})) {
                return this.{{ $model }}.includes('{{ $value ?? $name }}');
            }
            return this.{{ $model }};
        }
        return this.checkboxToggle;
    },
    toggle() {
        if (this.{{ $model }} !== undefined) {
            if (Array.isArray(this.{{ $model }})) {
                if (this.{{ $model }}.includes('{{ $value ?? $name }}')) {
                    this.{{ $model }} = this.{{ $model }}.filter(i => i !== '{{ $value ?? $name }}');
                } else {
                    this.{{ $model }}.push('{{ $value ?? $name }}');
                }
            } else {
                this.{{ $model }} = !this.{{ $model }};
            }
        } else {
            this.checkboxToggle = !this.checkboxToggle;
        }
    }
}" class="flex items-center">
    <label for="{{ $name }}"
        class="flex cursor-pointer items-center text-sm font-medium text-gray-700 select-none dark:text-gray-400">
        <div class="relative">
            <input type="checkbox" id="{{ $name }}" name="{{ $name }}" class="sr-only"
                :checked="isChecked" @change="toggle()" />

            <div :class="isChecked
                ?
                'border-brand-500 bg-brand-500' :
                'bg-transparent border-gray-300 dark:border-gray-700'"
                class="hover:border-brand-500 dark:hover:border-brand-500 mr-3 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px] transition">
                <span :class="isChecked ? '' : 'opacity-0'">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="white" stroke-width="1.94437"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
            </div>
        </div>

        {{ $label }}
    </label>
</div>
