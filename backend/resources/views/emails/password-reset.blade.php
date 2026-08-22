<h2>HelpDeskPro Password Reset</h2>

<p>Hello,</p>

<p>
You requested to reset your HelpDeskPro password.
</p>

<p>
Click the link below to reset your password:
</p>

<a href="{{ config('app.frontend_url') }}/reset-password?token={{ urlencode($token) }}&email={{ urlencode($email) }}">
    Reset Password
</a>

<p>
This link will expire soon.
</p>

<p>
If you did not request this password reset, you can ignore this email.
</p>