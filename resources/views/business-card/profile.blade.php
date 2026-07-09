<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $employee->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:Arial,sans-serif;background:linear-gradient(135deg,#0f172a,#1d4ed8);min-height:100vh;color:#fff}
        .container{max-width:980px;margin:auto;padding:40px 20px}
        .glass{background:rgba(255,255,255,.08);backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,.12);border-radius:24px;padding:30px;margin-bottom:24px}
        .hero{text-align:center}
        .logo{height:70px;margin-bottom:16px}
        .avatar{width:150px;height:150px;border-radius:50%;overflow:hidden;margin:20px auto;border:5px solid rgba(255,255,255,.25)}
        .avatar img{width:100%;height:100%;object-fit:cover}
        .initial{display:flex;align-items:center;justify-content:center;height:100%;font-size:56px;background:#fff;color:#1d4ed8;font-weight:bold}
        h1{margin:12px 0}.badge{display:inline-block;background:#fff;color:#111;padding:8px 18px;border-radius:999px}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}
        .item,.contact{background:rgba(255,255,255,.05);padding:18px;border-radius:16px}
        small{opacity:.7;display:block;margin-bottom:6px}
        .contact{display:flex;gap:12px;text-decoration:none;color:#fff;align-items:center}
        .contact i{font-size:26px}
        .actions a{display:block;text-align:center;text-decoration:none;color:#fff;padding:14px;border-radius:12px;margin-top:12px}
        .call{background:#16a34a}.mail{background:#2563eb}.save{background:#111827}
        .qr{display:block;margin:20px auto;max-width:220px}
    </style>
</head>
<body>
<div class="container">
    <div class="glass hero">
        @if(optional($employee->company)->logo)<img class="logo" src="{{ asset('storage/'.$employee->company->logo) }}">@endif
        <div class="avatar">
            @if($employee->photo)
                <img src="{{ asset('storage/'.$employee->photo) }}">
            @else
                <div class="initial">{{ strtoupper(substr($employee->name,0,1)) }}</div>
            @endif
        </div>
        <h1>{{ $employee->name }}</h1>
        <p>{{ $employee->position }}</p>
        <span class="badge">{{ optional($employee->company)->name }}</span>
    </div>

    <div class="glass">
        <h2>Employee Information</h2><br>
        <div class="grid">
            <div class="item"><small>Employee No</small>{{ $employee->employee_number }}</div>
            <div class="item"><small>Department</small>{{ optional($employee->department)->name }}</div>
            <div class="item"><small>Branch</small>{{ optional($employee->branch)->name }}</div>
            <div class="item"><small>Position</small>{{ $employee->position }}</div>
        </div>
    </div>

    <div class="glass">
        <h2>Contact</h2><br>

        @if($employee->phone)
            <a class="contact" href="tel:{{ $employee->phone }}">
                <i class="bi bi-telephone-fill"></i>
                <div>
                    <strong>Phone</strong><br>
                    {{ $employee->phone }}
                </div>
            </a><br>
        @endif

        @if($employee->email)
            <a class="contact" href="mailto:{{ $employee->email }}">
                <i class="bi bi-envelope-fill"></i>
                <div>
                    <strong>Work Email</strong><br>
                    {{ $employee->email }}
                </div>
            </a><br>
        @endif

        @if(optional($employee->user)->email)
            <a class="contact" href="mailto:{{ $employee->user->email }}">
                <i class="bi bi-envelope-fill"></i>
                <div>
                    <strong>Personal Email</strong><br>
                    {{ $employee->user->email }}
                </div>
            </a>
        @endif
    </div>

    <div class="glass" style="text-align:center">
        <h2>QR Code</h2>
        @if($card->qr_code)
            <img class="qr" src="{{ Storage::disk('public')->url($card->qr_code) }}">
        @endif
    </div>

    <div class="glass actions">
        @if($employee->phone)<a class="call" href="tel:{{ $employee->phone }}">Call</a>@endif
        @if($employee->email)<a class="mail" href="mailto:{{ $employee->email }}">Email</a>@endif
        <a class="save" href="{{ route('business-card.vcard',$card->id) }}">Download Contact</a>
    </div>
</div>
</body>
</html>
