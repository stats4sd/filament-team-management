@component('mail::message')

{{ $invite->inviter->name }} ({{ $invite->inviter->email }}) has sent you an invitation on web platform: {{ config('app.name') }}.

@if($invite->team)
You have been invited to join the {{ config('filament-team-management.models.team')::getModelNameLower() }}: {{ $invite->team->name }}.
@endif

You are a member of {{ $invite->team->name }} now.

If you have been sent this email by mistake, please ignore this message.

Best regards,
Site Admin,
{{ config('app.name') }}

@endcomponent
