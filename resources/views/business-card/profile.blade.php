<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $employee->name }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{
            background:#f5f7fb;
        }

        .card-profile{
            max-width:500px;
            margin:40px auto;
            border:none;
            border-radius:20px;
            overflow:hidden;
            box-shadow:0 10px 35px rgba(0,0,0,.12);
        }

        .header{
            background:linear-gradient(135deg,#0d6efd,#198754);
            color:#fff;
            text-align:center;
            padding:35px 20px;
        }

        .avatar{
            width:110px;
            height:110px;
            border-radius:50%;
            border:5px solid rgba(255,255,255,.3);
            background:#fff;
            color:#0d6efd;
            font-size:42px;
            font-weight:bold;
            display:flex;
            align-items:center;
            justify-content:center;
            margin:auto;
        }

        .info{
            padding:25px;
        }

        .info table{
            width:100%;
        }

        .info td{
            padding:10px 0;
            border-bottom:1px solid #eee;
        }

        .label{
            color:#777;
            width:40%;
        }

        .footer{
            text-align:center;
            padding:20px;
            color:#999;
            font-size:13px;
        }

        .badge-status{
            font-size:14px;
        }
    </style>

</head>
<body>

<div class="card card-profile">

    <div class="header">

        <div class="avatar">
            {{ strtoupper(substr($employee->name,0,1)) }}
        </div>

        <h3 class="mt-3 mb-1">{{ $employee->name }}</h3>

        <p class="mb-2">
            {{ optional($employee->department)->name }}
        </p>

        <span class="badge bg-success badge-status">
            {{ ucfirst($card->status) }}
        </span>

    </div>

    <div class="info">

        <table>

            <tr>
                <td class="label">Employee No.</td>
                <td>{{ $employee->employee_number }}</td>
            </tr>

            <tr>
                <td class="label">Email</td>
                <td>{{ $employee->email }}</td>
            </tr>

            <tr>
                <td class="label">Phone</td>
                <td>{{ $employee->phone ?? '-' }}</td>
            </tr>

            <tr>
                <td class="label">Company</td>
                <td>{{ optional($employee->company)->name }}</td>
            </tr>

            <tr>
                <td class="label">Department</td>
                <td>{{ optional($employee->department)->name ?? '-' }}</td>
            </tr>

            <tr>
                <td class="label">Status</td>
                <td>{{ ucfirst($card->status) }}</td>
            </tr>

        </table>

    </div>

    <div class="footer">

        Digital Business Card

        <br>

        {{ config('app.name') }}

    </div>

</div>

</body>
</html>
