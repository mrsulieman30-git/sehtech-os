<x-mail::message>
# Welcome to SEHTECH OS, {{ $user->name }}!

Your account has been successfully created. We're excited to have you on board!

Below are your login credentials:

**Email:** {{ $user->email }}  
**Temporary Password:** {{ $password }}

<x-mail::panel>
Please log in and change your password immediately.
</x-mail::panel>

<x-mail::button :url="$loginUrl">
Log In Now
</x-mail::button>

If you have any questions, please contact the IT Support team.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
