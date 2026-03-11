@props([
    'name',
    'title' => 'Hapus Data',
    'message' => 'Apakah anda yakin ingin menghapus data ini?',
    'confirmText' => 'Hapus',
])

<x-modal :name="$name" maxWidth="max-w-sm" {{ $attributes }}>
    <div class="text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 dark:bg-rose-900">
            <svg class="h-6 w-6 text-rose-600 dark:text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                </path>
            </svg>
        </div>
        <h3 class="mb-2 text-lg font-medium text-gray-500 dark:text-gray-400">
            {{ $title }}
        </h3>
        <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
            {{ $message }}
        </p>

        <div class="flex items-center justify-center gap-3">
            <button @click="$dispatch('close-modal', '{{ $name }}')" type="button"
                class="flex items-center w-full rounded-lg border justify-center border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700">
                Batal
            </button>
            <button @click="$dispatch('confirm-delete-{{ $name }}')" type="button"
                class="flex items-center w-full rounded-lg justify-center bg-rose-600 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-rose-800 focus:outline-none focus:ring-4 focus:ring-rose-300 dark:focus:ring-rose-800">
                {{ $confirmText }}
            </button>
        </div>
    </div>
</x-modal>
