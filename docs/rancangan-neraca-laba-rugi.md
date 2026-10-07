# Neraca dan Laba Rugi — tahap jurnal manual

Jurnal Umum dan Saldo Awal telah disetujui dan diimplementasikan, bersama Neraca dan Laba Rugi dengan filter per bulan. Laporan hanya membaca jurnal Posted. Transaksi operasional dan data historis belum diposting otomatis.

## Cara menggunakan

1. Buka Keuangan → Saldo Awal. Tentukan tanggal mulai pembukuan dan masukkan saldo Harta, Utang, serta Modal yang telah direkonsiliasi. Saldo laba sebelumnya menggunakan Laba Ditahan.
2. Simpan Draft, periksa detail, lalu posting dengan konfirmasi. Total debit dan kredit harus sama serta lebih dari nol. Saldo awal hanya dapat diposting sekali.
3. Buka Jurnal Umum untuk transaksi dan penyesuaian sejak tanggal mulai pembukuan. Draft belum masuk laporan. Posting memerlukan hak akses tersendiri.
4. Buka Neraca atau Laba Rugi dan pilih bulan. Bulan sebelum mulai pembukuan ditandai belum tersedia.
5. Jurnal umum Posted tidak dapat diedit/dihapus. Gunakan pembalik dengan tanggal dan alasan, kemudian jurnal pengganti. Saldo awal dikoreksi melalui jurnal penyesuaian.

Nilai mendukung dua angka desimal. Input tanpa pemisah ribuan, memakai titik untuk desimal (contoh 1500000.50). Referensi sumber opsional tetapi unik untuk membantu mencegah pencatatan bukti yang sama dua kali. Nomor jurnal, pembuat, pemosting, dan waktu posting disimpan.

Belum ada penguncian/penutupan periode. Jurnal bertanggal mundur yang sah dapat mengubah laporan periode sebelumnya. Hasil tahun sebelumnya yang belum ditutup disajikan terpisah pada Modal; akun 32102 dihitung oleh laporan dan tidak boleh diisi manual. Jurnal penutup otomatis belum tersedia.

## Kekurangan data saat ini

1. COA berisi daftar akun, belum mempunyai jurnal umum atau saldo buku besar. Ledger petani adalah buku pembantu piutang, bukan jurnal lengkap perusahaan.
2. Kas/bank, modal, utang, aset tetap, akumulasi penyusutan, dan persediaan belum mempunyai saldo awal yang direkonsiliasi.
3. Transaksi pemasukan/pengeluaran belum mempunyai pemetaan akun dan akun lawan; pemasukan tidak selalu pendapatan, pengeluaran tidak selalu biaya.
4. Persediaan bahan, WIP, dan barang jadi belum mempunyai nilai biaya. Proses produksi dan perhitungan HPP belum tersedia.
5. Penjualan dan pembayaran mempunyai sumber lama dan baru. Hubungan invoice baru sudah ada; data historis belum seluruhnya direkonsiliasi. Penjualan melalui Surat Jalan juga dapat dicatat sebelum pengiriman aktual, sehingga tanggal pencatatan penjualan belum otomatis menjadi tanggal pengakuan pendapatan.
6. Belum ada jurnal penyesuaian untuk penyusutan, beban terutang, biaya dibayar di muka, pajak, atau koreksi.

## Menu dan periode yang diusulkan

Keuangan → Neraca dan Keuangan → Laba Rugi. Filter menggunakan bulan dan tahun, dengan bulan berjalan di Asia/Jakarta sebagai default. Nilai selalu berasal dari jurnal yang diposting, bukan jumlah dokumen operasional.

### Neraca

Untuk bulan Oktober 2026, judul laporan adalah "Neraca per 31 Oktober 2026". Laporan mengakumulasi saldo awal dan mutasi seluruh periode sampai akhir tanggal tersebut; tidak hanya mengambil mutasi Oktober.

