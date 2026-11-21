# Resident Installation and Notifications

## Install and share the app

The report and status pages show a QR code for this installation. Scan it to
open the resident report page; supported mobile browsers can then install the
Progressive Web App or add it to the home screen. Residents can share the same
QR code or the page URL.

The QR code is generated locally from the page address. The current XAMPP
address is a developer-only demo: `localhost` on a resident's phone refers to
their own phone, not the computer running XAMPP. Before sharing with residents,
deploy the system at a public HTTPS address and set `appUrl` in the ignored
`php/config.local.php` file. Each sub-county installation must share its own
address and QR code.

On a supported phone browser, scan the QR code to open the report page, then
choose Install or Add to Home Screen from the browser. The browser does not
silently install an app from a QR scan.

The PWA caches only a generic offline page. It does not cache reports,
reference codes, contact information, or API responses. Submitting or checking
a report requires an internet connection.

## Email demo with Mailpit

Mailpit is a free, open-source SMTP testing tool. It captures messages in a
local inbox instead of sending them to residents' real email accounts.

1. Install Composer dependencies from the project folder:

   ```powershell
   composer install --no-interaction
   ```

2. On a new computer, download the Windows AMD64 Mailpit release from
   [Mailpit releases](https://github.com/axllent/mailpit/releases) and extract
   `mailpit.exe` to `C:\xampp\mailpit`. Start Mailpit on the XAMPP computer:

   ```powershell
   C:\xampp\mailpit\mailpit.exe --listen 127.0.0.1:8025 --smtp 127.0.0.1:1025 --disable-version-check
   ```

3. Open `http://127.0.0.1:8025` to view captured test messages.
4. Configure the local, Git-ignored `php/config.local.php` with:

   ```php
   'appUrl' => 'http://localhost/localinfra',
   'mailHost' => '127.0.0.1',
   'mailPort' => 1025,
   'mailUsername' => '',
   'mailPassword' => '',
   'mailEncryption' => 'none',
   'mailFromAddress' => 'noreply@localinfra.test',
   'mailFromName' => 'Local Infrastructure Issue Reporter',
   'mailTestMode' => true,
   ```

When email is selected, the application saves the report first, then sends a
reference-code email through the configured SMTP server. In Mailpit test mode,
the resident sees the code on the page and the email appears only in the
local test inbox. An SMTP error does not lose the saved report; the response
clearly tells the resident to keep the on-screen code.

## SMS and WhatsApp

SMS and WhatsApp are disabled in this demo until a sending integration is
implemented and an approved service is configured. The contact-method options
remain visible but unavailable, so the application will not collect a phone
number and imply that a message was sent.

Open-source SMS gateways such as Kannel provide gateway software, not free
mobile-network delivery. They still require a modem and SIM with airtime, or a
connection to a paid SMS gateway. There is no supported free, open-source
sender that can deliver messages directly to the WhatsApp network. For live
WhatsApp messages, use the official WhatsApp Business Platform and its
supported business setup, which may involve message charges. Unofficial
WhatsApp Web automation can put the office number and account at risk.

Do not put provider tokens or SMTP passwords in Git or paste them into chat.
Set credentials only in the server's ignored local configuration or protected
environment. Before opening the service to residents, use a public HTTPS URL,
verify consent and Kenyan phone formatting, and test the selected channel with
an office-owned address or number.

## References

- [Mailpit](https://mailpit.axllent.org/) local SMTP test inbox.
- [Kannel](https://www.kannel.org/) open-source SMS gateway software.
- [WhatsApp Business Platform](https://developers.facebook.com/documentation/business-messaging/whatsapp/about-the-platform/).
- [PWA installation requirements](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps/Guides/Making_PWAs_installable).
