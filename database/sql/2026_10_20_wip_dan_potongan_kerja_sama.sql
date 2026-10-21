-- MySQL / MariaDB: jalankan sekali pada database hosting yang sudah memiliki
-- seluruh struktur aplikasi sebelum perubahan WIP tanggal 20 Oktober 2026.
-- Backup database dahulu. Upload kode aplikasi versi yang sesuai juga.
-- Jangan jalankan bila dua migrasi di bawah sudah pernah diterapkan.
-- Jika ada error, hentikan eksekusi: DDL MySQL tidak dibatalkan dengan ROLLBACK.

-- Pemeriksaan awal: hasil normal sebelum upgrade adalah kosong.
SELECT migration, batch FROM migrations
WHERE migration IN (
    '2026_10_20_000001_add_tracked_wip_transfers',
    '2026_10_20_000002_add_invoice_cooperation_deductions'
);

SET @upgrade_wip_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations);

-- 1. WIP: referensi area sumber dan pencegahan pengiriman ulang form.
ALTER TABLE production_runs
    ADD COLUMN wip_mode TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN wip_token CHAR(36) NULL,
    ADD COLUMN source_area_id BIGINT UNSIGNED NULL,
    ADD UNIQUE KEY production_runs_wip_token_unique (wip_token),
    ADD CONSTRAINT production_runs_source_area_id_foreign
        FOREIGN KEY (source_area_id) REFERENCES warehouse_areas (id)
        ON DELETE RESTRICT;

-- 2. Riwayat setiap pemindahan sebagian/seluruh WIP ke barang jadi.
CREATE TABLE wip_transfers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    production_run_id BIGINT UNSIGNED NOT NULL,
    token CHAR(36) NOT NULL,
    transfer_date DATE NOT NULL,
    source_sacks INT UNSIGNED NOT NULL,
    reference_weight_kg DECIMAL(12,2) NOT NULL,
    output_weight_kg DECIMAL(12,2) NOT NULL,
    package_count INT UNSIGNED NOT NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_by_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY wip_transfers_token_unique (token),
    CONSTRAINT wip_transfers_production_run_id_foreign
        FOREIGN KEY (production_run_id) REFERENCES production_runs (id)
        ON DELETE RESTRICT,
    CONSTRAINT wip_transfers_created_by_foreign
        FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE finished_batches
    ADD COLUMN wip_transfer_id BIGINT UNSIGNED NULL,
    ADD CONSTRAINT finished_batches_wip_transfer_id_foreign
        FOREIGN KEY (wip_transfer_id) REFERENCES wip_transfers (id)
        ON DELETE RESTRICT;

UPDATE menus SET name = 'WIP' WHERE route = 'produksi.index';

-- Tandai migrasi WIP agar php artisan migrate tidak mengulang perubahan ini.
INSERT INTO migrations (migration, batch)
VALUES ('2026_10_20_000001_add_tracked_wip_transfers', @upgrade_wip_batch);

-- 3. Potongan kerja sama per pencairan invoice; pembayaran lama tetap nol.
ALTER TABLE invoice_payments
    ADD COLUMN cooperation_deduction BIGINT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN cooperation_notes TEXT NULL;

INSERT INTO migrations (migration, batch)
VALUES ('2026_10_20_000002_add_invoice_cooperation_deductions', @upgrade_wip_batch);

-- 4. Siapkan akun biaya baru tanpa menimpa master akun yang sudah ada.
INSERT INTO chart_of_accounts
    (code, name, category, account_group, normal_balance, description,
     is_active, created_at, updated_at)
SELECT '52113', 'Biaya Kerja Sama Penjualan', 'Biaya', 'Biaya Operasional',
    'Debit', 'Potongan kerja sama pada pencairan invoice; mengurangi piutang tanpa menambah kas.',
    1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM chart_of_accounts WHERE code = '52113');

-- Pemeriksaan akhir: kedua migrasi harus muncul.
SELECT migration, batch FROM migrations
WHERE migration IN (
    '2026_10_20_000001_add_tracked_wip_transfers',
    '2026_10_20_000002_add_invoice_cooperation_deductions'
);
