<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f1f5f9; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.08);">
                    
                    <!-- Header Banner -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0ea5e9 100%); padding: 40px 40px 32px 40px; text-align: center;">
                            <div style="font-size: 13px; font-weight: 700; color: #7dd3fc; text-transform: uppercase; letter-spacing: 3px; margin-bottom: 8px;">📢 Community Announcement</div>
                            <h1 style="margin: 0; font-size: 26px; font-weight: 800; color: #ffffff; line-height: 1.3;">{{ $announcement->title }}</h1>
                            <div style="margin-top: 16px;">
                                <span style="display: inline-block; padding: 6px 18px; background: rgba(255,255,255,0.15); backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.2); border-radius: 99px; font-size: 12px; font-weight: 700; color: #e0f2fe; text-transform: uppercase; letter-spacing: 1px;">{{ $announcement->category }}</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 36px 40px 20px 40px;">
                            <p style="margin: 0 0 6px 0; font-size: 13px; font-weight: 700; color: #0ea5e9; text-transform: uppercase; letter-spacing: 1px;">Message</p>
                            <div style="font-size: 15px; color: #334155; line-height: 1.75; background: #f8fafc; border-left: 4px solid #0ea5e9; padding: 20px 24px; border-radius: 0 12px 12px 0;">
                                {{ $announcement->content }}
                            </div>
                        </td>
                    </tr>

                    <!-- Details Bar -->
                    <tr>
                        <td style="padding: 12px 40px 32px 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #f0f9ff; border-radius: 12px; border: 1px solid #bae6fd;">
                                <tr>
                                    <td style="padding: 16px 24px; width: 50%;">
                                        <div style="font-size: 11px; font-weight: 700; color: #0369a1; text-transform: uppercase; letter-spacing: 1px;">Posted By</div>
                                        <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 4px;">{{ $announcement->author }}</div>
                                    </td>
                                    <td style="padding: 16px 24px; width: 50%; text-align: right;">
                                        <div style="font-size: 11px; font-weight: 700; color: #0369a1; text-transform: uppercase; letter-spacing: 1px;">Date Published</div>
                                        <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 4px;">{{ \Carbon\Carbon::parse($announcement->publish_date)->format('F d, Y \a\t g:i A') }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 40px;">
                            <div style="border-top: 1px solid #e2e8f0;"></div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 40px 32px 40px; text-align: center;">
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Althesa Subdivision</div>
                            <div style="font-size: 12px; color: #94a3b8; line-height: 1.5;">Community Management Office<br>&copy; {{ date('Y') }} Althesa Subdivision. All rights reserved.</div>
                            <div style="margin-top: 16px; font-size: 11px; color: #cbd5e1;">This is an automated notification. Please do not reply to this email.</div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
