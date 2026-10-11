@props(['label' => 'Keterangan'])

<details {{ $attributes->class(['my-2 min-w-0']) }}>
    <summary aria-label="{{ $label }}" title="{{ $label }}" class="inline-flex h-8 w-8 cursor-pointer list-none items-center justify-center rounded-full border border-gray-300 text-brand-600 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 dark:border-gray-700 dark:text-brand-300 dark:hover:bg-gray-800 [&::-webkit-details-marker]:hidden">
        <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/></svg>
    </summary>
    <div class="mt-2 space-y-2 rounded-lg bg-gray-50 p-4 text-sm leading-6 text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $slot }}</div>
</details>
