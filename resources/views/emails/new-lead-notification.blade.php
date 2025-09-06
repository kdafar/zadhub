<x-mail::message>
# 🚀 New Lead from Landing Page

A new lead has just been submitted. Here are the details:

<x-mail::table>
| Field       | Value                                   |
| :---------- | :-------------------------------------- |
| **Name** | {{ $lead->name ?: 'N/A' }}              |
| **Company** | {{ $lead->company ?: 'N/A' }}            |
| **Email** | {{ $lead->email ?: 'N/A' }}              |
| **Phone** | `{{ $lead->phone }}`                       |
| **Use Case**| {{ $lead->use_case ?: 'N/A' }}            |
| **Locale** | {{ strtoupper($lead->locale) }}         |
| **IP Address**| `{{ $lead->ip }}`                        |
</x-mail::table>

@if($lead->message)
**Message:**
<x-mail::panel>
{{ $lead->message }}
</x-mail::panel>
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
