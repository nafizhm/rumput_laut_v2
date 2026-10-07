# Analisis bisnis dan rancangan COA

Aplikasi mengelola pengumpulan dan perdagangan rumput laut: pembiayaan petani, pembelian panen, gudang basah/kering, perpindahan stok dan susut, penjualan ke pabrik, PO konsumen, surat jalan, invoice dan cicilan, serta pemasukan/pengeluaran operasional.

## Temuan dari implementasi

- `LoanService` memberikan pinjaman kepada petani dan menaikkan `farmers.current_debt`. Dari sudut pandang perusahaan ini adalah piutang (Harta), bukan utang perusahaan atau biaya.
- `HarvestTransactionService` menghitung nilai panen bersih setelah potongan, membagi menjadi pembayaran kas dan pengurangan piutang, kemudian menambah berat gudang basah. Potongan harga pembelian mengurangi biaya perolehan, bukan pendapatan baru.
- `FarmerPaymentService` menerima pelunasan tunai dan mengurangi piutang petani. Penerimaan ini bukan pendapatan baru.
- `WarehouseService` mencatat kuantitas penerimaan, transfer, susut, dan penyesuaian. Nilai persediaan/HPP serta metode biaya (misalnya rata-rata tertimbang atau FIFO) belum dikelola sebagai buku besar.
- `SalesService` mengurangi stok dan membuat penjualan dengan status pembayaran. `InvoiceService` / `InvoicePaymentService` mengelola dokumen penagihan dan pembayaran terpisah. Perlu hubungan transaksi yang jelas sebelum membuat jurnal otomatis agar penjualan tidak dihitung dua kali.
- `CashFlowController` saat ini menjumlah nilai penjualan berdasarkan tanggal penjualan tanpa memfilter pembayaran; pembayaran invoice dan pelunasan petani belum tercakup. Nilai tersebut belum dapat dianggap kas aktual atau laba akuntansi.
- `TransactionService` menyimpan pemasukan/pengeluaran berdasarkan kategori; belum membedakan pendapatan, modal, pinjaman, aset, atau pelunasan piutang menggunakan COA.

## Pembagian kode akun

Kode lima digit merupakan rancangan internal untuk usaha ini, bukan nomor baku yang diwajibkan standar.

| Awalan | Kategori | Subkelompok | Saldo normal umum |
| --- | --- | --- | --- |
| 1xxxx | Harta | Kas/bank, piutang, persediaan, uang muka, aset tetap, akumulasi penyusutan | Debit |
| 2xxxx | Utang | Utang lancar, uang muka konsumen, pinjaman perusahaan | Kredit |
| 3xxxx | Modal | Modal pemilik, prive, laba ditahan, laba/rugi berjalan | Kredit |
| 4xxxx | Pendapatan | Penjualan basah/kering, potongan penjualan, jasa, pendapatan lainnya | Kredit |
| 5xxxx | Biaya | HPP, kerugian stok, operasional, penyusutan, keuangan, pajak | Debit |

Pengecualian: akumulasi penyusutan bersaldo Kredit; prive serta retur/potongan penjualan bersaldo Debit. Master disimpan pada `chart_of_accounts`; kode unik dan awalan kategori divalidasi. Akun dapat ditambahkan, diedit, dan dinonaktifkan.

## Rencana pemetaan transaksi

Contoh berikut adalah rancangan untuk tahap jurnal berikutnya, belum posting otomatis.

| Peristiwa | Debit | Kredit |
| --- | --- | --- |
| Pencairan pinjaman petani | 11201 Piutang Petani | 11101 Kas / 11103 Bank |
| Pelunasan tunai petani | 11101 / 11103 | 11201 |
| Pembelian panen dibayar sebagian kas dan sebagian potongan pinjaman | 11301 Persediaan Basah sebesar nilai perolehan bersih | 11101 / 11103 sebesar kas dibayar; 11201 sebesar piutang yang dilunasi |
| Pembelian panen belum dibayar | 11301 | 21101 Utang Pembelian kepada Petani |
| Transfer basah ke kering | 11302 Persediaan Kering | 11301 Persediaan Basah, berdasarkan nilai biaya yang ditransfer |
| Penjualan kredit | 11202 Piutang Usaha | 41101 / 41102 Penjualan |
| Pengeluaran stok terjual | 51101 / 51102 HPP | 11301 / 11302 Persediaan |
| Pembayaran/cicilan invoice | 11101 / 11103 | 11202; bukan penjualan baru |
| Kerusakan/kehilangan stok abnormal | 51201 Kerugian Persediaan | 11301 / 11302 |
| Pembayaran biaya operasional | Akun 52xxx sesuai sifat biaya | 11101 / 11103 |
| Setoran modal | 11101 / 11103 | 31101 Modal Pemilik |
| Pengambilan pribadi pemilik | 31102 Prive | 11101 / 11103 |
| Penerimaan pinjaman perusahaan | 11101 / 11103 | 21301 / 22101 |

Susut normal pengeringan tidak otomatis menjadi kerugian: biaya persediaan perlu dialokasikan ke hasil produksi. Biaya angkut pembelian/pengolahan langsung yang memenuhi biaya perolehan masuk persediaan; biaya pengiriman penjualan menjadi biaya operasional. PO/surat jalan bukan pemicu pendapatan tambahan jika transaksi yang sama sudah dicatat; waktu pengakuan memerlukan kebijakan penyerahan barang yang jelas.

Referensi konsep lima elemen laporan keuangan: [IFRS Conceptual Framework](https://www.ifrs.org/issued-standards/list-of-standards/conceptual-framework/). Implementasi saat ini menambahkan master COA dan akses menu Keuangan; belum mengubah transaksi lama, membuat saldo awal, neraca, atau jurnal otomatis.
