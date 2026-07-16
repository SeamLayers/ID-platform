<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID PLUS PLATFORM - Password Reset</title>
</head>
<body style="margin:0; padding:40px 20px; background:#f5f7fb; font-family:Arial, Helvetica, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center">


            <table width="500" cellpadding="0" cellspacing="0" style="max-width:500px; background:#ffffff; border-radius:16px; box-shadow:0 8px 24px rgba(0,0,0,.08); overflow:hidden;">

                <!-- Header -->
                <tr>
                    <td style="background:linear-gradient(135deg,#2563eb,#1d4ed8); padding:35px 30px; text-align:center;">
                        <div style="font-size:30px; font-weight:700; color:#ffffff; letter-spacing:1px;">
                            ID PLUS PLATFORM
                        </div>

                        <div style="margin-top:10px; font-size:15px; color:#dbeafe;">
                            Secure Identity & Digital Access
                        </div>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:40px 35px; text-align:center;">

                        <h2 style="margin:0 0 15px; color:#1f2937; font-size:24px;">
                            Password Reset
                        </h2>

                        <p style="margin:0 0 30px; color:#6b7280; line-height:1.7;">
                            We received a request to reset your password.
                            Use the verification code below to continue.
                        </p>

                        <div style="display:inline-block; padding:18px 40px; background:#eff6ff; border:2px dashed #2563eb; border-radius:12px;">
                            <span style="font-size:36px; font-weight:bold; letter-spacing:10px; color:#2563eb;">
                                {{ $otp }}
                            </span>
                        </div>

                        <p style="margin:30px 0 0; color:#6b7280; line-height:1.6;">
                            This verification code is valid for
                            <strong>10 minutes</strong>.
                        </p>

                        <p style="margin:20px 0 0; color:#9ca3af; font-size:13px; line-height:1.6;">
                            If you didn't request a password reset, you can safely ignore this email.
                        </p>

                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="padding:20px; text-align:center; border-top:1px solid #e5e7eb; color:#9ca3af; font-size:12px;">
                        © {{ date('Y') }} <strong>ID PLUS PLATFORM</strong>. All rights reserved.
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
