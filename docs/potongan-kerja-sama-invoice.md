# Potongan kerja sama pada pencairan invoice

Pembagian hasil dihitung di luar aplikasi. Pada setiap pencairan, isi uang yang benar-benar diterima (`amount`), nominal potongan (`cooperation_deduction`, default nol) dan keterangan pihak/dasar kerja sama (`cooperation_notes`, wajib jika ada potongan).

Pengurang tagihan = uang diterima + potongan kerja sama. Nilai gabungannya tidak boleh melebihi sisa invoice. Keduanya boleh nol secara individual, tetapi gabungannya harus positif. Potongan tidak diulang otomatis pada cicilan berikutnya.

Contoh: invoice Rp100 juta, uang diterima Rp90 juta dan potongan Rp10 juta → invoice lunas, kas masuk Rp90 juta, biaya kerja sama Rp10 juta. Nilai invoice/penjualan tetap Rp100 juta.

Kolom lama `invoices.paid_amount` dan `sales_transactions.paid_amount` menyimpan total tagihan yang telah diselesaikan, termasuk potongan. Jangan menggunakannya sebagai angka kas. Pada invoice, `cash_received_amount` memisahkan uang masuk dan `cooperation_deduction_amount` menjumlahkan potongan. Riwayat pencairan menyimpan rincian per pembayaran.

Jurnal otomatis: debit kas sebesar uang diterima, debit akun 52113 (Biaya Kerja Sama Penjualan) sebesar potongan, kredit piutang sebesar tagihan yang diselesaikan. Bagian sebelum pengiriman mengikuti mekanisme uang muka konsumen yang sudah ada. Potongan bukan arus kas keluar tambahan. Laporan arus kas tetap hanya menggunakan uang sebenarnya.

Pembatalan melalui Riwayat Pencairan membalik jurnal pencairan beserta potongannya, menghitung ulang sisa invoice, dan menyelaraskan saldo penjualan terkait. Pembatalan memerlukan izin `invoice.update`.

Migrasi: `2026_10_20_000002_add_invoice_cooperation_deductions.php`. Pembayaran lama memperoleh potongan nol. Akun biaya disiapkan oleh seeder COA dan dibuat otomatis saat pertama digunakan oleh jurnal.
