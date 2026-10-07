# Tahap 1: Penjualan Langsung

Menu Manajemen Gudang → Manajemen Penjualan diganti dan dipindahkan menjadi Penjualan → Penjualan Langsung. Modul menggunakan tabel `sales_transactions` dan rute lama yang sama agar tidak menyalin/menggandakan data. Riwayat lama dan nomor referensinya dipertahankan. Nomor transaksi baru memakai `PJL-YYYYMMDD-XXXX`; kolom internal `invoice_number` tetap dipakai untuk kompatibilitas dan ditampilkan sebagai No. Penjualan, bukan dokumen invoice.

## Alur tahap ini

1. Pilih pabrik/konsumen dari Data Pabrik dan gudang stok (basah/kering atau inventori lain).
2. Masukkan tanggal, berat, karung, harga, bonus berat per karung, dan potongan.
3. Simpan penjualan: berat fisik dan karung berkurang sekali. Penjualan berstatus belum dibayar.
4. Setelah uang diterima, gunakan Pelunasan. Jumlah harus sama dengan total nilai jual, tanggal tidak boleh sebelum penjualan; bukti opsional untuk mendukung tunai. Pelunasan tidak memotong stok dan tidak dapat dicatat ulang.

Penjualan tunai mengikuti langkah yang sama, lalu langsung dilunasi pada tanggal uang diterima. Penjualan tanpa invoice yang belum dibayar tetap memiliki sisa tagihan. Tahap ini belum menyediakan cicilan penjualan langsung.

Perhitungan bonus per karung diselaraskan dengan ringkasan form: `(berat fisik - jumlah karung × bonus per karung) × harga`, kemudian potongan tambahan. Ini hanya berlaku transaksi baru, tidak menghitung ulang data lama. Stok keluar tetap memakai berat fisik keseluruhan. Pengecekan stok karung dan berat dilakukan dalam transaksi database.

Kolom tanggal bayar, jumlah dibayar, dan sisa tagihan ditampilkan. Untuk data lama berstatus lunas tetapi tanpa jumlah pembayaran, sisa tagihan nol dan jumlah pembayaran ditampilkan "Belum tercatat"; tidak mengarang tanggal/jumlah historis.

## Menu yang dipensiunkan

- Lokasi/nama **Manajemen Gudang → Manajemen Penjualan** sudah digantikan oleh **Penjualan → Penjualan Langsung**, menggunakan data dan fungsi yang sama.
- **Invoice**, **PO konsumen**, **Surat Jalan**, dan **Laporan Arus Kas** tetap diperlukan.

## Tahap 2: Invoice terhubung ke penjualan

Invoice baru wajib memilih satu penjualan yang sudah dicatat. Tidak ada pembuatan penjualan atau mutasi stok kedua dari invoice. PO dan surat jalan tetap dokumen referensi dan tidak mengurangi stok. Konsumen PO harus sesuai dengan pabrik/konsumen penjualan.

Nilai barang, kuantitas fisik, harga, dan nilai tagihan bersih mengikuti penjualan. Total tagihan disimpan sebagai snapshot agar tetap tepat untuk penjualan dengan bonus/potongan. Hasil cetak menampilkan nilai kotor serta potongan. Satu penjualan hanya dapat memiliki satu invoice; penjualan terkait tidak dapat diganti setelah invoice dihubungkan.

Pembayaran/cicilan invoice dicatat melalui tombol Bayar di Invoice. Jumlah dibayar, sisa tagihan, status, dan tanggal pembayaran terakhir disinkronkan ke penjualan. Pembayaran langsung melalui Penjualan Langsung diblokir untuk transaksi yang memiliki invoice. Status invoice tidak lagi dapat diubah manual menjadi lunas.

Invoice yang sudah memiliki pembayaran tidak boleh dihapus. Invoice tanpa pembayaran dapat dihapus tanpa membatalkan penjualan atau memulihkan stok. Penjualan yang masih memiliki invoice tidak dapat dihapus.

Invoice lama tidak dipasangkan otomatis: ditampilkan sebagai **Belum terhubung (data lama)** dan tetap dapat menerima pembayaran. Invoice lama tanpa pembayaran dapat dihubungkan secara eksplisit ke penjualan yang belum dibayar bila konsumen dan total sesuai. Data yang sudah dibayar memerlukan rekonsiliasi terpisah; tidak disalin atau dibuatkan mutasi stok historis. Menu Invoice tetap digunakan; tidak ada menu tambahan yang dipensiunkan pada tahap 2.

