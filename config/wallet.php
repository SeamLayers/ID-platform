<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Apple Wallet passes
    |--------------------------------------------------------------------------
    |
    | "Add to Apple Wallet" hands the phone a `.pkpass` bundle: a zip of the
    | pass description, its images, a manifest of SHA-1 digests, and a PKCS#7
    | signature over that manifest. iOS refuses any pass whose signature does
    | not chain to Apple's WWDR certificate under a Pass Type ID that belongs
    | to the team named inside the pass — so the feature cannot work on
    | credentials alone, it needs two files that only the Apple Developer
    | account holder can produce:
    |
    |   1. certificate.p12 — the Pass Type ID certificate and its private key,
    |      exported from Keychain Access after creating a Pass Type ID at
    |      developer.apple.com → Certificates, Identifiers & Profiles →
    |      Identifiers → Pass Type IDs.
    |   2. wwdr.pem — Apple Worldwide Developer Relations intermediate
    |      certificate (public download from apple.com/certificateauthority,
    |      converted with: openssl x509 -inform DER -in AppleWWDRCAG4.cer
    |      -out wwdr.pem).
    |
    | Drop both outside the web root (storage/app/wallet is the default) and
    | fill in the identifiers below. Until then `AppleWalletService::isConfigured()`
    | is false: the pass endpoint answers 503, the API stops advertising a
    | wallet URL, and the app hides the button — nothing half-works and no
    | invalid pass is ever handed to a phone.
    |
    */

    'apple' => [

        // e.g. pass.cfd.idplus.card — must match the Pass Type ID the
        // certificate was issued for, exactly.
        'pass_type_identifier' => env('APPLE_WALLET_PASS_TYPE_ID'),

        // The 10-character Apple Developer Team ID.
        'team_identifier' => env('APPLE_WALLET_TEAM_ID'),

        // Shown by iOS under the pass; also used in the pass description.
        'organization_name' => env('APPLE_WALLET_ORG_NAME', 'iD+ by Mhawer'),

        'certificate_path' => env(
            'APPLE_WALLET_CERTIFICATE_PATH',
            storage_path('app/wallet/certificate.p12')
        ),

        'certificate_password' => env('APPLE_WALLET_CERTIFICATE_PASSWORD', ''),

        'wwdr_path' => env(
            'APPLE_WALLET_WWDR_PATH',
            storage_path('app/wallet/wwdr.pem')
        ),

        // Default pass colours. A card whose template defines its own theme
        // overrides these per pass, so this is only the fallback.
        'background_color' => env('APPLE_WALLET_BACKGROUND', 'rgb(10,10,15)'),
        'foreground_color' => env('APPLE_WALLET_FOREGROUND', 'rgb(255,255,255)'),
        'label_color' => env('APPLE_WALLET_LABEL', 'rgb(0,188,212)'),

        // Images bundled into every pass. icon.png is mandatory — a pass
        // without one is rejected by iOS with no explanation.
        'assets_path' => resource_path('wallet'),
    ],

];
