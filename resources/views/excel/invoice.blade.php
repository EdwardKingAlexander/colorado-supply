<table>
    <thead>
        <tr>
            <th colspan="6" style="font-size: 18px; font-weight: bold;">Colorado Supply & Procurement LLC</th>
        </tr>
        <tr>
            <th colspan="6" style="font-size: 14px;">INVOICE</th>
        </tr>
        <tr><th colspan="6"></th></tr>
        <tr>
            <th>Invoice #:</th>
            <td>{{ $order->order_number }}</td>
            <th>Date:</th>
            <td>{{ $order->created_at->format('M d, Y') }}</td>
        </tr>
        @if($order->po_number)
            <tr>
                <th>PO #:</th>
                <td>{{ $order->po_number }}</td>
            </tr>
        @endif
        <tr><th colspan="6"></th></tr>
        <tr>
            <th colspan="2">Bill To:</th>
        </tr>
        <tr>
            <td colspan="2">{{ $order->customer_name }}</td>
        </tr>
        @if($order->company)
            <tr>
                <td colspan="2">{{ $order->company }}</td>
            </tr>
        @endif
        <tr><th colspan="6"></th></tr>
    </thead>
    <tbody>
        <tr>
            <th>Item</th>
            <th>Description</th>
            <th>Qty</th>
            <th>Unit Price</th>
            <th>Discount</th>
            <th>Total</th>
        </tr>
        @foreach($order->items as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ number_format($item->quantity, 2) }}</td>
                <td>${{ number_format($item->unit_price, 2) }}</td>
                <td>${{ number_format($item->line_discount, 2) }}</td>
                <td>${{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
        <tr><td colspan="6"></td></tr>
        <tr>
            <td colspan="4"></td>
            <th>Subtotal:</th>
            <td>${{ number_format($order->subtotal, 2) }}</td>
        </tr>
        @if($order->tax_total > 0)
            <tr>
                <td colspan="4"></td>
                <th>Tax ({{ number_format($order->tax_rate, 2) }}%):</th>
                <td>${{ number_format($order->tax_total, 2) }}</td>
            </tr>
        @endif
        @if($order->shipping_total > 0)
            <tr>
                <td colspan="4"></td>
                <th>Shipping:</th>
                <td>${{ number_format($order->shipping_total, 2) }}</td>
            </tr>
        @endif
        @if($order->discount_total > 0)
            <tr>
                <td colspan="4"></td>
                <th>Discount:</th>
                <td>-${{ number_format($order->discount_total, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td colspan="4"></td>
            <th style="font-size: 14px;">TOTAL:</th>
            <th style="font-size: 14px;">${{ number_format($order->grand_total, 2) }}</th>
        </tr>
    </tbody>
</table>
