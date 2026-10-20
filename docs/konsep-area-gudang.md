# Area gudang dinamis

Sumber kebenaran adalah master `warehouse_areas`, bukan nama bawaan seeder.

- `id`: identitas lokasi yang dirujuk transaksi melalui `warehouse_area_id`.
- `name` dan `code`: label yang dapat diatur melalui master. Angka, huruf, dan awalan kode tidak menentukan fungsi.
- `usage`: fungsi area (`receiving` untuk penerimaan, `processing` untuk proses/WIP, `finished` untuk barang ready/siap jual).
- `inventory_id`: gudang terkait; penerimaan memakai tahap stok `material`, siap jual memakai `finished`.
- `is_active`: menentukan ketersediaan area untuk transaksi baru.

Dashboard mengelompokkan berdasarkan `usage` dan menampilkan nama dari master. Pada contoh dashboard pengguna, Area A–D berada di penerimaan dan Area 1–4 di siap jual. Ini konfigurasi master, bukan aturan penamaan universal. Nama lain dan jumlah area lain juga sah.

Seeder berisi contoh awal yang dapat berbeda dari data operasional. Jangan menyimpulkan konfigurasi aktif dari seeder, menebak fungsi dari nama/kode, atau mengganti nama/fungsi data yang sudah dipakai hanya untuk menyamakan contoh. Perubahan fungsi/gudang area yang sudah digunakan dibatasi untuk menjaga referensi stok.

Saat menelusuri aplikasi, periksa filter `usage`, relasi ID, dan data master yang tersedia. Jika hanya kode yang dibaca, bedakan contoh seeder dengan konfigurasi aktual. Pengujian harus mencakup nama serta kode yang berbeda dari bawaan.
