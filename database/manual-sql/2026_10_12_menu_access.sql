-- MySQL / MariaDB: equivalent to 2026_10_12_000007_connect_user_menu_permissions.
-- Select the application database in phpMyAdmin before importing.
-- Upload the matching application code as well. Earlier migrations must already exist.
-- Re-running this script preserves existing records and avoids duplicate permissions.

SET @column_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.columns
           WHERE table_schema = DATABASE() AND table_name = 'users'
             AND column_name = 'uses_custom_permissions'),
    'SELECT 1',
    'ALTER TABLE `users` ADD COLUMN `uses_custom_permissions` TINYINT(1) NOT NULL DEFAULT 0'
);
PREPARE column_statement FROM @column_sql;
EXECUTE column_statement;
DEALLOCATE PREPARE column_statement;

START TRANSACTION;

-- Existing explicit selections become authoritative, except for superadmin.
-- CHAR(92) avoids dependence on MySQL backslash escaping settings.
SET @user_model = CONCAT('App', CHAR(92), 'Models', CHAR(92), 'User');
UPDATE users AS u
SET u.uses_custom_permissions = 1
WHERE EXISTS (
    SELECT 1 FROM model_has_permissions AS mp
    WHERE mp.model_id = u.id AND mp.model_type = @user_model
)
AND NOT EXISTS (
    SELECT 1 FROM model_has_roles AS mr
    JOIN roles AS r ON r.id = mr.role_id
    WHERE mr.model_id = u.id AND mr.model_type = @user_model AND r.name = 'superadmin'
);

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT new_permissions.name, 'web', NOW(), NOW()
FROM (
    SELECT 'production.update' AS name
    UNION ALL SELECT 'manajemen-gudang.read'
    UNION ALL SELECT 'invoice.read'
    UNION ALL SELECT 'invoice.create'
    UNION ALL SELECT 'invoice.update'
    UNION ALL SELECT 'invoice.delete'
    UNION ALL SELECT 'surat-jalan.read'
    UNION ALL SELECT 'surat-jalan.create'
    UNION ALL SELECT 'surat-jalan.update'
    UNION ALL SELECT 'surat-jalan.delete'
    UNION ALL SELECT 'po-konsumen.read'
    UNION ALL SELECT 'po-konsumen.create'
    UNION ALL SELECT 'po-konsumen.update'
    UNION ALL SELECT 'po-konsumen.delete'
) AS new_permissions
WHERE NOT EXISTS (
    SELECT 1 FROM permissions AS p
    WHERE p.name = new_permissions.name AND p.guard_name = 'web'
);

-- Preserve existing role defaults for production, warehouse, and sales documents.
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT DISTINCT target.id, r.id
FROM roles AS r
JOIN role_has_permissions AS existing ON existing.role_id = r.id
JOIN permissions AS source ON source.id = existing.permission_id AND source.guard_name = 'web'
JOIN permissions AS target ON target.guard_name = 'web' AND (
    (source.name = 'production.create' AND target.name = 'production.update')
    OR (source.name = 'receiving.read' AND target.name = 'manajemen-gudang.read')
    OR (
        source.name IN ('manajemen-penjualan.read', 'manajemen-penjualan.create',
                        'manajemen-penjualan.update', 'manajemen-penjualan.delete')
        AND SUBSTRING_INDEX(target.name, '.', 1) IN ('invoice', 'surat-jalan', 'po-konsumen')
        AND SUBSTRING_INDEX(target.name, '.', -1) = SUBSTRING_INDEX(source.name, '.', -1)
    )
)
WHERE r.guard_name = 'web'
AND NOT EXISTS (
    SELECT 1 FROM role_has_permissions AS assigned
    WHERE assigned.permission_id = target.id AND assigned.role_id = r.id
);

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM roles AS r
CROSS JOIN permissions AS p
WHERE r.name = 'superadmin' AND r.guard_name = 'web' AND p.guard_name = 'web'
AND NOT EXISTS (
    SELECT 1 FROM role_has_permissions AS assigned
    WHERE assigned.permission_id = p.id AND assigned.role_id = r.id
);

