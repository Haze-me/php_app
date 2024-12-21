<x-mail::message>
# An Invitation!

<x-mail::panel>
    <h1>{{ $mailData['title'] }}</h1><br/>
    {{ $mailData['body'] }}<br/>
    <h2 style="color: orange">Please do register a new account to accept this invite</h2>
    <br/>
        You have been invited to be an admin at Silfrica.<br/>Best regards,<br/>
        <b>The Team at Silfrica.</b>
    <br/><small>(This is an automated message, please do not reply)</small>
</x-mail::panel>

<x-mail::button :url="'https://silfrica.com'" color="success">
Visit Our Website
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
