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
5. In PowerShell, set the initial system administrator credentials and run the installer. The password prompt below is masked:

   ```powershell
   $env:SYSTEM_ADMIN_USERNAME = 'systemadmin'
   $securePassword = Read-Host 'Initial system administrator password' -AsSecureString
   $env:SYSTEM_ADMIN_PASSWORD = [System.Net.NetworkCredential]::new('', $securePassword).Password
   $env:SYSTEM_ADMIN_NAME = 'System Administrator'
   php database\install.php
   Remove-Item Env:\SYSTEM_ADMIN_USERNAME,Env:\SYSTEM_ADMIN_PASSWORD,Env:\SYSTEM_ADMIN_NAME -ErrorAction SilentlyContinue
   ```

   Use a unique password of at least 16 characters. The installer creates missing tables and categories, records the configured sub-county, imports the verified areas, and creates the first approved system administrator. It does not drop tables, create sample reports, or create demo credentials.
6. Serve the project through the configured web server and open `report.html`. Verify that the displayed sub-county and area list are correct before sharing the URL.

`database/install.php` is CLI-only. `database/schema.sql` uses `CREATE TABLE IF NOT EXISTS`; the installer also adds the account-approval columns when upgrading the earlier schema. It does not clean or migrate existing reports or area records. Back up existing data and review it before reusing an old database; using a new database for a new sub-county is the safer deployment path.

The local presentation database is `localinfrasystem`, configured for Nairobi
County and Kasarani Sub-County. The application database user is
`localinfraapp`. The database and local configuration are excluded from Git.

## Staff access

- Residents do not create accounts. They submit a report and use its reference code to check status.
- A sub-county administrator can request an account through `signup.html`. Requests are stored as pending and cannot log in until approved.
- The initial system administrator is created during installation and can approve or reject sub-county administrator requests in the dashboard. This administrator belongs to that one installation; there is no shared cross-county admin service.
- Department officers, supervisors, work-order assignment, and internal department workflows are future extensions.

## Report and communication behavior

- Reference codes include the configured sub-county code and a cryptographically random token (for example `RF-2026-4704-A1B2C3D4E5F60718`); lookup searches only the current installation's database.
- Report photos are stored in `uploads/`; MySQL stores a relative path.
- Contact details are optional. No email, SMS, or WhatsApp provider is configured, so residents must save their reference code.
- The administrator dashboard supports report review and status updates. Only areas imported for the configured instance are offered in the resident form.

## Local configuration and data

- `php/config.local.php` and `database/areas.local.csv` are intentionally excluded from Git. Keep credentials and local deployment data out of commits.
- `database/seed.sql` inserts only the standard report categories. There are no demo reports or hardcoded staff credentials.
- `SYSTEM_SCOPE_AND_WORKFLOW.txt` records the agreed product boundary and current operational limitations.
