-- MySQL: alternatif manual untuk migration 2026_10_13_000001.
-- Jalankan salah satu: Laravel migration ATAU SQL ini, bukan keduanya.
CREATE TABLE `transaction_histories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `transaction_id` BIGINT UNSIGNED NOT NULL,
    `action` VARCHAR(20) NOT NULL,
    `actor_id` BIGINT UNSIGNED NULL,
    `actor_name` VARCHAR(255) NOT NULL,
    `reason` TEXT NULL,
    `before` JSON NULL,
    `after` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `transaction_histories_transaction_id_index` (`transaction_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Catat migration agar Laravel tidak mencoba membuat tabel yang sama lagi.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_13_000001_create_transaction_histories_table', COALESCE(MAX(`batch`), 0) + 1
FROM (SELECT `batch` FROM `migrations`) AS existing_batches;

-- Contoh membaca riwayat (ganti 123 dengan ID transaksi).
-- SELECT * FROM transaction_histories WHERE transaction_id = 123 ORDER BY id;
