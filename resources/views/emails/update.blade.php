@component('mail::message')

{{ $invite->inviter->name }} ({{ $invite->inviter->email }}) has updated your user account on the web platform: {{ config('app.name') }}.

@if($invite->team)
You have been added to {{ config('filament-team-management.models.team')::getModelNameLower() }}: {{ $invite->team->name }}.
@endif

@if($invite->role)
You have been assigned the role: {{ $invite->role->name }}.
@endif

@if($invite->program)
You have been assigned to the program: {{ $invite->program->name }}.
@endif

If you have been sent this email by mistake, please ignore this message.

Best regards,
Site Admin,
{{ config('app.name') }}

@endcomponent
