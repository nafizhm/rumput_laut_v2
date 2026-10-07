<section class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <h3 class="border-b border-gray-100 px-4 py-3 text-base font-semibold text-gray-900 sm:px-5 dark:border-gray-800 dark:text-white">{{ $sectionTitle }}</h3>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm"><tbody>
        @forelse($sectionRows as $row)
            <tr class="border-b border-gray-100 dark:border-gray-800"><td class="min-w-40 px-4 py-3 sm:px-5"><span class="mr-2 font-mono text-xs text-gray-500">{{ $row['code'] }}</span>{{ $row['name'] }}<span class="mt-1 block text-xs text-gray-500">{{ $row['group'] }}</span></td><td class="whitespace-nowrap px-4 py-3 text-right sm:px-5">{{ $money::format($row['amount']) }}</td></tr>
        @empty<tr><td class="px-4 py-4 text-sm text-gray-500 sm:px-5">Belum ada jurnal akun kelompok ini pada periode laporan.</td></tr>@endforelse
    </tbody><tfoot><tr class="bg-gray-50 font-semibold dark:bg-gray-800"><td class="px-4 py-3 sm:px-5">Total {{ $sectionTitle }}</td><td class="whitespace-nowrap px-4 py-3 text-right sm:px-5">{{ $money::format($sectionTotal) }}</td></tr></tfoot></table></div>
</section>
