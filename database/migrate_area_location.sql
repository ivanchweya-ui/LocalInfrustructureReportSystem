SET @has_location = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = 'local_infra_db'
      AND table_name = 'areas'
      AND column_name = 'location'
);

SET @add_location = IF(
    @has_location = 0,
    'ALTER TABLE local_infra_db.areas ADD COLUMN location VARCHAR(150) NULL AFTER region',
    'SELECT 1'
);

PREPARE add_location_stmt FROM @add_location;
EXECUTE add_location_stmt;
DEALLOCATE PREPARE add_location_stmt;

UPDATE local_infra_db.areas
SET location = CONCAT(area_name, ', ', region)
WHERE location IS NULL OR location = '';

ALTER TABLE local_infra_db.areas
MODIFY location VARCHAR(150) NOT NULL;
