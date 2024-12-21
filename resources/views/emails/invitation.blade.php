<x-mail::message>
# An Invitation!

<x-mail::panel>
    <h1>{{ $mailData['title'] }}</h1><br/>
    <i>{{ $mailData['body'] }}</i><br/><br/>
    Click the link or button below to accept invite: <br/>
    <a href="{{ $mailData['url'] }}">
        Accept Invite
    </a><br/>
    <small>(This is an automated message, please do not reply)
    If you don't want to accept the invite, simply ignore it.</small><br/>Best regards,<br/>
    <b>The Team at Silfrica.</b>
</x-mail::panel>

<x-mail::button :url="$mailData['url']" color="success">
Accept invite
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
