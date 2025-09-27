<x-mail::message>
# New Inquiry

**Name:** {{ $data['name'] }}  
**Email:** {{ $data['email'] }}  
@isset($data['phone'])
**Phone:** {{ $data['phone'] }}
@endisset

**Message:**
> {{ $data['message'] }}

<x-mail::button :url="'mailto:'.$data['email']">
Reply to {{ $data['name'] }}
</x-mail::button>

Thanks,  
{{ config('app.name') }}
</x-mail::message>
