# Local Infrastructure Issue Reporter
Stack: HTML/CSS/JS, PHP, MySQL (XAMPP).

## Setup
1. Start Apache and MySQL in XAMPP.
2. Run: C:\xampp\php\php.exe database\install.php
3. Link the folder into htdocs and open http://localhost/infra_report/report.html

Demo staff login: officer1 / Officer@123
Demo codes: RF-2026-DEMO01 through RF-2026-DEMO06

## Demo behavior
- Demo reports and contact details are fictional sample data from different areas of Kasarani Sub-County, not real resident submissions. The presentation scenario assumes residents learned about the system at the Sub-County Administrator's physical office and then submitted reports from their own devices. Residents do not create accounts.
- Submitting a report shows the generated code. The app records a simulated delivery attempt in `logs/notifications.log`; it does not send email, SMS, or WhatsApp messages. Real delivery is future work.
- Report photos are uploaded to the server's `uploads/` folder; MySQL stores the relative file path, not the image bytes. This follows the documented design.
- Areas and wards are preconfigured for each regional installation; administrators cannot register areas from the dashboard. The current area data is sample data and does not confirm a deployed region. No map or GPS integration is included.

The seed wards Clay City, Mwiki, Kasarani, Njiru, and Ruai are listed for Kasarani Constituency in the [Kasarani Constituency reference](https://en.wikipedia.org/wiki/Kasarani_Constituency#Wards), which cites Kenya National Bureau of Statistics census material.

## Existing database
`database/install.php` drops and recreates `local_infra_db`. Do not rerun it if you need to preserve existing data. For an existing installation, import `database/migrate_area_location.sql` and then `database/seed.sql` in phpMyAdmin; the seed skips existing reference codes and status steps.
