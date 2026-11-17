USE local_infra_db;

INSERT IGNORE INTO categories (category_name) VALUES
    ('Pothole'),
    ('Streetlight'),
    ('Water leak'),
    ('Blocked drain'),
    ('Garbage'),
    ('Other');

INSERT INTO areas (area_name, region, location, latitude, longitude) VALUES
    ('Clay City', 'Nairobi', 'Kasarani Constituency', NULL, NULL),
    ('Mwiki', 'Nairobi', 'Kasarani Constituency', NULL, NULL),
    ('Kasarani', 'Nairobi', 'Kasarani Constituency', NULL, NULL),
    ('Njiru', 'Nairobi', 'Njiru Sub-county', NULL, NULL),
    ('Ruai', 'Nairobi', 'Njiru Sub-county', NULL, NULL)
ON DUPLICATE KEY UPDATE location = VALUES(location);

INSERT IGNORE INTO reports
    (reference_code, category_id, area_id, description, landmark, photo_path, contact_method, contact_detail, current_status)
SELECT 'RF-2026-DEMO01', c.category_id, a.area_id,
       'Large pothole on the main road damaging vehicles',
       'Near the market', NULL, 'email', 'resident01@example.invalid', 'In Progress'
FROM categories c JOIN areas a ON a.area_name = 'Kasarani' AND a.region = 'Nairobi'
WHERE c.category_name = 'Pothole';

INSERT IGNORE INTO reports
    (reference_code, category_id, area_id, description, landmark, photo_path, contact_method, contact_detail, current_status)
SELECT 'RF-2026-DEMO02', c.category_id, a.area_id,
       'Streetlight has not worked for two weeks',
       'Near the community centre', NULL, 'email', 'resident02@example.invalid', 'Submitted'
FROM categories c JOIN areas a ON a.area_name = 'Mwiki' AND a.region = 'Nairobi'
WHERE c.category_name = 'Streetlight';

INSERT IGNORE INTO reports
    (reference_code, category_id, area_id, description, landmark, photo_path, contact_method, contact_detail, current_status)
SELECT 'RF-2026-DEMO03', c.category_id, a.area_id,
       'Water leaking from a pipe onto the road',
       'Near the primary school', NULL, 'email', 'resident03@example.invalid', 'Under Review'
FROM categories c JOIN areas a ON a.area_name = 'Clay City' AND a.region = 'Nairobi'
WHERE c.category_name = 'Water leak';

INSERT IGNORE INTO reports
    (reference_code, category_id, area_id, description, landmark, photo_path, contact_method, contact_detail, current_status)
SELECT 'RF-2026-DEMO04', c.category_id, a.area_id,
       'Blocked drain causing water to collect after rain',
       'Along the main access road', NULL, 'email', 'resident04@example.invalid', 'Submitted'
FROM categories c JOIN areas a ON a.area_name = 'Njiru' AND a.region = 'Nairobi'
WHERE c.category_name = 'Blocked drain';

INSERT IGNORE INTO reports
    (reference_code, category_id, area_id, description, landmark, photo_path, contact_method, contact_detail, current_status)
SELECT 'RF-2026-DEMO05', c.category_id, a.area_id,
       'Uncollected rubbish near the local shopping area',
       'Near the market entrance', NULL, 'email', 'resident05@example.invalid', 'Resolved'
FROM categories c JOIN areas a ON a.area_name = 'Ruai' AND a.region = 'Nairobi'
WHERE c.category_name = 'Garbage';

INSERT IGNORE INTO reports
    (reference_code, category_id, area_id, description, landmark, photo_path, contact_method, contact_detail, current_status)
SELECT 'RF-2026-DEMO06', c.category_id, a.area_id,
       'Damaged public utility cover creating a hazard',
       'At the road junction', NULL, 'email', 'resident06@example.invalid', 'In Progress'
FROM categories c JOIN areas a ON a.area_name = 'Kasarani' AND a.region = 'Nairobi'
WHERE c.category_name = 'Other';

INSERT INTO status_updates (report_id, staff_id, old_status, new_status, resolution_note)
SELECT r.report_id, NULL, NULL, 'Submitted', 'Demo report received'
FROM reports r
WHERE r.reference_code IN ('RF-2026-DEMO01', 'RF-2026-DEMO02', 'RF-2026-DEMO03', 'RF-2026-DEMO04', 'RF-2026-DEMO05', 'RF-2026-DEMO06')
  AND NOT EXISTS (
      SELECT 1 FROM status_updates u
      WHERE u.report_id = r.report_id AND u.new_status = 'Submitted'
  );

INSERT INTO status_updates (report_id, staff_id, old_status, new_status, resolution_note)
SELECT report_id, NULL, 'Submitted', 'Under Review', 'Demo: checked by ward staff'
FROM reports WHERE reference_code IN ('RF-2026-DEMO01', 'RF-2026-DEMO03', 'RF-2026-DEMO05', 'RF-2026-DEMO06')
  AND NOT EXISTS (
      SELECT 1 FROM status_updates u
      WHERE u.report_id = reports.report_id AND u.new_status = 'Under Review'
  );

INSERT INTO status_updates (report_id, staff_id, old_status, new_status, resolution_note)
SELECT report_id, NULL, 'Under Review', 'In Progress', 'Demo: work scheduled'
FROM reports WHERE reference_code IN ('RF-2026-DEMO01', 'RF-2026-DEMO05', 'RF-2026-DEMO06')
  AND NOT EXISTS (
      SELECT 1 FROM status_updates u
      WHERE u.report_id = reports.report_id AND u.new_status = 'In Progress'
  );

INSERT INTO status_updates (report_id, staff_id, old_status, new_status, resolution_note)
SELECT report_id, NULL, 'In Progress', 'Resolved', 'Demo: work completed'
FROM reports WHERE reference_code = 'RF-2026-DEMO05'
  AND NOT EXISTS (
      SELECT 1 FROM status_updates u
      WHERE u.report_id = reports.report_id AND u.new_status = 'Resolved'
  );
