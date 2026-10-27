<x-mail::message>
# DIBBS RFQ digest

@if ($digest['new_file'])
New RFQs posted **{{ \Carbon\Carbon::parse($digest['posting_date'])->format('l, M j') }}** that fit Colorado Supply, plus RFQs closing soon that you're working or that match your catalog.
@else
No new DIBBS file since the last digest. RFQs closing soon that you're working or that match your catalog:
@endif

@foreach ($digest['sections'] as $key => $section)
## {{ $section['title'] }} ({{ $section['total'] }})

@foreach ($section['items'] as $rfq)
**[{{ $rfq['nomenclature'] ?: $rfq['nsn'] }}]({{ $rfq['admin_url'] }})** · NSN {{ $rfq['nsn'] }} · Qty {{ $rfq['quantity'] }}
Due **{{ $rfq['return_by'] }}** · {{ $rfq['competition'] }}@if ($rfq['set_aside']) · {{ $rfq['set_aside'] }} set-aside @endif @if ($key === 'closing') · {{ $rfq['status'] }} @endif · [{{ $rfq['solicitation_number'] }} in DIBBS]({{ $rfq['dibbs_url'] }})

@endforeach
@if ($section['total'] > count($section['items']))
…and {{ $section['total'] - count($section['items']) }} more on the RFQ list.

@endif
@endforeach
<x-mail::button :url="$listUrl">
Open DIBBS RFQs
</x-mail::button>

Approved-source buys need an approved part; check the RFQ's approved sources before quoting.

{{ config('app.name') }}
</x-mail::message>
