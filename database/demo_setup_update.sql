USE local_infra_db;

START TRANSACTION;

UPDATE reports r
JOIN areas old_area ON old_area.area_id = r.area_id
JOIN areas kasarani_area
    ON kasarani_area.area_name = 'Clay City'
    AND kasarani_area.region = 'Nairobi'
SET r.area_id = kasarani_area.area_id
WHERE r.reference_code = 'RF-2026-DEMO03'
  AND old_area.area_name = 'Githurai';

UPDATE areas
SET location = 'Kasarani Sub-County'
WHERE region = 'Nairobi'
  AND area_name IN ('Clay City', 'Mwiki', 'Kasarani', 'Njiru', 'Ruai');

DELETE FROM areas
WHERE region = 'Nairobi'
  AND area_name IN ('Githurai', 'Eastlands', 'Embakasi')
  AND NOT EXISTS (
      SELECT 1
      FROM reports
      WHERE reports.area_id = areas.area_id
  );

UPDATE status_updates
SET resolution_note = REPLACE(resolution_note, 'ward staff', 'Sub-County Administrator')
WHERE resolution_note LIKE '%ward staff%';

UPDATE staff
SET full_name = 'Charles Mutinda (Demo)',
    email = 'demo@kasarani.invalid'
WHERE username = 'officer1';

COMMIT;
