<h2>Welcome to HelpDeskPro</h2>

<p>Hello,</p>

<p>
Thank you for registering.
Please verify your email address by clicking the link below:
</p>


<a href="{{ config('app.frontend_url') }}/verify-email?token={{ urlencode($token) }}&email={{ urlencode($email) }}">
    Verify Email
</a>


<p>
If you did not create this account, you can ignore this email.
</p>