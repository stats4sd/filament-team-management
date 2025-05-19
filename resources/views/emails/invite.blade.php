@component('mail::message')

{{ $invite->inviter->name }} ({{ $invite->inviter->email }}) has invited you to join the web platform: {{ config('app.name') }}.

@if($invite->team)
You have been invited to join the team: {{ $invite->team->name }}.
@endif

@if($invite->role)
You will be assigned the role: {{ $invite->role->name }}.
@endif

@if($invite->program)
You will be assigned to the program: {{ $invite->program->name }}.
@endif

Click the link below to register on the platform. If you use the same email address, you will be automatically given the correct permissions after registration.

<x-mail::button :url='$acceptUrl'>
    Register Here
</x-mail::button>

If the button above is not working, or this email is not displaying correctly, please copy and paste the following link into your browser: {{ $acceptUrl }}.


If you do not wish to register, or you have been sent this email by mistake, please ignore this message.

Best regards,
Site Admin,
{{ config('app.name') }}

@endcomponent