-- Synchronize menu permission names with the application's route modules.
UPDATE menus AS m
JOIN (
    SELECT 'users' AS route_module, 'users' AS permission_module
    UNION ALL SELECT 'company-profile', 'company-profile'
    UNION ALL SELECT 'manajemen-menu', 'menu'
    UNION ALL SELECT 'manajemen-permission', 'permission'
    UNION ALL SELECT 'manajemen-role', 'role'
    UNION ALL SELECT 'manajemen-petani', 'manajemen-petani'
    UNION ALL SELECT 'manajemen-kategori-pengeluaran', 'manajemen-kategori-pengeluaran'
    UNION ALL SELECT 'manajemen-pabrik', 'manajemen-pabrik'
    UNION ALL SELECT 'manajemen-inventori', 'manajemen-inventori'
    UNION ALL SELECT 'manajemen-pinjaman', 'manajemen-pinjaman'
    UNION ALL SELECT 'manajemen-panen', 'manajemen-panen'
    UNION ALL SELECT 'manajemen-pemasukan-pengeluaran', 'manajemen-pemasukan-pengeluaran'
    UNION ALL SELECT 'manajemen-gudang', 'manajemen-gudang'
    UNION ALL SELECT 'manajemen-penjualan', 'manajemen-penjualan'
    UNION ALL SELECT 'area-gudang', 'area'
    UNION ALL SELECT 'area-barang-masuk', 'area'
    UNION ALL SELECT 'area-barang-ready', 'area'
    UNION ALL SELECT 'kualitas-barang', 'quality'
    UNION ALL SELECT 'penerimaan-barang', 'receiving'
    UNION ALL SELECT 'produksi', 'production'
    UNION ALL SELECT 'stok-awal', 'opening-stock'
    UNION ALL SELECT 'invoice', 'invoice'
    UNION ALL SELECT 'surat-jalan', 'surat-jalan'
    UNION ALL SELECT 'po-konsumen', 'po-konsumen'
    UNION ALL SELECT 'harvest-finance', 'harvest-finance'
    UNION ALL SELECT 'saldo-awal', 'journal'
    UNION ALL SELECT 'jurnal', 'journal'
    UNION ALL SELECT 'neraca', 'financial-reports'
    UNION ALL SELECT 'laba-rugi', 'financial-reports'
    UNION ALL SELECT 'coa', 'coa'
    UNION ALL SELECT 'cash-flow', 'cash-flow'
) AS mapping ON mapping.route_module = SUBSTRING_INDEX(m.route, '.', 1)
SET m.permission = CONCAT(mapping.permission_module, '.',
    CASE SUBSTRING_INDEX(m.route, '.', -1)
        WHEN 'create' THEN 'create' WHEN 'store' THEN 'create'
        WHEN 'edit' THEN 'update' WHEN 'update' THEN 'update'
        WHEN 'destroy' THEN 'delete'
        WHEN 'confirm' THEN 'confirm' WHEN 'price' THEN 'price' WHEN 'pay' THEN 'pay'
        WHEN 'post' THEN 'post' WHEN 'reverse' THEN 'reverse'
        ELSE 'read'
    END), m.updated_at = NOW();

