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
                            <div style="font-size: 13px; font-weight: 700; color: #7dd3fc; text-transform: uppercase; letter-spacing: 3px; margin-bottom: 8px;">Althesa Subdivision</div>
                            <h1 style="margin: 0; font-size: 26px; font-weight: 800; color: #ffffff; line-height: 1.3;">Welcome to Your New Account</h1>
                            <div style="margin-top: 16px;">
                                <span style="display: inline-block; padding: 6px 18px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2); border-radius: 99px; font-size: 12px; font-weight: 700; color: #e0f2fe; text-transform: uppercase; letter-spacing: 1px;">{{ $roleName }} Account</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Introduction -->
                    <tr>
                        <td style="padding: 36px 40px 16px 40px;">
                            <p style="margin: 0 0 12px 0; font-size: 16px; color: #0f172a;">Hello <strong>{{ $recipientName }}</strong>,</p>
                            <p style="margin: 0; font-size: 15px; color: #475569; line-height: 1.7;">
                                {{ $roleIntroduction }}
                            </p>
                        </td>
                    </tr>

                    <!-- Credentials Box -->
                    <tr>
                        <td style="padding: 12px 40px 20px 40px;">
                            <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 700; color: #0ea5e9; text-transform: uppercase; letter-spacing: 1px;">Your Login Credentials</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                                <tr>
                                    <td style="padding: 18px 24px;">
                                        <p style="margin: 0 0 10px 0; font-size: 14px; color: #334155;">
                                            <strong>Role:</strong> {{ $roleName }}
                                        </p>
                                        <p style="margin: 0 0 10px 0; font-size: 14px; color: #334155;">
                                            <strong>Email:</strong> {{ $user->email }}
                                        </p>
                                        <p style="margin: 0; font-size: 14px; color: #334155;">
                                            <strong>Temporary Password:</strong>
                                            <span style="display: inline-block; font-family: monospace; font-size: 15px; font-weight: 700; color: #0f172a; background: #e2e8f0; padding: 4px 10px; border-radius: 6px; margin-left: 4px;">{{ $password }}</span>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Website & Login Links -->
                    <tr>
                        <td style="padding: 8px 40px 24px 40px;">
                            <div style="text-align: center; margin-bottom: 20px;">
                                <a href="{{ $loginUrl }}" style="display: inline-block; background: #0ea5e9; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 700; padding: 14px 32px; border-radius: 10px;">Sign In to the Portal</a>
                            </div>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #f0f9ff; border-radius: 12px; border: 1px solid #bae6fd;">
                                <tr>
                                    <td style="padding: 16px 24px;">
                                        <div style="font-size: 11px; font-weight: 700; color: #0369a1; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Website Links</div>
                                        <p style="margin: 0 0 6px 0; font-size: 13px; color: #334155;">
                                            <strong>Main Website:</strong>
                                            <a href="{{ $websiteUrl }}" style="color: #0284c7; text-decoration: underline;">{{ $websiteUrl }}</a>
                                        </p>
                                        <p style="margin: 0; font-size: 13px; color: #334155;">
                                            <strong>Login Page:</strong>
                                            <a href="{{ $loginUrl }}" style="color: #0284c7; text-decoration: underline;">{{ $loginUrl }}</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Security Note -->
                    <tr>
                        <td style="padding: 0 40px 28px 40px;">
                            <div style="font-size: 13px; color: #475569; line-height: 1.6; background: #fffbeb; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 0 8px 8px 0;">
                                <strong>Security Reminder:</strong> For your protection, please sign in using the link above and change your password immediately. Do not share your credentials with anyone.
                            </div>
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
