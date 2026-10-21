# Hitung ulang potongan saat edit invoice

Penjualan keranjang baru menyimpan jenis dan nilai potongan tingkat transaksi pada `cart_deduction_type` dan `cart_deduction_value`. Jalankan migrasi `2026_10_21_000001_preserve_cart_deduction_rules.php` sebelum menggunakan kode ini.

Saat jumlah/harga barang invoice diedit, dasar nilai dihitung setelah bonus berat per kemasan. Potongan persentase mengikuti dasar nilai baru; potongan kg mengikuti rumus keranjang awal (dasar nilai total dikali kg potongan dibagi berat total terbaru). Potongan nominal tetap secara total, tetapi pembagiannya antarbarang dihitung ulang secara proporsional. Pembulatan kumulatif memastikan jumlah potongan per barang tepat sama dengan total potongan.

Total penjualan, total invoice, sisa tagihan, status pembayaran, dan jurnal terkait diselaraskan melalui alur update invoice yang sama. Perubahan yang membuat tagihan lebih kecil daripada pembayaran ditolak dalam satu transaksi database.

Data keranjang lama hanya menyimpan alokasi nominal; jenis/nilai input awal tidak tersedia. Migrasi tidak menebak aturan lama. Data tersebut tetap menggunakan nominal tersimpan. Di edit invoice, pilih jenis potongan seluruh barang dan isi nilai aslinya untuk memulihkan aturan. Aturan disimpan pada seluruh barang dan digunakan kembali saat kilo/harga berubah, termasuk setelah halaman dibuka ulang. Persentase dihitung dari nilai seluruh barang setelah bonus berat, dengan batas 100%. Potongan kerja sama pada pencairan tetap merupakan catatan pembayaran terpisah.
