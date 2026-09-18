@component('mail::message')

{{ $snapshot['senderName'] }}@if($snapshot['senderEmail']) ({{ $snapshot['senderEmail'] }})@endif has invited you to {{ config('app.name') }}.

You have been invited to join the {{ $snapshot['targetLabel'] }}: {{ $snapshot['targetName'] }}.

Use the link below to register with the email address that received this invitation and join this {{ $snapshot['targetLabel'] }}.

<x-mail::button :url="$acceptUrl">Register and join</x-mail::button>

If the button is not working, copy this link into your browser: {{ $acceptUrl }}.

If you did not expect this invitation, you can ignore this email.

Best regards,
{{ config('app.name') }}

@endcomponent
