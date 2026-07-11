@php
    $avatarUrl    = $employee->getFirstMediaUrl('employee_logo');
    $companyLogo  = $employee->company?->getFirstMediaUrl('company_logo');
    $companyName  = $employee->company?->name;
    $initial      = mb_strtoupper(mb_substr(trim($employee->name ?? ''), 0, 1, 'UTF-8'), 'UTF-8') ?: '•';
    $hasDetails   = $employee->employee_number || $employee->department?->name || $employee->branch?->name
                 || $employee->phone || $employee->email || $employee->user?->email;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0b1220">
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $employee->name }}{{ $employee->position ? ' — ' . $employee->position : '' }}">
    <meta property="og:description" content="{{ $companyName ?: 'Digital business card — iD+ by Mhawer' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if($avatarUrl)
    <meta property="og:image" content="{{ $avatarUrl }}">
    @endif
    <title>{{ $employee->name }}{{ $employee->position ? ' — ' . $employee->position : '' }} | iD+ by Mhawer</title>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans Arabic', sans-serif;
            background: #070d17;
            color: #f2f7fd;
            line-height: 1.5;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* ---------- Ambient background ---------- */
        .ambient { position: fixed; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; }
        .ambient::before {
            content: ""; position: absolute; width: 620px; height: 620px;
            top: -260px; left: 50%; transform: translateX(-50%);
            background: radial-gradient(closest-side, rgba(34, 211, 238, .17), transparent 72%);
        }
        .ambient::after {
            content: ""; position: absolute; width: 520px; height: 520px;
            bottom: -240px; left: 50%; transform: translateX(-62%);
            background: radial-gradient(closest-side, rgba(16, 224, 197, .10), transparent 72%);
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
                radial-gradient(130% 170% at 50% -45%, rgba(34, 211, 238, .34), rgba(34, 211, 238, .05) 58%, transparent 78%),
                linear-gradient(180deg, rgba(6, 182, 212, .14), transparent);
            border-bottom: 1px solid rgba(148, 184, 255, .08);
        }
        .avatar-ring {
            width: 108px; height: 108px; border-radius: 50%; padding: 3px;
            margin: -54px auto 0;
            background: linear-gradient(135deg, #06b6d4, #22d3ee 52%, #10e0c5);
            box-shadow: 0 10px 30px rgba(6, 182, 212, .35);
        }
        .avatar {
            width: 100%; height: 100%; border-radius: 50%; overflow: hidden;
            border: 3px solid #0b1523; background: #0e1a2c;
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .avatar-initial {
            width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
            font-size: 42px; font-weight: 700; color: #67e8f9;
            background: linear-gradient(160deg, #0d2b3f, #0a1f30);
        }
        .name { margin-top: 14px; font-size: clamp(22px, 6vw, 27px); font-weight: 700; letter-spacing: -.02em; }
        .position { margin-top: 4px; color: #8fa3bf; font-size: 15px; font-weight: 500; }
        .company-chip {
            display: inline-flex; align-items: center; gap: 8px; margin-top: 12px;
            padding: 7px 14px; border-radius: 999px;
            background: rgba(148, 184, 255, .08); border: 1px solid rgba(148, 184, 255, .15);
            font-size: 13.5px; font-weight: 600; color: #d7e6f7;
        }
        .company-chip img { width: 18px; height: 18px; border-radius: 5px; object-fit: cover; }
        .company-chip svg { width: 15px; height: 15px; color: #22d3ee; flex: none; }

        /* ---------- Buttons ---------- */
        .actions { display: flex; flex-direction: column; gap: 10px; margin-top: 20px; }
        .actions-row, .share-row { display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; gap: 10px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 9px;
            border: 0; border-radius: 14px; font-family: inherit; font-weight: 600;
            text-decoration: none; cursor: pointer;
            transition: transform .15s ease, box-shadow .2s ease, background .2s ease;
            -webkit-tap-highlight-color: transparent;
        }
        .btn:active { transform: scale(.98); }
        .btn:focus-visible { outline: 2px solid rgba(34, 211, 238, .65); outline-offset: 2px; }
        .btn svg { width: 18px; height: 18px; flex: none; }
        .btn-primary {
            min-height: 52px; font-size: 15.5px; color: #03242c;
            background: linear-gradient(135deg, #06b6d4, #22d3ee 52%, #10e0c5);
            box-shadow: 0 10px 28px rgba(34, 211, 238, .28), inset 0 1px 0 rgba(255, 255, 255, .35);
        }
        .btn-primary:hover { box-shadow: 0 12px 34px rgba(34, 211, 238, .42), inset 0 1px 0 rgba(255, 255, 255, .35); }
        .btn-ghost {
            min-height: 48px; font-size: 14.5px; color: #e4eefb;
            background: rgba(148, 184, 255, .07); border: 1px solid rgba(148, 184, 255, .15);
        }
        .btn-ghost:hover { background: rgba(148, 184, 255, .13); }
        .btn-ghost svg { color: #39d6ee; }
        [hidden] { display: none !important; }

        /* ---------- Sections ---------- */
        .section { padding: 20px 22px 22px; }
        .section-title {
            font-size: 11.5px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase;
            color: #6f84a4; margin-bottom: 12px;
            padding-bottom: 10px; border-bottom: 1px solid rgba(148, 184, 255, .1);
        }

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
            background: rgba(34, 211, 238, .09); border: 1px solid rgba(34, 211, 238, .17);
            display: flex; align-items: center; justify-content: center; color: #3fd8ef;
        }
        .row-icon svg { width: 18px; height: 18px; }
        .row-body { min-width: 0; flex: 1; text-align: start; }
        .row-label { font-size: 11px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: #7488a8; }
        .row-value { margin-top: 1px; font-size: 15px; font-weight: 500; color: #edf4fc; overflow-wrap: anywhere; }
        a.row { transition: background .2s ease; border-radius: 10px; }
        a.row:hover .row-value { color: #7ce9f7; }

        /* ---------- QR / share ---------- */
        .qr-tile {
            width: min(224px, 68%); margin: 4px auto 18px;
            background: #ffffff; border-radius: 20px; padding: 14px;
            box-shadow: 0 14px 34px rgba(2, 8, 20, .5);
        }
        .qr-tile img { display: block; width: 100%; height: auto; border-radius: 8px; }
        .qr-hint { text-align: center; color: #8fa3bf; font-size: 13px; margin: -6px 0 16px; }

        /* ---------- Footer ---------- */
        .foot { text-align: center; color: #54688a; font-size: 12.5px; padding: 12px 0 2px; }
        .foot .mark {
            font-weight: 700;
            background: linear-gradient(135deg, #06b6d4, #22d3ee 52%, #10e0c5);
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

        @media (max-width: 359px) {
            .section, .hero { padding-inline: 16px; }
            .hero-cover { margin-inline: -16px; }
            .panel { border-radius: 20px; }
        }
    </style>
</head>
<body>
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
                    Save Contact
                </a>
                @if($employee->phone || $employee->email)
                    <div class="actions-row">
                        @if($employee->phone)
                            <a class="btn btn-ghost" href="tel:{{ $employee->phone }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                Call
                            </a>
                        @endif
                        @if($employee->email)
                            <a class="btn btn-ghost" href="mailto:{{ $employee->email }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                Email
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        {{-- ============ Details ============ --}}
        @if($hasDetails)
            <section class="panel section">
                <h2 class="section-title">Details</h2>
                <div class="rows">
                    @if($employee->employee_number)
                        <div class="row">
                            <span class="row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M15 8h2M15 12h2M7 16h10"/></svg></span>
                            <span class="row-body">
                                <span class="row-label">Employee No</span>
                                <span class="row-value" style="display:block" dir="auto">{{ $employee->employee_number }}</span>
                            </span>
                        </div>
                    @endif
                    @if($employee->department?->name)
                        <div class="row">
                            <span class="row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 12.18-8.58 3.91a2 2 0 0 1-1.66 0L3.18 12.18"/><path d="m22 17.18-8.58 3.91a2 2 0 0 1-1.66 0L3.18 17.18"/></svg></span>
                            <span class="row-body">
                                <span class="row-label">Department</span>
                                <span class="row-value" style="display:block" dir="auto">{{ $employee->department->name }}</span>
                            </span>
                        </div>
                    @endif
                    @if($employee->branch?->name)
                        <div class="row">
                            <span class="row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></span>
                            <span class="row-body">
                                <span class="row-label">Branch</span>
                                <span class="row-value" style="display:block" dir="auto">{{ $employee->branch->name }}</span>
                            </span>
                        </div>
                    @endif
                    @if($employee->phone)
                        <a class="row" href="tel:{{ $employee->phone }}">
                            <span class="row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
                            <span class="row-body">
                                <span class="row-label">Phone</span>
                                <span class="row-value" style="display:block" dir="ltr">{{ $employee->phone }}</span>
                            </span>
                        </a>
                    @endif
                    @if($employee->email)
                        <a class="row" href="mailto:{{ $employee->email }}">
                            <span class="row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></span>
                            <span class="row-body">
                                <span class="row-label">Work Email</span>
                                <span class="row-value" style="display:block" dir="ltr">{{ $employee->email }}</span>
                            </span>
                        </a>
                    @endif
                    @if($employee->user?->email)
                        <a class="row" href="mailto:{{ $employee->user->email }}">
                            <span class="row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"/></svg></span>
                            <span class="row-body">
                                <span class="row-label">Personal Email</span>
                                <span class="row-value" style="display:block" dir="ltr">{{ $employee->user->email }}</span>
                            </span>
                        </a>
                    @endif
                </div>
            </section>
        @endif

        {{-- ============ Share ============ --}}
        <section class="panel section">
            <h2 class="section-title">Share this card</h2>
            @if($card->qr_code)
                <div class="qr-tile">
                    <img src="{{ Storage::disk('public')->url($card->qr_code) }}" alt="QR code for this business card">
                </div>
                <p class="qr-hint">Scan the code or share the link below</p>
            @endif
            <div class="share-row">
                <button type="button" class="btn btn-ghost" id="copyBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    <span id="copyLabel" aria-live="polite">Copy link</span>
                </button>
                <button type="button" class="btn btn-ghost" id="shareBtn" hidden>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.59 13.51 6.83 3.98"/><path d="m15.41 6.51-6.82 3.98"/></svg>
                    Share
                </button>
            </div>
        </section>

        <footer class="foot">
            <span class="mark">iD+</span> by Mhawer — Digital Business Card
        </footer>
    </div>
</main>

<script>
    (function () {
        'use strict';

        /* ---- Fire-and-forget view tracking (must never break the page) ---- */
        try {
            var slug = @js($card->public_url);
            var endpoint = new URL('../cards/' + encodeURIComponent(slug) + '/track', window.location.href);
            fetch(endpoint.toString(), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ interaction_type: 'view', source: 'LINK' }),
                keepalive: true
            }).catch(function () {});
        } catch (e) { /* no-op */ }

        /* ---- Copy link ---- */
        var copyBtn = document.getElementById('copyBtn');
        var copyLabel = document.getElementById('copyLabel');
        var copyTimer = null;

        function showCopied() {
            if (!copyLabel) return;
            copyLabel.textContent = 'Copied';
            if (copyTimer) clearTimeout(copyTimer);
            copyTimer = setTimeout(function () { copyLabel.textContent = 'Copy link'; }, 2000);
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
    })();
</script>
</body>
</html>
