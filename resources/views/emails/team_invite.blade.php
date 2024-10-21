@component('mail::message')

{{ $invite->inviter->name }} ({{ $invite->inviter->email }}) has invited you to join the following team on the {{ config('app.name') }}.

**Team:** {{ $invite->team->name }}

Click the link below to register on the platform. If you use the same email address, you will be automatically added to the team after registration.


<x-mail::button :url='$acceptUrl'>
    Register to join {{ $invite->team->name }}
</x-mail::button>


If you do not wish to register, or you have been sent this email by mistake, please ignore this message.


Best regards,
Site Admin,
{{ config('app.name') }}

@endcomponent