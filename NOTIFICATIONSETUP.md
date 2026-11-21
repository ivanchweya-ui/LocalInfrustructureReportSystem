# Reference Code Notification Setup

The current system deliberately does not send resident notifications. A resident
receives the reference code in the browser immediately after submission and
must save it. Contact details are stored only when the resident voluntarily
provides them.

To add delivery later, use a provider-approved integration and keep the
provider credentials in the server environment or an excluded local
configuration file, never in Git.

## Email

Use an SMTP provider or an approved mail relay. Configure the provider host,
port, username, password, sender address, and TLS mode. The submitted message
must contain the reference code and a link to `status.html`.

## SMS

Use an SMS gateway with a Kenyan sender ID. Configure an API account SID or API
key and sender number. Only send to the number entered by the resident after
phone number validation.

## WhatsApp

Use an approved WhatsApp Business provider. The provider account must have a
template approved by Meta and a sender number. The template should contain the
reference code and status-check URL.

## Privacy and delivery checks

- Ask residents to opt in to a specific delivery method.
- Do not log full reference codes or contact details in application logs.
- Record delivery success and failure separately from resident-facing status.
- Test with your own number or test mailbox before a public launch.
- Display a clear message that delivery was sent or not sent; never claim
  delivery if the provider request was rejected.
