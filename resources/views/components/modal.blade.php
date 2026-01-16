@props(['name', 'title' => null, 'maxWidth' => 'max-w-[584px]'])

<div x-data="{ show: false }" x-cloak x-show="show"
    @open-modal.window="if ($event.detail === '{{ $name }}') show = true"
    @close-modal.window="if ($event.detail === '{{ $name }}') show = false" @keydown.escape.window="show = false"
    x-cloak class="fixed inset-0 z-9999">
    <div x-show="show" @click="show = false" x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-400/50 backdrop-blur-sm dark:bg-gray-800/50">
    </div>

    <div x-show="show"
        class="fixed inset-0 z-10 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center p-5 pointer-events-none">
        <div x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full {{ $maxWidth }} pointer-events-auto rounded-3xl bg-white p-6 shadow-xl dark:bg-gray-900 lg:p-10">
            <button @click="show = false"
                class="group absolute right-3 top-3 z-999 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-200 text-gray-500 transition-colors hover:bg-gray-300 hover:text-gray-500 dark:bg-gray-800 dark:hover:bg-gray-700 sm:right-6 sm:top-6 sm:h-11 sm:w-11">
                <svg class="transition-colors fill-current group-hover:text-gray-600 dark:group-hover:text-gray-200"
                    width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z"
                        fill="" />
                </svg>
            </button>

            @if ($title)
                <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">
                    {{ $title }}
                </h4>
            @endif

            <div class="space-y-6">
                {{ $slot }}
            </div>

            @if (isset($footer))
                <div class="flex items-center justify-end w-full gap-3 mt-6">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
