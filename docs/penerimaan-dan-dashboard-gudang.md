# Penerimaan dan dashboard gudang

Tahap ini menambahkan master area, penerimaan fisik, batch bahan masuk, riwayat kualitas menurut petani, dan dashboard lima tahap yang dapat diklik. Belum menambahkan pencatatan produksi harian, WIP, hasil press/packing, retur, atau persetujuan koreksi stok. Kartu untuk tahap tersebut menjelaskan alur yang akan digunakan dan tidak menampilkan angka produksi yang diasumsikan.

## Penerimaan baru

1. Catat pembelian panen jika barang dibeli melalui modul panen. Pembelian baru ditandai `receipt_mode=separate`; perubahan pembayaran/piutang tetap mengikuti modul pembelian, tanpa stok otomatis.
2. Buat Draft Penerimaan Barang. Pilih petani, referensi pembelian baru atau referensi nota/kedatangan unik, gudang bahan, dan tanggal aktual.
3. Isi satu atau beberapa baris kualitas/area, berat timbang aktual, dan jumlah karung. Jika hasil pemeriksaan belum ada, gunakan Q00 di area pemeriksaan. Area harus aktif, milik gudang yang sama, untuk bahan masuk, dan mengizinkan kualitas tersebut.
4. Konfirmasi Diterima setelah fisik barang tersedia. Konfirmasi mengunci dokumen dan referensi pembelian, memvalidasi kembali area/kualitas, menambah stok bahan, mencatat satu mutasi masuk unik, dan membentuk satu batch per baris kualitas/area. Semua berjalan dalam satu transaksi database.
5. Klik Barang Datang pada dashboard untuk melihat batch, asal petani, kualitas, area, berat datang, karung datang, dan karung belum diproses. Keterangan tersebut juga tersedia melalui tautan Penerimaan / Kualitas pada menu Petani.

Satu pembelian mempunyai maksimal satu penerimaan; satu penerimaan dapat mempunyai banyak baris. Jika pembelian datang bertahap, mekanisme penerimaan parsial perlu ditambahkan sebelum dipakai untuk kasus tersebut. Referensi nota unik mencegah input sumber manual yang sama lebih dari satu kali.

Berat datang bukan berat resmi barang jadi. Perbedaan nota dan timbang aktual merupakan selisih penerimaan, tidak otomatis dicatat sebagai susut/kehilangan. Berat referensi pembelian tidak dihitung ulang menjadi berat fisik, dan penerimaan tidak mengubah nilai pembelian atau pembayaran petani.

## Area awal

Seeder menyediakan gudang Bahan Masuk dan area BM-A (Q01), BM-B (Q02), BM-C (Q03), serta BM-K untuk pemeriksaan/karantina (Q00/Q04/Q05). Ini contoh awal yang dapat disesuaikan dengan layout client. Area dan grade dapat dinonaktifkan; gudang/fungsi area yang sudah dipakai penerimaan tidak boleh diganti.

## Data lama dan pengamanan

- Inventori lama ditandai `stock_stage=legacy` dan saldo dipertahankan. Pembelian lama ditandai `receipt_mode=legacy` sehingga tidak dapat dikonfirmasi ulang melalui penerimaan baru.
- Saldo Bahan Masuk memakai `stock_stage=material` dan ditampilkan terpisah; tidak dapat dipilih atau dijual sebagai barang jadi. Transfer/susut/penyesuaian manual terhadap gudang bahan diblokir agar tidak memutus saldo batch.
- Tombol Terima Stok lama diganti dengan tautan Penerimaan Barang. Endpoint penerimaan manual lama menolak penambahan langsung tanpa batch.
- Mutasi `goods_receipt_id` unik di database. Konfirmasi mengunci penerimaan, sumber pembelian, baris, area/kualitas, dan gudang. Klik ulang tidak menambah saldo/batch lagi.
- Penerimaan terkonfirmasi tidak dapat diubah/dihapus. Modul koreksi dengan alasan, PIC, dan persetujuan menjadi tahap terpisah; jangan menggunakan edit saldo untuk memperbaiki penerimaan.
- Hak akses area dan penerimaan diberikan kepada owner, superadmin, dan admin sesuai fungsi master/operasional. Semua rute baru memeriksa permission.

## Dashboard

Kartu Barang Datang menampilkan jumlah batch, berat penerimaan kumulatif, sisa karung belum diproses, dan jumlah Draft. Empat kartu berikutnya menampilkan penjelasan tahap tanpa nilai fiktif. Saldo lama memiliki tautan mutasi tersendiri; tidak otomatis dianggap stok barang jadi yang sudah press/timbang.

Tahap berikutnya: proses harian yang dapat mengambil karung dari beberapa batch, kemudian WIP, lalu press+timbang+packing bersama. Hasil perlu dibagi menurut kualitas/area dan menjadi sumber stok siap jual. Selisih proses baru dihitung dengan cakupan batch yang jelas, tidak memaksakan kg pada karung yang baru sebagian diproses.

Pengujian otomatis menggunakan SQLite; locking untuk MySQL diterapkan, uji beban paralel MySQL belum dilakukan.
