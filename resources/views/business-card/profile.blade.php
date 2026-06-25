<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $employee->name }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>

        body{
            background:#edf2f7;
            font-family:'Segoe UI',sans-serif;
        }

        .profile-card{

            max-width:650px;
            margin:35px auto;

            border:none;
            border-radius:25px;

            overflow:hidden;

            box-shadow:0 12px 35px rgba(0,0,0,.12);
        }

        .header{

            background:linear-gradient(135deg,#0d6efd,#198754);

            color:#fff;

            text-align:center;

            padding:35px;
        }

        .logo{

            max-height:70px;
            margin-bottom:15px;
        }

        .avatar{

            width:130px;
            height:130px;

            border-radius:50%;

            background:white;

            margin:auto;

            border:6px solid rgba(255,255,255,.25);

            overflow:hidden;
        }

        .avatar img{

            width:100%;
            height:100%;
            object-fit:cover;
        }

        .avatar span{

            display:flex;
            align-items:center;
            justify-content:center;

            height:100%;

            font-size:55px;
            color:#0d6efd;
            font-weight:bold;
        }

        .section{

            padding:25px;
        }

        .section-title{

            color:#0d6efd;

            font-size:18px;

            font-weight:600;

            margin-bottom:15px;
        }

        .table td{

            border:none;

            padding:8px 0;
        }

        .label{

            width:40%;
            color:#6c757d;
        }

        .qr{

            width:170px;

            margin:auto;

            display:block;
        }

        .footer{

            background:#fafafa;

            text-align:center;

            padding:25px;

            color:#888;
        }

        .action-btn{

            margin-bottom:10px;
        }

    </style>

</head>

<body>

<div class="card profile-card">

    <div class="header">

        {{-- Company Logo --}}

        @if(optional($employee->company)->logo)

            <img src="{{ asset('storage/'.$employee->company->logo) }}"
                 class="logo">

        @endif

        <div class="avatar">

            @if($employee->photo)

                <img src="{{ asset('storage/'.$employee->photo) }}">

            @else

                <span>{{ strtoupper(substr($employee->name,0,1)) }}</span>

            @endif

        </div>

        <h3 class="mt-3">{{ $employee->name }}</h3>

        <h6>

            {{ $employee->job_title }}

        </h6>

        <span class="badge bg-light text-success">

{{ optional($employee->company)->name }}

</span>

    </div>

    <div class="section">

        <div class="section-title">

            <i class="bi bi-person-fill"></i>

            Personal Information

        </div>

        <table class="table">

            <tr>
                <td class="label">Employee No</td>
                <td>{{ $employee->employee_number }}</td>
            </tr>

            <tr>
                <td class="label">Iqama</td>
                <td>{{ $employee->iqama_number ?? '-' }}</td>
            </tr>

            <tr>
                <td class="label">Nationality</td>
                <td>{{ $employee->nationality ?? '-' }}</td>
            </tr>

            <tr>
                <td class="label">Gender</td>
                <td>{{ $employee->gender ?? '-' }}</td>
            </tr>

        </table>

    </div>

    <hr>

    <div class="section">

        <div class="section-title">

            <i class="bi bi-building"></i>

            Employment

        </div>

        <table class="table">

            <tr>
                <td class="label">Company</td>
                <td>{{ optional($employee->company)->name }}</td>
            </tr>

            <tr>
                <td class="label">Department</td>
                <td>{{ optional($employee->department)->name }}</td>
            </tr>

            <tr>
                <td class="label">Branch</td>
                <td>{{ $employee->branch ?? '-' }}</td>
            </tr>

            <tr>
                <td class="label">Manager</td>
                <td>{{ optional($employee->manager)->name ?? '-' }}</td>
            </tr>

            <tr>
                <td class="label">Hire Date</td>
                <td>{{ $employee->hire_date ?? '-' }}</td>
            </tr>

        </table>

    </div>

    <hr>

    <div class="section">

        <div class="section-title">

            <i class="bi bi-envelope-fill"></i>

            Contact

        </div>

        <table class="table">

            <tr>
                <td class="label">Email</td>

                <td>

                    <a href="mailto:{{ $employee->email }}">

                        {{ $employee->email }}

                    </a>

                </td>

            </tr>

            <tr>

                <td class="label">

                    Phone

                </td>

                <td>

                    @if($employee->phone)

                        <a href="tel:{{ $employee->phone }}">

                            {{ $employee->phone }}

                        </a>

                    @endif

                </td>

            </tr>

        </table>

    </div>

    <hr>

    <div class="section text-center">

        <div class="section-title">

            <i class="bi bi-qr-code"></i>

            QR Code

        </div>

        @if($card->qr_code)

            <img src="{{ Storage::url($card->qr_code) }}"
                 class="qr">

        @endif

    </div>

    <hr>

    <div class="section">

        <div class="section-title">

            <i class="bi bi-lightning-charge-fill"></i>

            Quick Actions

        </div>

        <div class="d-grid">

            @if($employee->phone)

                <a href="tel:{{ $employee->phone }}"
                   class="btn btn-success action-btn">

                    <i class="bi bi-telephone-fill"></i>

                    Call

                </a>

            @endif

            @if($employee->email)

                <a href="mailto:{{ $employee->email }}"
                   class="btn btn-primary action-btn">

                    <i class="bi bi-envelope-fill"></i>

                    Email

                </a>

            @endif

            @if(optional($employee->company)->website)

                <a href="{{ $employee->company->website }}"
                   target="_blank"
                   class="btn btn-dark action-btn">

                    <i class="bi bi-globe"></i>

                    Company Website

                </a>

            @endif

            <a href="{{ route('business-card.vcard',$card->id) }}"
               class="btn btn-outline-primary">

                <i class="bi bi-person-vcard-fill"></i>

                Download Contact

            </a>

        </div>

    </div>

    <div class="footer">

        <div>

            <strong>{{ config('app.name') }}</strong>

        </div>

        <div>

            Digital Employee Business Card

        </div>

        <div class="mt-2">

            @if($card->is_active)

                <span class="badge bg-success">

Active

</span>

            @else

                <span class="badge bg-danger">

Inactive

</span>

            @endif

        </div>

    </div>

</div>

</body>

</html>
