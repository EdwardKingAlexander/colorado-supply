<x-mail::message>
# New Repair Services Request

**Name:** {{ $data['name'] }}
**Email:** {{ $data['email'] }}
@isset($data['phone'])
**Phone:** {{ $data['phone'] }}
@endisset
@isset($data['company'])
**Company:** {{ $data['company'] }}
@endisset

**Equipment Type:** {{ $data['equipment_type'] }}
@isset($data['manufacturer'])
**Manufacturer:** {{ $data['manufacturer'] }}
@endisset
**Model Number:** {{ $data['model_number'] }}
@isset($data['serial_number'])
**Serial Number:** {{ $data['serial_number'] }}
@endisset
@isset($data['urgency'])
**Urgency:** {{ ucfirst($data['urgency']) }}
@endisset

**Issue Description:**
> {{ $data['issue_description'] }}

<x-mail::button :url="'mailto:'.$data['email']">
Reply to {{ $data['name'] }}
</x-mail::button>

Thanks,
{{ config('app.name') }}
</x-mail::message>
