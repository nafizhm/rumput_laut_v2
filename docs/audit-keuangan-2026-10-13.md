# Audit submenu Keuangan — 13 Oktober 2026

Urutan diterapkan ke database lokal dan disimpan dalam migrasi serta seeder agar konsisten ketika seeder menu dijalankan ulang.

| Urutan | Menu | Penggunaan dan hasil pemeriksaan |
| --- | --- | --- |
| 1 | COA | Menyiapkan akun; nominal dicatat lewat transaksi atau jurnal. Akun yang sudah dipakai jurnal Posted dilindungi dari perubahan klasifikasi. |
| 2 | Saldo Awal | Memulai pembukuan. Ringkasan saldo efektif kini memasukkan jurnal Posted pada tanggal mulai, termasuk koreksi modal; jurnal asli tetap tampil dalam tabel. |
| 3 | Manajemen Pinjaman | Pemberian pinjaman menambah piutang petani. Pengembalian tunai lewat detail petani, pemotongan lewat pembayaran panen. Panduan dan tautan ditambahkan. |
| 4 | Pembelian & Pembayaran Panen | Konfirmasi penerimaan gudang → penetapan harga → pembayaran atau potongan bertahap. Form harga hanya tampil setelah penerimaan dikonfirmasi; tanggal dibatasi sesuai penerimaan. |
| 5 | Pemasukan & Pengeluaran | Mencatat kas lainnya dengan akun transaksi dan Kas / Bank. Panduan membedakan pencatatan ini dari pembayaran pada modul panen, pinjaman, dan penjualan. |
| 6 | Jurnal Umum | Pemeriksaan jurnal otomatis, draft jurnal manual, posting, dan pembalik. Draft dikecualikan dari Neraca/Laba Rugi; jurnal otomatis dikoreksi lewat sumbernya. |
| 7 | Laporan Arus Kas | Menggunakan tanggal pembayaran. Filter tanggal/jenis divalidasi, tanggal awal/akhir terbalik tetap didukung, transaksi otomatis mendatang dikecualikan, nominal diurutkan sebagai angka. |
| 8 | Laba Rugi | Pendapatan, HPP, dan beban periode dihitung dari jurnal Posted. Keterangan integrasi otomatis diperbaiki. |
| 9 | Neraca | Saldo kumulatif akun hingga akhir bulan, termasuk modal dan laba berjalan. Keterangan integrasi otomatis diperbaiki. |

## Pemeriksaan data lokal

- Seluruh sembilan halaman daftar berhasil dirender dengan HTTP 200 menggunakan akses superadmin.
- 322 jurnal berstatus Posted: 320 otomatis dan 2 manual. Tidak ditemukan jurnal dengan debit dan kredit berbeda.
- Neraca seluruh 12 bulan yang memiliki jurnal, Oktober 2025–September 2026, seimbang dengan selisih nol.
- Modal Pemilik Rp2.000.000.000 terhitung pada seluruh periode tersebut dan tampil di ringkasan Saldo Awal.
- Piutang petani pada akun 11201 Rp622.946.700, sama dengan total saldo pinjaman pada master petani.
- Indikator penjualan tanpa jurnal HPP tidak aktif pada bulan-bulan yang diperiksa. Ini bukan pemeriksaan terhadap bukti atau kelengkapan seluruh biaya usaha.

## Validasi

60 tes lulus dengan 639 assertions, mencakup AccountingTest, ChartOfAccountTest, CashTransactionCoaTest, dan BusinessFlowAuditTest. Alur yang diuji termasuk pinjaman, pembelian dan pembayaran panen, pemotongan pinjaman, stok/produksi/HPP, pembayaran penjualan bertahap, pembalik, izin akses, serta rekonsiliasi laporan. Tes memakai SQLite dalam memori.

Kompilasi Blade, build aset Vite, dan migrasi urutan menu berhasil. Pemeriksaan halaman dilakukan melalui render server; interaksi visual browser belum diperiksa.
