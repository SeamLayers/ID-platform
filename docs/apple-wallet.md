# Add to Apple Wallet

A card can be added to Apple Wallet as a signed `.pkpass`. The code is complete
and tested; what it needs from you is two certificate files that only the Apple
Developer account holder can produce.

Until they are installed, the feature is *off and silent*: the API returns
`wallet_pass_url: null`, the mobile app and the public card page hide the
button, and `GET /api/v1/cards/{public_url}/wallet.pkpass` answers `503`.
Nothing half-works and no invalid pass ever reaches a phone.

## 1. Create the Pass Type ID

developer.apple.com → Certificates, Identifiers & Profiles → **Identifiers** →
**+** → *Pass Type IDs*. Use something like `pass.cfd.idplus.card`.

## 2. Export the certificate

With the Pass Type ID selected, create a certificate for it, download the
`.cer`, and double-click to install it in **Keychain Access**. Then find it
under *My Certificates*, right-click → **Export**, and save as
`certificate.p12` with a password.

The private key must be exported with it — that is what "My Certificates"
(rather than "Certificates") guarantees.

## 3. Download Apple's WWDR intermediate

From <https://www.apple.com/certificateauthority/> take the current *Worldwide
Developer Relations* intermediate certificate (G4 at the time of writing) and
convert it to PEM:

```bash
openssl x509 -inform DER -in AppleWWDRCAG4.cer -out wwdr.pem
```

Without this the phone cannot chain the signature to Apple's root and rejects
the pass.

## 4. Install on the server

Put both files somewhere **outside the web root**:

```bash
mkdir -p storage/app/wallet
# upload certificate.p12 and wwdr.pem into it
chmod 600 storage/app/wallet/*
```

Then set in `.env`:

```
APPLE_WALLET_PASS_TYPE_ID=pass.cfd.idplus.card
APPLE_WALLET_TEAM_ID=SX3NG263VK
APPLE_WALLET_ORG_NAME="iD+ by Mhawer"
APPLE_WALLET_CERTIFICATE_PATH=/full/path/storage/app/wallet/certificate.p12
APPLE_WALLET_CERTIFICATE_PASSWORD=the-export-password
APPLE_WALLET_WWDR_PATH=/full/path/storage/app/wallet/wwdr.pem
```

and clear the config cache:

```bash
php artisan config:clear
```

`APPLE_WALLET_TEAM_ID` must be the team the Pass Type ID belongs to. A mismatch
between the team in `pass.json` and the team the certificate was issued under is
the most common reason a correctly-signed pass is still refused.

## 5. Check it

```bash
curl -sI https://<host>/api/v1/cards/<public_url>/wallet.pkpass
```

`200` with `Content-Type: application/vnd.apple.pkpass` means it works — open
the same URL in Safari on an iPhone and Wallet offers to add it. A `503` means
the certificates are not readable or the password is wrong; the real reason is
in `storage/logs/laravel.log` (it is deliberately not in the HTTP response).

## What the pass contains

A `generic` pass — the Wallet type for membership and ID cards:

| Where | Field |
|---|---|
| Front, primary | Name |
| Front, secondary | Position, Company |
| Front, auxiliary | Phone, Department |
| Back | Email, phone, second phone, bio, card link |
| Barcode | QR of the public card URL — the same payload the printed QR carries |

Colours come from the card's `effective_theme` (template theme + the employee's
overrides), falling back to the `APPLE_WALLET_*` colours in `.env`.

The serial number is the card's `public_url`, so adding the same card twice
replaces the pass in Wallet instead of stacking a duplicate.

## Passes do not auto-update

There is no `webServiceURL` in the pass, so a pass already in someone's Wallet
does not change when the employee edits their card. The QR inside it still
resolves to the live page, so the *content* behind it stays current — only the
printed fields go stale. Adding push updates means standing up the Wallet web
service (registration, device tokens, an APNs certificate for the pass type);
worth doing only if the client asks for it.
