# WIP — Work in Process / Work in Progress

Menu WIP menggantikan Proses Harian. Nama area tetap dinamis dari master; sumber harus berjenis `receiving` dan tujuan harus `finished`.

## Mulai WIP

Satu catatan mengambil karung dari tepat satu area penerimaan. Pengambilan otomatis memakai batch paling lama yang tersedia pada tanggal mulai. Sistem menyimpan area sumber, rincian batch, karung, berat alokasi, pekerjaan, PIC dan tanggal. Stok penerimaan berkurang saat mulai, sedangkan saldo WIP bertambah. WIP belum bisa dijual.

## Pemindahan hasil

Pemindahan dapat dilakukan beberapa kali. Setiap pemindahan mencatat tanggal, karung bahan yang selesai, kg hasil timbang, jumlah kemasan, area tujuan, PIC pencatat dan catatan. Satu pemindahan dapat membagi hasil ke beberapa area jadi. Karung selesai tidak boleh melebihi sisa WIP.

Berat alokasi bahan selesai dihitung proporsional terhadap karung awal WIP. Pembulatan kumulatif memastikan pemindahan terakhir menghabiskan berat dan biaya alokasi dengan tepat.

Penyusutan kg = berat alokasi bahan selesai − berat hasil timbang.

Penyusutan persen = penyusutan kg / berat alokasi bahan selesai × 100. Selisih negatif berarti kenaikan berat. Angka ini perbandingan terhadap berat alokasi dari stok asal, bukan pengukuran timbang ulang saat bahan masuk WIP. Jumlah karung bahan dan jumlah kemasan jadi adalah satuan berbeda.

Hasil setiap pemindahan langsung tersedia di area jadi, walaupun sebagian bahan masih di WIP. Pengeluaran tidak boleh bertanggal sebelum pemindahan hasil itu. Status WIP baru selesai (`packed`) ketika seluruh karung sumber telah diselesaikan. Token unik mencegah pengiriman ulang form mulai/pemindahan menggandakan stok.

## Dashboard dan riwayat

Dashboard menampilkan sisa di area penerimaan, sisa WIP berdasarkan area sumber, karung yang telah selesai, kg hasil kumulatif dan penyusutan bahan selesai. Contoh: dari 100 karung, diambil 30 ke WIP → 70 masih di area, 30 di WIP. Setelah 10 karung bahan selesai → 70 masih di area, 20 di WIP, hasil 10 karung tercatat terpisah sebagai kg dan kemasan jadi.

Riwayat lama tetap dipertahankan. Proses lama yang masih aktif ikut dihitung berdasarkan area batch sumber; transaksi selesai lama tidak diubah menjadi WIP baru. Riwayat Aktivitas Area hanya catatan pekerjaan tanpa mutasi stok dan tetap ditampilkan terpisah.

Migrasi: `2026_10_20_000001_add_tracked_wip_transfers.php`. Menambah penanda WIP dan referensi sumber, tabel pemindahan, referensi pemindahan pada batch jadi, serta mengganti label menu menjadi WIP.
