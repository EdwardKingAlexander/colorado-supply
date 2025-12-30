<x-mail::message>
# Order {{ $order->order_number }} Confirmed

Thank you for partnering with Colorado Supply & Procurement. We received your quote conversion and have opened order **{{ $order->order_number }}** on {{ $order->created_at?->format('F j, Y') ?? now()->format('F j, Y') }}.

**Order Summary**

- Company / Contact: {{ $order->company ?? $order->customer_name ?? 'Procurement Team' }}
- Total: ${{ number_format($order->grand_total ?? 0, 2) }}
- Status: {{ ucfirst($order->status) }}

@if ($order->items?->count())
| Item | Qty | Unit | Line Total |
| :--- | ---:| ---: | ---: |
@foreach ($order->items as $item)
| {{ $item->name }} | {{ number_format($item->quantity, 2) }} | ${{ number_format($item->unit_price, 2) }} | ${{ number_format($item->line_total, 2) }} |
@endforeach
@endif

If you have updates for PO or delivery instructions, reply to this email and our team will assist.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
