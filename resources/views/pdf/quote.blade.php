<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Quote {{ $quote['number'] ?? '' }}</title>
    <style>
        @page {
            margin: 32px 40px;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Helvetica, Arial, sans-serif;
            color: #111827;
            font-size: 12px;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            height: 48px;
            margin: 0 auto 8px;
        }

        .text-xs {
            font-size: 10px;
            color: #6b7280;
        }

        .section-title {
            font-size: 13px;
            font-weight: 600;
            margin-top: 20px;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #4b5563;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
        }

        th {
            background: #f3f4f6;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #374151;
        }

        td {
            font-size: 12px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary {
            margin-top: 16px;
            width: 40%;
            float: right;
        }

        .summary table {
            margin-top: 0;
        }

        .summary td {
            border: none;
            padding: 4px 0;
        }

        .summary .label {
            color: #6b7280;
        }

        .summary .value {
            font-weight: 600;
            color: #111827;
        }

        .footer {
            margin-top: 40px;
            font-size: 10px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        @if ($logo)
            <img src="{{ $logo }}" alt="Colorado Supply Logo">
        @endif
        <h1 style="font-size: 18px; font-weight: 700; margin: 0;">
            {{ $quote['company']['name'] ?? config('app.name') }}
        </h1>
        <p class="text-xs" style="margin: 4px 0;">
            CAGE: {{ $quote['company']['cage'] ?? 'N/A' }} • DUNS: {{ $quote['company']['duns'] ?? 'N/A' }} •
            {{ $quote['company']['email'] ?? '' }}
        </p>
        <p class="text-xs" style="margin: 0;">
            {{ $quote['company']['address'] ?? '' }} • {{ $quote['company']['phone'] ?? '' }} •
            {{ $quote['company']['website'] ?? '' }}
        </p>
    </div>

    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
        <div>
            <p class="section-title" style="margin-top: 0;">Quote</p>
            <p style="margin: 0;"><strong>Quote #:</strong> {{ $quote['number'] }}</p>
            <p style="margin: 0;"><strong>Date:</strong> {{ $quote['date'] }}</p>
        </div>
        <div>
            <p class="section-title" style="margin-top: 0;">Bill To</p>
            <p style="margin: 0;">
                {{ $quote['customer']['name'] ?? 'Procurement Department' }}
            </p>
            @if (!empty($quote['customer']['company']))
                <p style="margin: 0;">{{ $quote['customer']['company'] }}</p>
            @endif
            @if (!empty($quote['customer']['email']))
                <p style="margin: 0;">{{ $quote['customer']['email'] }}</p>
            @endif
        </div>
    </div>

    <div>
        <p class="section-title">Line Items</p>

        @if (!empty($quote['items_by_location']) && count($quote['items_by_location']) > 1)
            {{-- Display grouped by location when multiple locations --}}
            @foreach ($quote['items_by_location'] as $locationGroup)
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 11px; font-weight: 600; color: #374151; margin: 8px 0 4px; padding: 4px 8px; background: #f9fafb; border-left: 3px solid #6366f1;">
                        {{ $locationGroup['location_name'] }}
                    </p>
                    <table>
                        <thead>
                            <tr>
                                <th class="text-left">Description</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($locationGroup['items'] as $item)
                                <tr>
                                    <td>{{ $item['name'] }}</td>
                                    <td class="text-center">{{ $item['quantity'] }}</td>
                                    <td class="text-right">${{ number_format($item['price'], 2) }}</td>
                                    <td class="text-right">${{ number_format($item['price'] * $item['quantity'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        @else
            {{-- Display ungrouped when single location or no location data --}}
            <table>
                <thead>
                    <tr>
                        <th class="text-left">Description</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quote['items'] as $item)
                        <tr>
                            <td>{{ $item['name'] }}</td>
                            <td class="text-center">{{ $item['quantity'] }}</td>
                            <td class="text-right">${{ number_format($item['price'], 2) }}</td>
                            <td class="text-right">${{ number_format($item['price'] * $item['quantity'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="summary">
        <table>
            <tr>
                <td class="label">Subtotal</td>
                <td class="value text-right">${{ number_format($quote['subtotal'], 2) }}</td>
            </tr>
            @if (($quote['tax'] ?? 0) > 0)
                <tr>
                    <td class="label">Tax</td>
                    <td class="value text-right">${{ number_format($quote['tax'], 2) }}</td>
                </tr>
            @endif
            <tr>
                <td class="label">Total</td>
                <td class="value text-right">${{ number_format($quote['total'], 2) }}</td>
            </tr>
        </table>
    </div>

    @if (!empty($quote['notes']))
        <div style="margin-top: 80px;">
            <p class="section-title">Notes</p>
            <p style="margin: 0;">{{ $quote['notes'] }}</p>
        </div>
    @endif

    <div class="footer">
        Prices valid for 30 days from quote date. Standard Net-30 terms apply. All items subject to availability and
        applicable freight charges. Please reference quote #{{ $quote['number'] }} on your purchase order.
    </div>
</body>

</html>
