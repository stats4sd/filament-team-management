@component('mail::message')

{{ $invite->inviter->name }} ({{ $invite->inviter->email }}) has sent you an invitation on web platform: {{ config('app.name') }}.

@if($invite->program)
You will be assigned to the program: {{ $invite->program->name }}.
@endif

You are a member of {{ $invite->program->name }} now.

If you have been sent this email by mistake, please ignore this message.

Best regards,
Site Admin,
{{ config('app.name') }}

@endcomponent
