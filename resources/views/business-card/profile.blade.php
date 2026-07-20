@php
    /*
    |--------------------------------------------------------------------------
    | Language
    |--------------------------------------------------------------------------
    | SetLocale (the api middleware group) only accepts an EXACT `ar` or `en`
    | Accept-Language, which no browser ever sends — a phone set to Arabic sends
    | `ar-SA,ar;q=0.9,en;q=0.8`. Relying on app()->getLocale() alone would pin
    | every real visitor to English, so the header is re-read here with a prefix
    | match instead of loosening the shared middleware the whole JSON API rides
    | on. `?lang=` wins so a card link can be pinned to one language.
    */
    $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

    // Only the FIRST tag counts — browsers send them in preference order, and an
    // English speaker who merely lists Arabic as a fallback must not be flipped.
    $preferred = trim(explode(',', (string) request()->header('Accept-Language', ''))[0]);
    if ($lang !== 'ar' && preg_match('/^ar\b/i', $preferred)) {
        $lang = 'ar';
    }

    $forced = strtolower((string) request()->query('lang', ''));
    if ($forced === 'ar' || $forced === 'en') {
        $lang = $forced;
    }

    $isRtl = $lang === 'ar';

    /** Every visible string on this page goes through here. */
    $T = static fn (string $en, string $ar): string => $lang === 'ar' ? $ar : $en;

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    | effectiveTheme() is template defaults + the employee's overrides. The
    | values reach us from a user-editable field, so each one is re-validated
    | before it is interpolated into CSS — a colour must never be able to close
    | a declaration and open another.
    */
    $theme = $card->effectiveTheme();

    $hex = static function ($value, string $fallback): string {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{3,8}$/', $value) ? $value : $fallback;
    };

    $rgb = static function (string $hexColor): string {
        $h = ltrim($hexColor, '#');
        if (strlen($h) === 3) {
            $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        }
        $h = substr($h, 0, 6);

        return strlen($h) === 6
            ? hexdec(substr($h, 0, 2)) . ', ' . hexdec(substr($h, 2, 2)) . ', ' . hexdec(substr($h, 4, 2))
            : '34, 211, 238';
    };

    /** Perceived brightness 0..1, used to keep text legible on any chosen colour. */
    $luma = static function (string $hexColor) use ($rgb): float {
        [$r, $g, $b] = array_map('intval', explode(',', $rgb($hexColor)));

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    };

    $cPrimary = $hex($theme['primary'] ?? null, '#06B6D4');
    $cAccent  = $hex($theme['accent'] ?? null, '#22D3EE');
    $cBg      = $hex($theme['background'] ?? null, '#070D17');
    $cText    = $hex($theme['text'] ?? null, '#F2F7FD');

    // The page is a dark glass sheet — translucent light panels over a dark
    // base. A bright background would leave every panel unreadable, so a light
    // theme is dropped back to the house dark rather than shipping a card
    // nobody can read.
    if ($luma($cBg) > 0.45) {
        $cBg   = '#070D17';
        $cText = '#F2F7FD';
    }

    // Ink for the primary button, which sits on the accent gradient.
    $cOnAccent = $luma($cAccent) > 0.55 ? '#03242C' : '#FFFFFF';

    /*
    |--------------------------------------------------------------------------
    | Content
    |--------------------------------------------------------------------------
    */
    // photoUrl() already resolves the published snapshot, so it must be called
    // rather than reading the media collection — the live collection may hold a
    // replacement the owner has not approved yet.
    $cardPhoto    = $card->photoUrl();
    $employeeLogo = $employee->getFirstMediaUrl('employee_logo');
    $avatarUrl    = $cardPhoto ?: ($employeeLogo ?: null);

    $bio          = trim((string) ($card->bio ?? ''));
    $secondPhone  = trim((string) ($card->secondary_phone ?? ''));

    $companyLogo  = $employee->company?->getFirstMediaUrl('company_logo');
    $companyName  = $employee->company?->name;
    $initial      = mb_strtoupper(mb_substr(trim($employee->name ?? ''), 0, 1, 'UTF-8'), 'UTF-8') ?: '•';
    $hasDetails   = $employee->employee_number || $employee->department?->name || $employee->branch?->name
                 || $employee->phone || $secondPhone || $employee->email || $employee->user?->email;

    $slug         = (string) $card->public_url;
    $landingUrl   = 'https://idplus.cfd/';
    // host=card, path=/view/<slug>. The host is what the Android manifest
    // filters on; the PATH is all Flutter ever sees (both embedders forward
    // only Uri.path), so the app's route is '/view/:slug'. Changing the shape
    // of this URL means changing that route too.
    $schemeUrl    = 'idplus://card/view/' . rawurlencode($slug);

    // Chrome on Android resolves this itself: it launches the app when the
    // package is installed and navigates to the fallback when it is not, with
    // no timer and no error interstitial. Requires a genuine user gesture.
    $intentUrl    = 'intent://card/view/' . rawurlencode($slug)
                  . '#Intent;scheme=idplus;package=com.elgohary.id_by_mhawer'
                  . ';S.browser_fallback_url=' . rawurlencode($landingUrl) . ';end';

    $contactUrl   = url('/api/v1/cards/' . rawurlencode($slug) . '/contact');
    $privacyUrl   = url('/api/v1/privacy-policy?lang=' . $lang);
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="{{ $cBg }}">
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $employee->name }}{{ $employee->position ? ' — ' . $employee->position : '' }}">
    <meta property="og:description" content="{{ $companyName ?: $T('Digital business card — iD+ by Mhawer', 'بطاقة أعمال رقمية — iD+ من مهاور') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if($avatarUrl)
    <meta property="og:image" content="{{ $avatarUrl }}">
    @endif
    <title>{{ $employee->name }}{{ $employee->position ? ' — ' . $employee->position : '' }} | iD+ by Mhawer</title>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #070d17;
            --text: #f2f7fd;
            --primary: #06b6d4;
            --primary-rgb: 6, 182, 212;
            --accent: #22d3ee;
            --accent-rgb: 34, 211, 238;
            --on-accent: #03242c;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans Arabic', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* ---------- Ambient background ---------- */
        .ambient { position: fixed; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; }
        .ambient::before {
            content: ""; position: absolute; width: 620px; height: 620px;
            top: -260px; left: 50%; transform: translateX(-50%);
            background: radial-gradient(closest-side, rgba(var(--accent-rgb), .17), transparent 72%);
        }
        .ambient::after {
            content: ""; position: absolute; width: 520px; height: 520px;
            bottom: -240px; left: 50%; transform: translateX(-62%);
            background: radial-gradient(closest-side, rgba(var(--primary-rgb), .10), transparent 72%);
        }
        .grain {
            position: fixed; inset: 0; z-index: 0; pointer-events: none; opacity: .05;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }

        /* ---------- Layout ---------- */
        .page {
            position: relative; z-index: 1;
            min-height: 100vh; min-height: 100dvh;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: clamp(14px, 4vw, 44px) 16px calc(clamp(14px, 4vw, 44px) + env(safe-area-inset-bottom));
        }
        .card { width: 100%; max-width: 520px; display: flex; flex-direction: column; gap: 14px; }

        .panel {
            background: linear-gradient(165deg, rgba(151, 183, 255, .085), rgba(151, 183, 255, .028));
            border: 1px solid rgba(148, 184, 255, .13);
            border-radius: 26px;
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            box-shadow: 0 18px 48px rgba(2, 8, 20, .55);
            overflow: hidden;
        }

        /* ---------- Hero ---------- */
        .hero { text-align: center; padding: 0 22px 22px; }
        .hero-cover {
            height: 116px; margin-inline: -22px;
            background:
                radial-gradient(130% 170% at 50% -45%, rgba(var(--accent-rgb), .34), rgba(var(--accent-rgb), .05) 58%, transparent 78%),
                linear-gradient(180deg, rgba(var(--primary-rgb), .14), transparent);
            border-bottom: 1px solid rgba(148, 184, 255, .08);
        }
        .avatar-ring {
            width: 108px; height: 108px; border-radius: 50%; padding: 3px;
            margin: -54px auto 0;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            box-shadow: 0 10px 30px rgba(var(--primary-rgb), .35);
        }
        .avatar {
            width: 100%; height: 100%; border-radius: 50%; overflow: hidden;
            border: 3px solid #0b1523; background: #0e1a2c;
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .avatar-initial {
            width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
            font-size: 42px; font-weight: 700; color: var(--accent);
            background: linear-gradient(160deg, #0d2b3f, #0a1f30);
        }
        .name { margin-top: 14px; font-size: clamp(22px, 6vw, 27px); font-weight: 700; letter-spacing: -.02em; }
        .position { margin-top: 4px; color: #8fa3bf; font-size: 15px; font-weight: 500; }
        .bio {
            margin: 12px auto 0; max-width: 42ch;
            font-size: 14.5px; line-height: 1.65; color: #b9c9de;
            white-space: pre-line; overflow-wrap: anywhere;
        }
        .company-chip {
            display: inline-flex; align-items: center; gap: 8px; margin-top: 12px;
            padding: 7px 14px; border-radius: 999px;
            background: rgba(148, 184, 255, .08); border: 1px solid rgba(148, 184, 255, .15);
            font-size: 13.5px; font-weight: 600; color: #d7e6f7;
        }
        .company-chip img { width: 18px; height: 18px; border-radius: 5px; object-fit: cover; }
        .company-chip svg { width: 15px; height: 15px; color: var(--accent); flex: none; }

        /* ---------- Buttons ---------- */
        .actions { display: flex; flex-direction: column; gap: 10px; margin-top: 20px; }
        .share-row { display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; gap: 10px; }
        /* Wraps rather than squeezing — a card with two phone numbers puts three
           buttons in this row and they must stay tappable on a 320px screen. */
        .actions-row { display: flex; flex-wrap: wrap; gap: 10px; }
        .actions-row > .btn { flex: 1 1 132px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 9px;
            border: 0; border-radius: 14px; font-family: inherit; font-weight: 600;
            text-decoration: none; cursor: pointer;
            transition: transform .15s ease, box-shadow .2s ease, background .2s ease;
            -webkit-tap-highlight-color: transparent;
        }
        .btn:active { transform: scale(.98); }
        .btn:focus-visible { outline: 2px solid rgba(var(--accent-rgb), .65); outline-offset: 2px; }
        .btn svg { width: 18px; height: 18px; flex: none; }
        .btn[disabled] { opacity: .55; cursor: default; transform: none; }
        .btn-primary {
            min-height: 52px; font-size: 15.5px; color: var(--on-accent);
            background: linear-gradient(135deg, var(--primary), var(--accent));
            box-shadow: 0 10px 28px rgba(var(--accent-rgb), .28), inset 0 1px 0 rgba(255, 255, 255, .35);
        }
        .btn-primary:hover { box-shadow: 0 12px 34px rgba(var(--accent-rgb), .42), inset 0 1px 0 rgba(255, 255, 255, .35); }
        .btn-ghost {
            min-height: 48px; font-size: 14.5px; color: #e4eefb;
            background: rgba(148, 184, 255, .07); border: 1px solid rgba(148, 184, 255, .15);
        }
        .btn-ghost:hover { background: rgba(148, 184, 255, .13); }
        .btn-ghost svg { color: var(--accent); }
        .btn-block { width: 100%; }
        .link {
            display: inline-block; margin-top: 12px; font-size: 13.5px; font-weight: 600;
            color: var(--accent); text-decoration: none; border-bottom: 1px solid rgba(var(--accent-rgb), .4);
            padding-bottom: 1px;
        }
        [hidden] { display: none !important; }

        /* ---------- Sections ---------- */
        .section { padding: 20px 22px 22px; }
        .section-title {
            font-size: 11.5px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase;
            color: #6f84a4; margin-bottom: 12px;
            padding-bottom: 10px; border-bottom: 1px solid rgba(148, 184, 255, .1);
        }
        .section-lead { color: #9fb2cd; font-size: 14px; margin-bottom: 14px; }
        .section-note { color: #62779a; font-size: 12px; margin-top: 12px; }

        /* ---------- Detail rows ---------- */
        .rows { display: flex; flex-direction: column; }
        .row {
            display: flex; align-items: center; gap: 14px; padding: 11px 2px;
            text-decoration: none; color: inherit;
            border-top: 1px solid rgba(148, 184, 255, .07);
        }
        .row:first-child { border-top: 0; }
        .row-icon {
            width: 40px; height: 40px; flex: none; border-radius: 12px;
            background: rgba(var(--accent-rgb), .09); border: 1px solid rgba(var(--accent-rgb), .17);
            display: flex; align-items: center; justify-content: center; color: var(--accent);
        }
        .row-icon svg { width: 18px; height: 18px; }
        .row-body { min-width: 0; flex: 1; text-align: start; }
        .row-label { font-size: 11px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: #7488a8; }
        .row-value { margin-top: 1px; font-size: 15px; font-weight: 500; color: #edf4fc; overflow-wrap: anywhere; }
        a.row { transition: background .2s ease; border-radius: 10px; }
        a.row:hover .row-value { color: var(--accent); }

        /* ---------- QR / share ---------- */
        .qr-tile {
            width: min(224px, 68%); margin: 4px auto 18px;
            background: #ffffff; border-radius: 20px; padding: 14px;
            box-shadow: 0 14px 34px rgba(2, 8, 20, .5);
        }
        .qr-tile img { display: block; width: 100%; height: auto; border-radius: 8px; }
        .qr-hint { text-align: center; color: #8fa3bf; font-size: 13px; margin: -6px 0 16px; }

        /* ---------- Forms ---------- */
        .field { margin-top: 12px; }
        .field-grid { display: grid; gap: 12px; grid-template-columns: 1fr; }
        @media (min-width: 420px) { .field-grid { grid-template-columns: 1fr 1fr; } }
        .field-grid .field { margin-top: 0; }
        .label {
            display: block; margin-bottom: 6px;
            font-size: 11px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: #7488a8;
        }
        .label .opt { text-transform: none; letter-spacing: 0; font-weight: 500; color: #5b6f90; }
        .input {
            width: 100%; min-height: 46px; padding: 12px 14px;
            border-radius: 14px; font-family: inherit; font-size: 15px;
            color: var(--text); background: rgba(6, 12, 24, .5);
            border: 1px solid rgba(148, 184, 255, .16);
            text-align: start;
        }
        .input::placeholder { color: #5a6d8c; }
        .input:focus {
            outline: none; border-color: rgba(var(--accent-rgb), .55);
            box-shadow: 0 0 0 3px rgba(var(--accent-rgb), .15);
        }
        .input[aria-invalid="true"] { border-color: rgba(248, 113, 113, .55); }
        .input:disabled { opacity: .6; }
        textarea.input { min-height: 78px; resize: vertical; line-height: 1.5; }
        .field-err { margin-top: 6px; font-size: 12.5px; color: #fca5a5; }
        .form-err {
            margin-top: 14px; padding: 10px 12px; border-radius: 12px; font-size: 13.5px;
            color: #fecaca; background: rgba(248, 113, 113, .1); border: 1px solid rgba(248, 113, 113, .28);
        }
        .consent { display: flex; align-items: flex-start; gap: 10px; margin-top: 16px; }
        .consent input {
            width: 20px; height: 20px; flex: none; margin-top: 2px;
            accent-color: var(--accent); cursor: pointer;
        }
        .consent label { font-size: 12.5px; line-height: 1.55; color: #9fb2cd; }
        .consent a { color: var(--accent); }
        /* Off-screen rather than display:none — a field a bot's parser skips
           entirely is a honeypot that never catches anything. */
        .hp {
            position: absolute; width: 1px; height: 1px; overflow: hidden;
            clip: rect(0 0 0 0); clip-path: inset(50%); white-space: nowrap;
        }
        .spin {
            width: 16px; height: 16px; flex: none; border-radius: 50%;
            border: 2px solid rgba(0, 0, 0, .25); border-top-color: currentColor;
            animation: spin .7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .done {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 14px; border-radius: 16px;
            background: rgba(var(--accent-rgb), .08); border: 1px solid rgba(var(--accent-rgb), .2);
        }
        .done svg { width: 22px; height: 22px; flex: none; color: var(--accent); margin-top: 1px; }
        .done-title { font-weight: 600; font-size: 15px; }
        .done-sub { margin-top: 2px; font-size: 13px; color: #9fb2cd; }

        /* ---------- Footer ---------- */
        .foot { text-align: center; color: #54688a; font-size: 12.5px; padding: 12px 0 2px; }
        .foot .mark {
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }

        /* ---------- Motion ---------- */
        @media (prefers-reduced-motion: no-preference) {
            .panel, .foot { animation: rise .55s cubic-bezier(.22, .9, .3, 1) both; }
            .card > *:nth-child(2) { animation-delay: .07s; }
            .card > *:nth-child(3) { animation-delay: .14s; }
            .card > *:nth-child(4) { animation-delay: .21s; }
            @keyframes rise {
                from { opacity: 0; transform: translateY(14px); }
                to   { opacity: 1; transform: none; }
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .spin { animation: none; }
        }

        @media (max-width: 359px) {
            .section, .hero { padding-inline: 16px; }
            .hero-cover { margin-inline: -16px; }
            .panel { border-radius: 20px; }
        }
    </style>
</head>
<body style="--bg: {{ $cBg }}; --text: {{ $cText }}; --primary: {{ $cPrimary }}; --primary-rgb: {{ $rgb($cPrimary) }}; --accent: {{ $cAccent }}; --accent-rgb: {{ $rgb($cAccent) }}; --on-accent: {{ $cOnAccent }};">
<div class="ambient" aria-hidden="true"></div>
<div class="grain" aria-hidden="true"></div>

<main class="page">
    <div class="card">

        {{-- ============ Hero ============ --}}
        <section class="panel hero">
            <div class="hero-cover" aria-hidden="true"></div>
            <div class="avatar-ring">
                <div class="avatar">
                    @if($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="{{ $employee->name }}">
                    @else
                        <div class="avatar-initial" aria-hidden="true">{{ $initial }}</div>
                    @endif
                </div>
            </div>
            <h1 class="name" dir="auto">{{ $employee->name }}</h1>
            @if($employee->position)
                <p class="position" dir="auto">{{ $employee->position }}</p>
            @endif
            @if($bio !== '')
                <p class="bio" dir="auto">{{ $bio }}</p>
            @endif
            @if($companyName)
                <span class="company-chip" dir="auto">
                    @if($companyLogo)
                        <img src="{{ $companyLogo }}" alt="">
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/></svg>
                    @endif
                    {{ $companyName }}
                </span>
            @endif

            <div class="actions">
                <a class="btn btn-primary" href="{{ route('business-card.vcard', $card->id) }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
                    {{ $T('Save Contact', 'حفظ جهة الاتصال') }}
                </a>
                @if($employee->phone || $secondPhone !== '' || $employee->email)
                    <div class="actions-row">
                        @if($employee->phone)
                            <a class="btn btn-ghost" href="tel:{{ $employee->phone }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                {{ $T('Call', 'اتصال') }}
                            </a>
                        @endif
                        @if($secondPhone !== '')
                            <a class="btn btn-ghost" href="tel:{{ $secondPhone }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M11 18h2"/></svg>
                                {{ $T('Second phone', 'هاتف إضافي') }}
                            </a>
                        @endif
                        @if($employee->email)
                            <a class="btn btn-ghost" href="mailto:{{ $employee->email }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                {{ $T('Email', 'بريد إلكتروني') }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        {{-- ============ Details ============ --}}
        @if($hasDetails)
            <section class="panel section">
                <h2 class="section-title">{{ $T('Details', 'التفاصيل') }}</h2>
                <div class="rows">
                    @if($employee->employee_number)
                        <div class="row">
                            <span class="row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M15 8h2M15 12h2M7 16h10"/></svg></span>
                            <span class="row-body">
                                <span class="row-label">{{ $T('Employee No', 'الرقم الوظيفي') }}</span>
                                <span class="row-value" style="display:block" dir="auto">{{ $employee->employee_number }}</span>
                            </span>
                        </div>
                    @endif
                    @if($employee->department?->name)
                        <div class="row">
                            <span class="row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 12.18-8.58 3.91a2 2 0 0 1-1.66 0L3.18 12.18"/><path d="m22 17.18-8.58 3.91a2 2 0 0 1-1.66 0L3.18 17.18"/></svg></span>
                            <span class="row-body">
                                <span class="row-label">{{ $T('Department', 'القسم') }}</span>
                                <span class="row-value" style="display:block" dir="auto">{{ $employee->department->name }}</span>
                            </span>
                        </div>
                    @endif
                    @if($employee->branch?->name)
                        <div class="row">
                            <span class="row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></span>
                            <span class="row-body">
                                <span class="row-label">{{ $T('Branch', 'الفرع') }}</span>
                                <span class="row-value" style="display:block" dir="auto">{{ $employee->branch->name }}</span>
                            </span>
                        </div>
                    @endif
                    @if($employee->phone)
                        <a class="row" href="tel:{{ $employee->phone }}">
                            <span class="row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
                            <span class="row-body">
                                <span class="row-label">{{ $T('Phone', 'الهاتف') }}</span>
                                <span class="row-value" style="display:block" dir="ltr">{{ $employee->phone }}</span>
                            </span>
                        </a>
                    @endif
                    @if($secondPhone !== '')
                        <a class="row" href="tel:{{ $secondPhone }}">
                            <span class="row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M11 18h2"/></svg></span>
                            <span class="row-body">
                                <span class="row-label">{{ $T('Second phone', 'هاتف إضافي') }}</span>
                                <span class="row-value" style="display:block" dir="ltr">{{ $secondPhone }}</span>
                            </span>
                        </a>
                    @endif
                    @if($employee->email)
                        <a class="row" href="mailto:{{ $employee->email }}">
                            <span class="row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></span>
                            <span class="row-body">
                                <span class="row-label">{{ $T('Work Email', 'البريد الإلكتروني للعمل') }}</span>
                                <span class="row-value" style="display:block" dir="ltr">{{ $employee->email }}</span>
                            </span>
                        </a>
                    @endif
                    @if($employee->user?->email)
                        <a class="row" href="mailto:{{ $employee->user->email }}">
                            <span class="row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"/></svg></span>
                            <span class="row-body">
                                <span class="row-label">{{ $T('Personal Email', 'البريد الإلكتروني الشخصي') }}</span>
                                <span class="row-value" style="display:block" dir="ltr">{{ $employee->user->email }}</span>
                            </span>
                        </a>
                    @endif
                </div>
            </section>
        @endif

        {{-- ============ Share your details back ============ --}}
        <section class="panel section" id="cbPanel">
            <h2 class="section-title">{{ $T('Share your details back', 'شارك بياناتك معه') }}</h2>
            {{-- <bdi> keeps a Latin name from being torn apart by the surrounding
                 Arabic run (and vice versa) when the two are mixed in one sentence. --}}
            <p class="section-lead" dir="auto">{{ $T('Send your contact details straight to ', 'أرسل بيانات التواصل الخاصة بك مباشرة إلى ') }}<bdi>{{ $employee->name }}</bdi>.</p>

            <button type="button" class="btn btn-ghost btn-block" id="cbToggle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
                {{ $T('Share my details', 'مشاركة بياناتي') }}
            </button>

            <form id="cbForm" novalidate hidden>
                <div class="field-grid">
                    <div class="field">
                        <label class="label" for="cb_first">{{ $T('First name', 'الاسم الأول') }}</label>
                        <input class="input" id="cb_first" name="first_name" type="text" maxlength="80" autocomplete="given-name" dir="auto" required>
                        <p class="field-err" id="cb_err_first_name" hidden></p>
                    </div>
                    <div class="field">
                        <label class="label" for="cb_last">{{ $T('Last name', 'اسم العائلة') }}</label>
                        <input class="input" id="cb_last" name="last_name" type="text" maxlength="80" autocomplete="family-name" dir="auto" required>
                        <p class="field-err" id="cb_err_last_name" hidden></p>
                    </div>
                </div>
                <div class="field">
                    <label class="label" for="cb_email">{{ $T('Email', 'البريد الإلكتروني') }}</label>
                    <input class="input" id="cb_email" name="email" type="email" maxlength="190" autocomplete="email" dir="ltr" required>
                    <p class="field-err" id="cb_err_email" hidden></p>
                </div>
                <div class="field">
                    <label class="label" for="cb_phone">{{ $T('Phone', 'رقم الهاتف') }} <span class="opt">— {{ $T('optional', 'اختياري') }}</span></label>
                    <input class="input" id="cb_phone" name="phone" type="tel" maxlength="30" autocomplete="tel" dir="ltr">
                    <p class="field-err" id="cb_err_phone" hidden></p>
                </div>
                <div class="field">
                    <label class="label" for="cb_note">{{ $T('Note', 'ملاحظة') }} <span class="opt">— {{ $T('optional', 'اختياري') }}</span></label>
                    <textarea class="input" id="cb_note" name="note" maxlength="280" dir="auto" placeholder="{{ $T('Where you met, what to follow up on…', 'أين التقيتما، وما الذي تودّ متابعته…') }}"></textarea>
                    <p class="field-err" id="cb_err_note" hidden></p>
                </div>

                <div class="hp" aria-hidden="true">
                    <label for="cb_website">{{ $T('Website', 'الموقع الإلكتروني') }}</label>
                    <input id="cb_website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>

                <div class="consent">
                    <input type="checkbox" id="cb_consent" name="consent">
                    <label for="cb_consent" dir="auto">{{ $T('I agree to send my name, email address, phone number and note to ', 'أوافق على إرسال اسمي وبريدي الإلكتروني ورقم هاتفي وملاحظتي إلى ') }}<bdi>{{ $employee->name }}</bdi>@if($companyName){{ $T(' at ', ' في ') }}<bdi>{{ $companyName }}</bdi>@endif{{ $T(' so they can contact me. ', ' ليتمكن من التواصل معي. ') }}<a href="{{ $privacyUrl }}" target="_blank" rel="noopener noreferrer">{{ $T('Privacy policy', 'سياسة الخصوصية') }}</a></label>
                </div>

                <p class="form-err" id="cbError" role="alert" hidden></p>

                <div class="field">
                    <button type="submit" class="btn btn-primary btn-block" id="cbSubmit" disabled>
                        <span class="spin" id="cbSpin" hidden></span>
                        <span id="cbSubmitLabel">{{ $T('Send my details', 'إرسال بياناتي') }}</span>
                    </button>
                </div>
            </form>

            <div id="cbDone" hidden>
                <div class="done">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    <span>
                        <span class="done-title" id="cbDoneTitle" dir="auto"></span>
                        <span class="done-sub" style="display:block" dir="auto">{{ $T('They will see your details in the iD+ app.', 'ستظهر بياناتك لديه في تطبيق iD+.') }}</span>
                    </span>
                </div>
                <a href="#" class="link" id="cbAgain">{{ $T('Send different details', 'إرسال بيانات مختلفة') }}</a>
            </div>
        </section>

        {{-- ============ Open in the app ============ --}}
        <section class="panel section">
            <h2 class="section-title">{{ $T('Open in the iD+ app', 'افتح في تطبيق iD+') }}</h2>
            <p class="section-lead">{{ $T('Keep this card and manage your own from the iD+ app.', 'احتفظ بهذه البطاقة وأدِر بطاقتك من تطبيق iD+.') }}</p>

            {{-- Default-visible so a visitor with JS disabled still lands somewhere real. --}}
            <div id="appGeneric">
                <a class="btn btn-ghost btn-block" href="{{ $landingUrl }}" target="_blank" rel="noopener noreferrer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    {{ $T('Learn more about iD+', 'تعرّف أكثر على iD+') }}
                </a>
            </div>

            <div id="appAndroid" hidden>
                <a class="btn btn-primary btn-block" href="{{ $intentUrl }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="3"/><path d="M11 18h2"/></svg>
                    {{ $T('Open in the iD+ app', 'الفتح في تطبيق iD+') }}
                </a>
            </div>

            {{-- iOS gives a page no way to test whether a scheme resolved, and the
                 old iframe+timeout probe now raises a visible error alert, so the
                 choice is handed to the visitor instead. --}}
            <div id="appIos" hidden>
                <a class="btn btn-primary btn-block" href="{{ $landingUrl }}" target="_blank" rel="noopener noreferrer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M4 21h16"/></svg>
                    {{ $T('Get the iD+ app', 'احصل على تطبيق iD+') }}
                </a>
                <a class="link" href="{{ $schemeUrl }}">{{ $T('Already have the app? Open it', 'لديك التطبيق بالفعل؟ افتحه') }}</a>
            </div>

            <p class="section-note">{{ $T('Coming soon to the App Store and Google Play.', 'قريبًا على App Store و Google Play.') }}</p>
        </section>

        {{-- ============ Share ============ --}}
        <section class="panel section">
            <h2 class="section-title">{{ $T('Share this card', 'مشاركة هذه البطاقة') }}</h2>
            @if($card->qr_code)
                <div class="qr-tile">
                    <img src="{{ Storage::disk('public')->url($card->qr_code) }}" alt="{{ $T('QR code for this business card', 'رمز QR لهذه البطاقة') }}">
                </div>
                <p class="qr-hint">{{ $T('Scan the code or share the link below', 'امسح الرمز أو شارك الرابط بالأسفل') }}</p>
            @endif
            <div class="share-row">
                <button type="button" class="btn btn-ghost" id="copyBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    <span id="copyLabel" aria-live="polite">{{ $T('Copy link', 'نسخ الرابط') }}</span>
                </button>
                <button type="button" class="btn btn-ghost" id="shareBtn" hidden>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.59 13.51 6.83 3.98"/><path d="m15.41 6.51-6.82 3.98"/></svg>
                    {{ $T('Share', 'مشاركة') }}
                </button>
            </div>
        </section>

        <footer class="foot">
            <span class="mark">iD+</span> {{ $T('by Mhawer — Digital Business Card', 'من مهاور — بطاقة أعمال رقمية') }}
        </footer>
    </div>
</main>

<script>
    (function () {
        'use strict';

        var slug = @js($slug);

        /* ---- How the visitor got here ----
           QR images and NFC payloads carry ?src=qr / ?src=nfc, so the dashboard's
           scan count and source mix reflect reality instead of reporting every
           visit as a link. Shared by view tracking and the contact exchange. */
        var source = 'LINK';
        try {
            var raw = (new URLSearchParams(window.location.search).get('src') || '').toUpperCase();
            if (raw === 'QR' || raw === 'NFC') { source = raw; }
        } catch (e) { /* no-op */ }

        /* ---- Fire-and-forget view tracking (must never break the page) ---- */
        try {
            var endpoint = new URL('../cards/' + encodeURIComponent(slug) + '/track', window.location.href);

            fetch(endpoint.toString(), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ interaction_type: 'view', source: source }),
                keepalive: true
            }).catch(function () {});
        } catch (e) { /* no-op */ }

        /* ---- Copy link ---- */
        var copyBtn = document.getElementById('copyBtn');
        var copyLabel = document.getElementById('copyLabel');
        var copyIdle = @js($T('Copy link', 'نسخ الرابط'));
        var copyDone = @js($T('Copied', 'تم النسخ'));
        var copyTimer = null;

        function showCopied() {
            if (!copyLabel) return;
            copyLabel.textContent = copyDone;
            if (copyTimer) clearTimeout(copyTimer);
            copyTimer = setTimeout(function () { copyLabel.textContent = copyIdle; }, 2000);
        }

        function legacyCopy(text) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try {
                if (document.execCommand('copy')) showCopied();
            } catch (e) { /* no-op */ }
            document.body.removeChild(ta);
        }

        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                var url = window.location.href;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(showCopied).catch(function () { legacyCopy(url); });
                } else {
                    legacyCopy(url);
                }
            });
        }

        /* ---- Web Share API (shown only when supported) ---- */
        var shareBtn = document.getElementById('shareBtn');
        if (shareBtn && navigator.share) {
            shareBtn.hidden = false;
            shareBtn.addEventListener('click', function () {
                navigator.share({
                    title: @js($employee->name),
                    url: window.location.href
                }).catch(function () {});
            });
        }

        /* ---- Open in the app ----
           Platform picked from the UA because the two stores behave differently:
           Chrome/Android resolves the intent: URL itself, while iOS has no
           try-then-fall-back at all. */
        try {
            var ua = navigator.userAgent || '';
            var isAndroid = /Android/i.test(ua);
            // iPadOS 13+ reports itself as a Mac; the touch-point count is what
            // separates a real desktop Safari from an iPad.
            var isIos = /iPad|iPhone|iPod/i.test(ua)
                || (/Macintosh/i.test(ua) && navigator.maxTouchPoints > 1);

            var generic = document.getElementById('appGeneric');
            var target = isAndroid ? document.getElementById('appAndroid')
                       : (isIos ? document.getElementById('appIos') : null);

            if (target && generic) {
                target.hidden = false;
                generic.hidden = true;
            }
        } catch (e) { /* no-op — the generic link stays visible */ }

        /* ---- Share your details back ---- */
        (function contactBack() {
            var form = document.getElementById('cbForm');
            var toggle = document.getElementById('cbToggle');
            var done = document.getElementById('cbDone');
            var doneTitle = document.getElementById('cbDoneTitle');
            var again = document.getElementById('cbAgain');
            var submit = document.getElementById('cbSubmit');
            var submitLabel = document.getElementById('cbSubmitLabel');
            var spin = document.getElementById('cbSpin');
            var errStrip = document.getElementById('cbError');

            if (!form || !toggle || !done || !submit) return;

            var endpoint = @js($contactUrl);
            var storeKey = 'idplus.contact-shared.' + slug;
            var fields = ['first_name', 'last_name', 'email', 'phone', 'note'];
            var required = ['first_name', 'last_name', 'email'];
            var consent = document.getElementById('cb_consent');
            var inFlight = false;

            var TXT = {
                idle:      @js($T('Send my details', 'إرسال بياناتي')),
                sending:   @js($T('Sending…', 'جارٍ الإرسال…')),
                sent:      @js($T('Sent to ', 'تم الإرسال إلى ')),
                updated:   @js($T('Details updated for ', 'تم تحديث بياناتك لدى ')),
                generic:   @js($T('Something went wrong. Please try again.', 'حدث خطأ ما. يرجى المحاولة مرة أخرى.')),
                throttled: @js($T('Too many attempts. Please try again in a minute.', 'محاولات كثيرة. يرجى المحاولة بعد دقيقة.')),
                gone:      @js($T('This card is no longer available.', 'لم تعد هذه البطاقة متاحة.')),
                invalid:   @js($T('Please check the highlighted fields.', 'يرجى مراجعة الحقول المحددة.'))
            };

            function el(name) { return form.elements[name]; }

            function clearErrors() {
                errStrip.hidden = true;
                errStrip.textContent = '';
                fields.forEach(function (name) {
                    var box = document.getElementById('cb_err_' + name);
                    if (box) { box.hidden = true; box.textContent = ''; }
                    var input = el(name);
                    if (input) input.removeAttribute('aria-invalid');
                });
            }

            function showFieldErrors(errors) {
                Object.keys(errors || {}).forEach(function (name) {
                    var box = document.getElementById('cb_err_' + name);
                    var msg = [].concat(errors[name])[0];
                    if (!box || !msg) return;
                    box.textContent = msg;
                    box.hidden = false;
                    var input = el(name);
                    if (input) input.setAttribute('aria-invalid', 'true');
                });
            }

            function showStrip(message) {
                errStrip.textContent = message;
                errStrip.hidden = false;
            }

            function refreshSubmit() {
                var ok = consent && consent.checked && required.every(function (name) {
                    var input = el(name);
                    return input && input.value.trim() !== '';
                });
                submit.disabled = inFlight || !ok;
            }

            function busy(on) {
                inFlight = on;
                spin.hidden = !on;
                submitLabel.textContent = on ? TXT.sending : TXT.idle;
                fields.concat(['consent']).forEach(function (name) {
                    var input = el(name);
                    if (input) input.disabled = on;
                });
                refreshSubmit();
            }

            function showDone(name, alreadyShared) {
                doneTitle.textContent = (alreadyShared ? TXT.updated : TXT.sent) + (name || '');
                form.hidden = true;
                toggle.hidden = true;
                done.hidden = false;
            }

            // A returning visitor should not be asked again on every scan.
            try {
                var saved = window.localStorage.getItem(storeKey);
                if (saved) { showDone(saved === '1' ? '' : saved, true); }
            } catch (e) { /* private mode — fall through to the normal form */ }

            toggle.addEventListener('click', function () {
                toggle.hidden = true;
                form.hidden = false;
                var first = el('first_name');
                if (first) first.focus();
            });

            again.addEventListener('click', function (e) {
                e.preventDefault();
                try { window.localStorage.removeItem(storeKey); } catch (err) { /* no-op */ }
                form.reset();
                clearErrors();
                refreshSubmit();
                done.hidden = true;
                form.hidden = false;
            });

            form.addEventListener('input', refreshSubmit);
            form.addEventListener('change', refreshSubmit);

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (inFlight) return;

                clearErrors();
                busy(true);

                var payload = {
                    first_name: el('first_name').value.trim(),
                    last_name: el('last_name').value.trim(),
                    email: el('email').value.trim(),
                    phone: el('phone').value.trim(),
                    note: el('note').value.trim(),
                    source: source,
                    consent: true,
                    website: el('website').value
                };

                var status = 0;

                fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                }).then(function (res) {
                    status = res.status;
                    return res.json().catch(function () { return {}; });
                }).then(function (body) {
                    if (status === 200 && body && body.success !== false) {
                        var data = body.data || {};
                        var name = data.employee_name || '';
                        try { window.localStorage.setItem(storeKey, name || '1'); } catch (err) { /* no-op */ }
                        busy(false);
                        showDone(name, !!data.already_shared);
                        return;
                    }

                    busy(false);

                    if (status === 422) {
                        showFieldErrors(body && body.errors);
                        showStrip((body && body.message) || TXT.invalid);
                    } else if (status === 429) {
                        showStrip(TXT.throttled);
                    } else if (status === 404) {
                        showStrip(TXT.gone);
                    } else {
                        showStrip(TXT.generic);
                    }
                }).catch(function () {
                    busy(false);
                    showStrip(TXT.generic);
                });
            });

            refreshSubmit();
        })();
    })();
</script>
</body>
</html>
