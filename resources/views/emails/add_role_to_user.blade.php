@component('mail::message')

{{ $invite->inviter->name }} ({{ $invite->inviter->email }}) has sent you an invitation on web platform: {{ config('app.name') }}.

@if($invite->role)
You has been assigned the role: {{ $invite->role->name }}.
@endif

You have {{ $invite->role->name }} role now.

If you have been sent this email by mistake, please ignore this message.

Best regards,
Site Admin,
{{ config('app.name') }}

@endcomponent
