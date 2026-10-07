@props(['title', 'description' => null])

<section {{ $attributes->class(['mb-5 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900']) }}>
    <div class="rounded-t-xl border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
        <h5 class="text-sm font-semibold text-gray-800 dark:text-white">{{ $title }}</h5>
        @if($description)<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $description }}</p>@endif
    </div>
    <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2">{{ $slot }}</div>
</section>
