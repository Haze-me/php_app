<x-mail::message>
# New Connection!

<x-mail::panel>
    <h1>{{ $mailData['title'] }}</h1><br/>
    {{ $mailData['message'] }}<br/><br/>
    <a href="mailto:{{ $mailData['email_requested'] }}">
        Message user
    </a><br/>
    <small>(This is an automated message, please do not reply)
    If you don't want to accept the connection request, simply ignore it.</small><br/>Best regards,<br/>
    The Team at Silfrica.
</x-mail::panel>

<x-mail::button :url="'mailto:'.$mailData['email_requested']" color="success">
Message User
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>