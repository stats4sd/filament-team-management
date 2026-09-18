@component('mail::message')

{{ $snapshot['senderName'] }}@if($snapshot['senderEmail']) ({{ $snapshot['senderEmail'] }})@endif has updated your membership on {{ config('app.name') }}.

You have been added to the {{ $snapshot['targetLabel'] }}: {{ $snapshot['targetName'] }}.

If you did not expect this change, please contact the site administrator.

Best regards,
{{ config('app.name') }}

@endcomponent
