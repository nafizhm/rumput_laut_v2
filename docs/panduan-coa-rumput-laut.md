# Panduan akun usaha rumput laut

Buka Keuangan → COA untuk mencari akun. Nominal diisi melalui Saldo Awal atau Jurnal Umum. Daftar standar dilengkapi tanpa menimpa nama, klasifikasi, atau status akun yang telah diedit pengguna.

| Kebutuhan | Akun |
|---|---|
| Uang tunai / rekening | 11101 Kas Operasional / 11103 Bank |
| Setoran / penarikan pemilik | 31101 Modal Pemilik / 31102 Prive Pemilik |
| Pinjaman ke petani | 11201 Piutang Petani |
| Tagihan pelanggan | 11202 Piutang Usaha Pabrik / Konsumen |
| Pembelian panen belum dibayar | 21101 Utang Pembelian kepada Petani |
| Tagihan pemasok lainnya | 21102 Utang Usaha Pemasok |
| Uang pelanggan sebelum penyerahan barang | 21201 Uang Muka Konsumen |
| Bahan basah / bahan kering belum diolah | 11301 / 11302 |
| Bahan kemasan belum dipakai | 11303 Persediaan Karung dan Kemasan |
| Proses blower/jemur / menunggu press | 11304 Persediaan Dalam Proses (WIP) |
| Selesai press, timbang, packing | 11305 Persediaan Barang Jadi Rumput Laut |
| Penjualan rumput laut kering termasuk hasil packing | 41102 Penjualan Rumput Laut Kering |
| Biaya barang jadi yang terjual | 51103 Harga Pokok Penjualan Barang Jadi Rumput Laut |
| Kerusakan/kehilangan/susut abnormal terverifikasi | 51201 Kerugian Persediaan |

## Contoh jurnal manual

- Setoran pemilik Rp10 juta: debit 11101, kredit 31101, masing-masing 10000000.
- Beli bahan basah tunai Rp5 juta: debit 11301, kredit 11101, masing-masing 5000000.
- Bahan masuk proses dengan biaya Rp5 juta: debit 11304, kredit 11301, masing-masing 5000000.
- Upah pengolahan langsung Rp500 ribu yang layak masuk biaya produksi: debit 11304, kredit 11101, masing-masing 500000. Jangan sekaligus debit Biaya Gaji untuk nilai yang sama.
- Seluruh proses selesai, biaya aktual terverifikasi Rp5,5 juta: debit 11305, kredit 11304, masing-masing 5500000. Proses yang belum selesai tetap WIP.
- Barang jadi tersebut dijual tunai Rp7 juta: debit 11101, kredit 41102, masing-masing 7000000; jurnal biaya barang terjual: debit 51103, kredit 11305, masing-masing 5500000.

Contoh hanya ilustrasi, bukan transaksi yang dimasukkan sistem. Untuk penjualan sebagian, gunakan biaya bagian yang terjual, bukan seluruh biaya batch. Pemindahan produksi dan HPP masih manual; belum terhubung otomatis dengan proses gudang.

Akun persediaan kering yang telah dipakai sebelumnya perlu direkonsiliasi sebelum memindahkan saldo ke akun Barang Jadi. Jangan menjurnal saldo yang sama sebagai kering sekaligus barang jadi. HPP 51102 tetap tersedia untuk pemakaian terdahulu/penjualan langsung stok kering; gunakan satu akun HPP sesuai sumber stok tiap transaksi.

Area dan kualitas adalah rincian stok, bukan akun COA terpisah setiap area. Susut normal pengeringan diperhitungkan dalam biaya hasil produksi; perbedaan kg saja bukan dasar otomatis untuk jurnal rugi. Penilaian dan alokasi biaya memerlukan angka yang disahkan penanggung jawab keuangan.

Prinsip biaya pembelian/pengolahan dan pengakuan biaya saat persediaan dijual merujuk [IAS 2 — Inventories](https://www.ifrs.org/issued-standards/list-of-standards/ias-2-inventories/). Metode biaya dan otomatisasi belum ditetapkan oleh perubahan daftar akun ini.
