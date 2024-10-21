@component('mail::message')

{{ $invite->inviter->name }} ({{ $invite->inviter->email }}) has invited you to be the following role on the {{ config('app.name') }}.

**Role:** {{ $invite->role->name }}

Click the link below to register on the platform. If you use the same email address, you will be automatically added to the role after registration.


<x-mail::button :url='$acceptUrl'>
    Register to be {{ $invite->role->name }}
</x-mail::button>


If you do not wish to register, or you have been sent this email by mistake, please ignore this message.


Best regards,
Site Admin,
{{ config('app.name') }}

@endcomponent