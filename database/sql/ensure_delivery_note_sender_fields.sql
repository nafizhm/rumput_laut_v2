-- Run on the selected application database. Existing columns are left intact.
SELECT GROUP_CONCAT(CONCAT('ADD COLUMN `', wanted.column_name, '` ', wanted.column_type)
    ORDER BY wanted.column_name SEPARATOR ', ')
INTO @sender_columns_to_add
FROM (
    SELECT 'sender_name' AS column_name, 'VARCHAR(255) NULL' AS column_type
    UNION ALL SELECT 'origin_address', 'TEXT NULL'
    UNION ALL SELECT 'sender_contact', 'VARCHAR(255) NULL'
) AS wanted
LEFT JOIN information_schema.columns AS existing
    ON existing.table_schema = DATABASE()
    AND existing.table_name = 'delivery_notes'
    AND existing.column_name = wanted.column_name
WHERE existing.column_name IS NULL;

SET @sender_fields_sql = IF(@sender_columns_to_add IS NULL,
    'SELECT ''Kolom pengirim sudah lengkap'' AS hasil',
    CONCAT('ALTER TABLE `delivery_notes` ', @sender_columns_to_add));
PREPARE sender_fields_statement FROM @sender_fields_sql;
EXECUTE sender_fields_statement;
DEALLOCATE PREPARE sender_fields_statement;