Alur penggunaan: **catat penjualan di Penjualan Langsung → pilih penjualan pada Invoice (bila diperlukan) → catat pembayaran melalui Invoice**. Penjualan yang tidak memerlukan invoice tetap memakai pelunasan langsung. Nama Penjualan Langsung dipertahankan pada tahap ini, tetapi data di dalamnya menjadi sumber penjualan untuk invoice juga.

## Tahap 3: Konfirmasi pengiriman dan pemotongan stok

Berlaku untuk transaksi baru setelah migrasi `2026_10_07_000006`. Penjualan lama tetap `stock_mode=immediate`; Surat Jalan lama ditandai `status=legacy`. Migrasi hanya menambahkan metadata, tidak mengubah stok atau membuat mutasi historis.

Saat membuat penjualan baru, pilih mekanisme:

- **Langsung:** barang diserahkan saat penjualan disimpan, stok keluar sekali pada penjualan. Tidak dapat menggunakan pengeluaran Surat Jalan.
- **Melalui Surat Jalan:** penjualan disimpan tanpa memotong atau memesan stok. Surat Jalan baru harus memilih penjualan dengan mekanisme ini.

Alur dokumen: **Draft → Konfirmasi Dikirim → Dikirim**. Draft dapat diedit/dihapus dan dicetak dengan label belum dikirim. Konfirmasi meminta tanggal barang keluar dan centang bahwa barang benar-benar keluar. Status dokumen tidak dapat ditentukan melalui payload penyimpanan biasa.

Pengaman konfirmasi:

1. Hanya pengguna dengan permission `manajemen-penjualan.create` dapat mengonfirmasi.
2. Server mengunci Surat Jalan, penjualan, dan stok gudang dalam satu transaksi database.
3. Dokumen harus Draft baru, belum memiliki mutasi pengiriman, dan penjualan menggunakan mekanisme Surat Jalan. Penjualan dengan mutasi lama/langsung ditolak.
4. Pengiriman bertahap diperbolehkan. Total berat fisik dan karung seluruh mutasi pengiriman tidak boleh melebihi penjualan. Pembacaan mutasi menggunakan locking read agar melihat hasil konfirmasi sebelumnya pada MySQL REPEATABLE READ.
5. Stok berat dan karung diperiksa saat konfirmasi. Draft tidak mereservasi stok; konfirmasi dapat gagal jika stok sudah dipakai transaksi lain.
6. Satu `delivery_note_id` hanya boleh memiliki satu mutasi stok melalui unique constraint. Pengurangan stok, pembuatan mutasi, dan perubahan status dilakukan atomik; gagal salah satu membatalkan semuanya.
7. Tanggal aktual tidak boleh sebelum penjualan atau di masa depan. Waktu konfirmasi, ID/nama pengguna, tanggal aktual, berat, karung, dan saldo setelah mutasi disimpan.
8. Dokumen Dikirim tidak dapat diedit/dihapus; penjualan yang memiliki Surat Jalan juga tidak dapat dihapus. Retur/pembatalan pengiriman harus menggunakan proses tersendiri yang belum dibuat, bukan mengedit atau menghapus bukti pengeluaran.

Invoice untuk penjualan melalui Surat Jalan menampilkan hanya pengiriman yang sudah dikonfirmasi dari penjualan yang sama, termasuk pengiriman yang dikonfirmasi setelah invoice dibuat. Invoice dan pembayaran tetap tidak memotong stok. Tidak ada menu yang dipensiunkan pada tahap ini.

Pengujian otomatis memakai SQLite untuk draft, konfirmasi berulang, batas pengiriman bertahap, kekurangan stok, constraint unik, data lama, dan proteksi akses. Penguncian konkurensi MySQL diterapkan pada kode; uji beban paralel MySQL belum dilakukan.

## Tahap berikutnya

1. Tambahkan riwayat pembayaran/cicilan untuk penjualan tanpa invoice, termasuk strategi membaca pembayaran lama.
2. Perbaiki Laporan Arus Kas berdasarkan pembayaran aktual beserta penerimaan pelunasan petani. Laporan saat ini masih memakai perhitungan lama. Pembayaran invoice yang terhubung dan nilai dibayar pada penjualan tidak boleh dijumlah dua kali.
3. Rekonsiliasi invoice dan penjualan historis yang sudah dibayar, setelah memastikan keduanya mewakili transaksi yang sama.
4. Hubungkan ke COA dan jurnal setelah nilai persediaan/HPP ditetapkan.
