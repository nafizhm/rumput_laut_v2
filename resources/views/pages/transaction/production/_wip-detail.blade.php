<section class="space-y-4 rounded-lg border p-4">
    <h3 class="font-semibold">WIP dari {{ $run->sourceArea->name }}</h3>
    <div class="grid gap-3 text-sm sm:grid-cols-3">
        <p>Bahan masuk WIP: <strong>{{ $run->input_sacks }} karung</strong><br>{{ number_format($run->input_reference_weight_kg,2,',','.') }} Kg alokasi</p>
        <p>Selesai diproses: <strong>{{ $run->transfers->sum('source_sacks') }} karung bahan</strong><br>Hasil: {{ number_format($run->transfers->sum('output_weight_kg'),2,',','.') }} Kg / {{ $run->transfers->sum('package_count') }} kemasan</p>
        <p>Masih di WIP: <strong>{{ $run->remaining_wip_sacks }} karung</strong><br>{{ number_format($run->input_reference_weight_kg - $run->transfers->sum('reference_weight_kg'),2,',','.') }} Kg alokasi</p>
    </div>
    <p class="text-sm text-gray-500">Penyusutan = Kg alokasi bahan yang selesai dikurangi Kg hasil timbang. Angka negatif berarti kenaikan berat. Berat alokasi mengikuti stok asal, bukan timbang ulang bahan saat masuk WIP.</p>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm">
        <thead><tr>@foreach(['Tanggal','Karung bahan selesai','Kg alokasi','Area tujuan / hasil','Penyusutan','PIC / catatan'] as $heading)<th class="p-3">{{ $heading }}</th>@endforeach</tr></thead>
        <tbody>@forelse($run->transfers as $transfer)<tr class="border-t">
            <td class="p-3">{{ $transfer->transfer_date->format('d/m/Y') }}</td><td class="p-3">{{ $transfer->source_sacks }}</td><td class="p-3">{{ number_format($transfer->reference_weight_kg,2,',','.') }}</td>
            <td class="p-3">@foreach($transfer->outputs as $output)<p>{{ $output->area->name }}: {{ number_format($output->weight_kg,2,',','.') }} Kg / {{ $output->package_count }} kemasan</p>@endforeach</td>
            <td class="p-3">{{ number_format($transfer->shrinkage_kg,2,',','.') }} Kg<br>{{ number_format($transfer->reference_weight_kg > 0 ? $transfer->shrinkage_kg / $transfer->reference_weight_kg * 100 : 0,2,',','.') }}%</td>
            <td class="p-3 whitespace-pre-line">{{ $transfer->created_by_name }}<br>{{ $transfer->notes }}</td>
        </tr>@empty<tr><td colspan="6" class="p-3 text-gray-500">Belum ada pemindahan ke area jadi.</td></tr>@endforelse</tbody>
    </table></div>
    @if($run->transfers->isNotEmpty())<p class="text-sm font-semibold">Total penyusutan bahan selesai: {{ number_format($run->transfers->sum('reference_weight_kg') - $run->transfers->sum('output_weight_kg'),2,',','.') }} Kg</p>@endif
</section>