- Harta: kas/bank, piutang, persediaan, uang muka/biaya dibayar di muka, aset tetap dikurangi akumulasi penyusutan.
- Utang: kewajiban usaha, biaya/pajak terutang, uang muka konsumen, dan pinjaman perusahaan.
- Modal: modal pemilik, pengurang prive, laba ditahan, serta hasil periode yang belum ditutup ke modal. Mekanisme penutupan harus menghindari penghitungan laba dua kali.
- Pemeriksaan: Harta = Utang + Modal. Selisih ditampilkan sebagai masalah pencatatan, bukan dimasukkan otomatis ke akun modal untuk memaksa seimbang.

Saldo akun pengurang disajikan dengan tanda sesuai kategori ekonomi, bukan sekadar dianggap positif karena mempunyai saldo normal yang berbeda.

### Laba Rugi

Untuk Oktober 2026, periode 1–31 Oktober 2026.

1. Penjualan dikurangi retur/potongan = penjualan bersih.
2. Penjualan bersih dikurangi HPP = laba kotor.
3. Laba kotor dikurangi beban operasional = laba usaha.
4. Pendapatan/beban lainnya, beban keuangan, dan beban pajak jika tercatat = laba/rugi bersih.

Pembayaran invoice mengurangi piutang, bukan mencatat penjualan kedua. Pinjaman petani adalah piutang. Setoran modal dan pinjaman yang diterima perusahaan bukan pendapatan; prive dan pembayaran pokok pinjaman bukan beban.

## Fondasi tahap pertama

Tahap pertama yang telah diterapkan: Jurnal Umum, Saldo Awal, dan kedua laporan dari jurnal yang diposting. Bagian keputusan lanjutan tetap memerlukan pembahasan sebelum otomatisasi.

- Jurnal memiliki tanggal, nomor, keterangan, referensi sumber, status Draft/Posted, dan beberapa baris akun debit/kredit.
- Total debit harus sama dengan total kredit; baris dan nilai divalidasi sebelum posting. Draft tidak masuk laporan.
- Saldo awal dicatat melalui jurnal saldo awal dengan tanggal mulai pembukuan yang disepakati dan nilai yang disediakan/review oleh penanggung jawab keuangan.
- Posting transaksi historis tidak dilakukan otomatis. Pemetaan sumber, cut-off, dan kelengkapan saldo harus direkonsiliasi terlebih dahulu.
- Jurnal yang sudah diposting dikoreksi melalui pembalik/penyesuaian; riwayat tidak dihapus diam-diam.
- Pada awal implementasi, penyesuaian/HPP dapat dicatat manual oleh pihak keuangan berdasarkan angka yang disahkan. Posting otomatis dan perhitungan biaya persediaan adalah tahap berikutnya, bukan angka estimasi sistem.
- UI menunjukkan jika saldo awal/jurnal/HPP belum lengkap. Angka kosong tidak diberi label nol atau laporan final.

## Keputusan lanjutan sebelum posting otomatis

- Tanggal mulai pembukuan dan saldo awal terverifikasi.
- Pemetaan transaksi ke COA dan pemisahan kas/bank.
- Kebijakan pengakuan penjualan sesuai penyerahan barang; hubungan invoice/pengiriman bukan alasan membuat pendapatan tambahan.
- Metode biaya persediaan (misalnya rata-rata tertimbang) dan alokasi biaya produksi/susut normal ke hasil jadi. Tidak otomatis memperlakukan seluruh pembelian sebagai HPP periode berjalan.
- Perlakuan data lama, tahun buku, penyesuaian, dan penutupan periode. FIFO urutan kerja gudang tidak harus menjadi metode biaya persediaan.

Referensi prinsip: [IAS 1 — Presentation of Financial Statements](https://www.ifrs.org/issued-standards/list-of-standards/ias-1-presentation-of-financial-statements.html/) dan [IAS 2 — Inventories](https://www.ifrs.org/issued-standards/list-of-standards/ias-2-inventories/). Rancangan ini tidak menyatakan kepatuhan penuh terhadap standar pelaporan entitas tertentu; standar yang berlaku dan kebijakan perusahaan perlu ditetapkan sesuai kebutuhan client.