-- Route aliases and specialized actions.
UPDATE menus
SET permission = CASE route
    WHEN 'dashboard' THEN 'dashboard.read'
    WHEN 'dashboard.stats' THEN 'dashboard.read'
    WHEN 'dashboard.chart-data' THEN 'dashboard.read'
    WHEN 'menu-data' THEN 'menu.read'
    WHEN 'permission-data' THEN 'permission.read'
    WHEN 'role-data' THEN 'role.read'
    WHEN 'farmer-data' THEN 'manajemen-petani.read'
    WHEN 'farmer.ledgers' THEN 'manajemen-petani.read'
    WHEN 'farmer.pay' THEN 'manajemen-petani.update'
    WHEN 'farmer.pay.destroy' THEN 'manajemen-petani.delete'
    WHEN 'expense-category-data' THEN 'manajemen-kategori-pengeluaran.read'
    WHEN 'loan-data' THEN 'manajemen-pinjaman.read'
    WHEN 'harvest-data' THEN 'manajemen-panen.read'
    WHEN 'transaction-data' THEN 'manajemen-pemasukan-pengeluaran.read'
    WHEN 'saldo-awal.start-zero' THEN 'journal.post'
    WHEN 'penerimaan-barang.link-purchase' THEN 'receiving.update'
    WHEN 'produksi.complete' THEN 'production.confirm'
    WHEN 'produksi.pack' THEN 'production.confirm'
    WHEN 'manajemen-gudang.receive' THEN 'manajemen-gudang.create'
    WHEN 'manajemen-gudang.shrinkage' THEN 'manajemen-gudang.update'
    WHEN 'manajemen-gudang.transfer' THEN 'manajemen-gudang.update'
    WHEN 'manajemen-gudang.adjustment' THEN 'manajemen-gudang.update'
    WHEN 'manajemen-gudang.transfer-data' THEN 'manajemen-gudang.update'
    WHEN 'manajemen-gudang.adjustment-data' THEN 'manajemen-gudang.update'
    WHEN 'manajemen-penjualan.purchase-orders' THEN 'manajemen-penjualan.create'
    WHEN 'manajemen-penjualan.pay' THEN 'manajemen-penjualan.update'
    WHEN 'invoice.pay' THEN 'invoice.update'
    WHEN 'surat-jalan.dispatch' THEN 'surat-jalan.update'
    ELSE permission
END, updated_at = NOW()
WHERE route IN (
    'dashboard', 'dashboard.stats', 'dashboard.chart-data', 'menu-data', 'permission-data', 'role-data',
    'farmer-data', 'farmer.ledgers', 'farmer.pay', 'farmer.pay.destroy', 'expense-category-data',
    'loan-data', 'harvest-data', 'transaction-data', 'saldo-awal.start-zero', 'penerimaan-barang.link-purchase',
    'produksi.complete', 'produksi.pack', 'manajemen-gudang.receive', 'manajemen-gudang.shrinkage',
    'manajemen-gudang.transfer', 'manajemen-gudang.adjustment', 'manajemen-gudang.transfer-data',
    'manajemen-gudang.adjustment-data', 'manajemen-penjualan.purchase-orders', 'manajemen-penjualan.pay',
    'invoice.pay', 'surat-jalan.dispatch'
);

-- Mark this migration applied only after the preceding updates have succeeded.
INSERT INTO migrations (migration, batch)
SELECT '2026_10_12_000007_connect_user_menu_permissions', COALESCE(MAX(batch), 0) + 1
FROM migrations
HAVING NOT EXISTS (
    SELECT 1 FROM migrations
    WHERE migration = '2026_10_12_000007_connect_user_menu_permissions'
);

COMMIT;

-- Clear only Spatie's permission cache when using Laravel's database cache table.
SET @cache_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.tables
           WHERE table_schema = DATABASE() AND table_name = 'cache'),
    'DELETE FROM `cache` WHERE RIGHT(`key`, CHAR_LENGTH(''spatie.permission.cache'')) = ''spatie.permission.cache''',
    'SELECT 1'
);
PREPARE cache_statement FROM @cache_sql;
EXECUTE cache_statement;
DEALLOCATE PREPARE cache_statement;

SELECT COLUMN_NAME, COLUMN_DEFAULT FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'uses_custom_permissions';
SELECT name, guard_name FROM permissions
WHERE guard_name = 'web' AND (name IN ('production.update', 'manajemen-gudang.read')
    OR name LIKE 'invoice.%' OR name LIKE 'surat-jalan.%' OR name LIKE 'po-konsumen.%')
ORDER BY name;
