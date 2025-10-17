<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #1d4ed8;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1d4ed8;
            margin-bottom: 5px;
        }
        .invoice-title {
            font-size: 18px;
            margin-top: 10px;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background-color: #f3f4f6;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #e5e7eb;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .text-right {
            text-align: right;
        }
        .totals-table {
            width: 300px;
            margin-left: auto;
            margin-top: 20px;
        }
        .totals-table td {
            padding: 5px 10px;
        }
        .grand-total {
            font-size: 16px;
            font-weight: bold;
            background-color: #f3f4f6;
        }
        .payment-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
        }
        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-unpaid {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            text-align: center;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">Colorado Supply & Procurement LLC</div>
        <div>Professional Procurement Services</div>
        <div class="invoice-title">INVOICE</div>
    </div>

    <div class="info-section">
        <div class="info-grid">
            <div class="info-col">
                <div class="info-label">Bill To:</div>
                <div>{{ $order->customer_name }}</div>
                @if($order->company)
                    <div>{{ $order->company }}</div>
                @endif
                <div>{{ $order->contact_email }}</div>
                <div>{{ $order->contact_phone }}</div>
                @if($order->billing_address)
                    <div style="margin-top: 10px;">
                        @if(isset($order->billing_address['street']))
                            <div>{{ $order->billing_address['street'] }}</div>
                        @endif
                        @if(isset($order->billing_address['line2']))
                            <div>{{ $order->billing_address['line2'] }}</div>
                        @endif
                        @if(isset($order->billing_address['city']))
                            <div>
                                {{ $order->billing_address['city'] }},
                                {{ $order->billing_address['state'] ?? '' }}
                                {{ $order->billing_address['zip'] ?? '' }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
            <div class="info-col">
                <div><strong>Invoice #:</strong> {{ $order->order_number }}</div>
                <div><strong>Date:</strong> {{ $order->created_at->format('M d, Y') }}</div>
                @if($order->po_number)
                    <div><strong>PO #:</strong> {{ $order->po_number }}</div>
                @endif
                @if($order->job_number)
                    <div><strong>Job #:</strong> {{ $order->job_number }}</div>
                @endif
                <div style="margin-top: 10px;">
                    <strong>Payment Status:</strong><br>
                    <span class="payment-status status-{{ strtolower($order->payment_status->value) }}">
                        {{ $order->payment_status->label() }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    @if($order->shipping_address && $order->shipping_address !== $order->billing_address)
        <div class="info-section">
            <div class="info-label">Ship To:</div>
            @if(isset($order->shipping_address['street']))
                <div>{{ $order->shipping_address['street'] }}</div>
            @endif
            @if(isset($order->shipping_address['line2']))
                <div>{{ $order->shipping_address['line2'] }}</div>
            @endif
            @if(isset($order->shipping_address['city']))
                <div>
                    {{ $order->shipping_address['city'] }},
                    {{ $order->shipping_address['state'] ?? '' }}
                    {{ $order->shipping_address['zip'] ?? '' }}
                </div>
            @endif
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Description</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->line_discount, 2) }}</td>
                    <td class="text-right">${{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">${{ number_format($order->subtotal, 2) }}</td>
        </tr>
        @if($order->discount_total > 0)
            <tr>
                <td>Discount:</td>
                <td class="text-right">-${{ number_format($order->discount_total, 2) }}</td>
            </tr>
        @endif
        @if($order->tax_total > 0)
            <tr>
                <td>Tax ({{ number_format($order->tax_rate, 2) }}%):</td>
                <td class="text-right">${{ number_format($order->tax_total, 2) }}</td>
            </tr>
        @endif
        @if($order->shipping_total > 0)
            <tr>
                <td>Shipping:</td>
                <td class="text-right">${{ number_format($order->shipping_total, 2) }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td>TOTAL:</td>
            <td class="text-right">${{ number_format($order->grand_total, 2) }}</td>
        </tr>
    </table>

    @if($order->notes)
        <div style="margin-top: 30px;">
            <div class="info-label">Notes:</div>
            <div>{{ $order->notes }}</div>
        </div>
    @endif

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>Colorado Supply & Procurement LLC | contact@cogovsupply.com</p>
        <p>Terms: Payment due upon receipt. Please reference invoice number on all payments.</p>
    </div>
</body>
</html>
