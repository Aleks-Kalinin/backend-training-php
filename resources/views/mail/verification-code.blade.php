<x-mail::message>
# Your verification code

Use this code to finish signing up:

<x-mail::panel>
{{ $code }}
</x-mail::panel>

It expires in {{ $minutes }} minutes and can be used once.

If you did not request it, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
