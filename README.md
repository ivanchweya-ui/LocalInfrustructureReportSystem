# Local Infrastructure Issue Reporter

Resident-facing infrastructure reporting and status tracking for a single sub-county installation. The application uses HTML, CSS, JavaScript, PHP, and MySQL.

## Deployment model

Deploy one application instance and one dedicated MySQL database per sub-county. Configure each instance with a county code and sub-county code. Reports, staff accounts, and reference-code lookups remain inside that database; an instance cannot query another sub-county's records.

The national directory in `database/kenya_subcounties.csv` contains 334 county/sub-county entries across 47 counties from the Census.ke copy of the KNBS 2019 census reference table. Its license is Creative Commons Attribution. This is a historical reference, not a guarantee of current administrative boundaries; confirm the selected unit with the County before deployment. Source: [Census.ke, Table 1.2](https://census.ke/@census-ke/table-1-2-list-of-counties-and-sub-counties) and [KNBS 2019 Census Volume II](https://www.knbs.or.ke/wp-content/uploads/2023/09/2019-Kenya-population-and-Housing-Census-Volume-2-Distribution-of-Population-by-Administrative-Units.pdf).

That directory lists sub-counties, not wards. Do not infer ward-to-sub-county assignments from constituency names. Before installation, prepare `database/areas.local.csv` from a County-verified area/ward list. Copy the header from `database/areas.local.example.csv`; every row must use the configured county and sub-county codes. This local file is ignored by Git.

## Installation

1. Install PHP 7.4+ with `mysqli`, MySQL, and a web server. Use HTTPS for deployments that handle real resident information.
2. Create a dedicated MySQL database and user for this sub-county. Grant that user access only to this database, including the schema privileges needed during installation (`CREATE`, `ALTER`, and `INDEX`). Do not use the MySQL root account for a deployed instance. The database must exist before running the installer.
3. Copy `php/config.local.example.php` to `php/config.local.php`. Set the database credentials and codes from `database/kenya_subcounties.csv`. Use a unique database name for every instance.
4. Create `database/areas.local.csv` using the county-verified area list and the format in `database/areas.local.example.csv`.
5. Coordinate with the Sub-County office to identify its administrator. During installation, the installation team creates that administrator's account for this specific instance. In PowerShell, enter the account details and run the installer. The password prompt below is masked:

   ```powershell
   $env:OFFICEADMINUSERNAME = Read-Host 'Sub-County Administrator username'
   $securePassword = Read-Host 'Sub-County Administrator password' -AsSecureString
   $env:OFFICEADMINPASSWORD = [System.Net.NetworkCredential]::new('', $securePassword).Password
   $env:OFFICEADMINNAME = Read-Host 'Sub-County Administrator full name'
   $env:OFFICEADMINEMAIL = Read-Host 'Administrator email (optional)'
   php database\install.php
   Remove-Item Env:\OFFICEADMINUSERNAME,Env:\OFFICEADMINPASSWORD,Env:\OFFICEADMINNAME,Env:\OFFICEADMINEMAIL -ErrorAction SilentlyContinue
   ```

   Use a unique password of at least 16 characters and hand the credentials to the administrator securely. The installer creates missing tables and categories, records the configured sub-county, imports the verified areas, and creates the approved Sub-County Administrator account. It does not drop tables, create sample reports, or create demo credentials. Re-running the installer does not reset an existing administrator's password.
6. Install the PHP mail dependency:

   ```powershell
   composer install --no-interaction
   ```

7. Serve the project through the configured web server and open `report.html`. Verify that the displayed sub-county and area list are correct before sharing the URL.

`database/install.php` is CLI-only. `database/schema.sql` uses `CREATE TABLE IF NOT EXISTS`; the installer upgrades the previous administrator role while retaining approved staff. It does not clean existing reports or area records. Back up existing data and review it before reusing an old database; using a new database for a new sub-county is the safer deployment path.

The local presentation database is `localinfrasystem`, configured for Nairobi
County and Kasarani Sub-County. The application database user is
`localinfraapp`. The database and local configuration are excluded from Git.

## Staff access

- Residents do not create accounts. They submit a report and use its reference code to check status.
- The installation team creates one approved Sub-County Administrator account during installation in coordination with the local office.
- There is no public staff sign-up, account approval queue, or separate System Administrator account. Each administrator belongs to that installation.
- Department officers, supervisors, work-order assignment, and internal department workflows are future extensions.

## Report and communication behavior

- Reference codes include the configured sub-county code and a cryptographically random token (for example `RF-2026-4704-A1B2C3D4E5F60718`); lookup searches only the current installation's database.
- Report photos are stored in `uploads/`; MySQL stores a relative path.
- The reference code is always shown in the browser. The demo can send email to a local Mailpit test inbox; that is not delivery to a resident's real mailbox. SMS and WhatsApp remain unavailable until their sending integrations and approved providers are in place.
- The report and status pages show a shareable install QR code. Resident installation requires publishing that sub-county instance at a public HTTPS address; the local XAMPP QR is for this computer only.
- The administrator dashboard supports report review and status updates. Only areas imported for the configured instance are offered in the resident form.

## Local configuration and data

- `php/config.local.php` and `database/areas.local.csv` are intentionally excluded from Git. Keep credentials and local deployment data out of commits.
- Composer dependencies are described by `composer.json` and `composer.lock`; `vendor/` is generated locally.
- The browser QR encoder is pinned in `package.json` and included locally under `js/vendor/`.
- `database/seed.sql` inserts only the standard report categories. There are no demo reports or hardcoded staff credentials.
- `SYSTEM_SCOPE_AND_WORKFLOW.txt` records the agreed product boundary and current operational limitations.
