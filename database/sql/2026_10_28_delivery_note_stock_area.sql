-- Update 28 Oktober 2026: area stok pada surat jalan dan detail surat jalan.
-- Sesuai migration 2026_10_28_000001_add_delivery_note_stock_area.
-- Jalankan sekali jika migration tersebut belum diterapkan.
-- Jika pemeriksaan menunjukkan migration/kolom sudah ada, jangan lanjutkan.
-- Hentikan eksekusi jika ada error; DDL MySQL tidak dapat di-ROLLBACK.

SELECT migration, batch FROM migrations
WHERE migration = '2026_10_28_000001_add_delivery_note_stock_area';

SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('delivery_notes', 'delivery_note_items')
  AND COLUMN_NAME = 'warehouse_area_id';

ALTER TABLE delivery_notes
    ADD COLUMN warehouse_area_id BIGINT UNSIGNED NULL,
    ADD CONSTRAINT delivery_notes_warehouse_area_id_foreign
        FOREIGN KEY (warehouse_area_id) REFERENCES warehouse_areas (id)
        ON DELETE RESTRICT;

ALTER TABLE delivery_note_items
    ADD COLUMN warehouse_area_id BIGINT UNSIGNED NULL,
    ADD CONSTRAINT delivery_note_items_warehouse_area_id_foreign
        FOREIGN KEY (warehouse_area_id) REFERENCES warehouse_areas (id)
        ON DELETE RESTRICT;

-- Jalankan pencatatan ini hanya setelah kedua ALTER TABLE berhasil.
INSERT INTO migrations (migration, batch)
SELECT '2026_10_28_000001_add_delivery_note_stock_area', COALESCE(MAX(batch), 0) + 1
FROM migrations;
