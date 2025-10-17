<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #1d4ed8;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 30px 20px;
        }
        .order-details {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #1d4ed8;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Confirmation</h1>
        </div>
        <div class="content">
            <p>Dear {{ $order->customer_name }},</p>

            <p>Thank you for your order! We're pleased to confirm that we've received your payment and your order is being processed.</p>

            <div class="order-details">
                <h3>Order Details</h3>
                <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
                <p><strong>Order Date:</strong> {{ $order->created_at->format('F d, Y') }}</p>
                <p><strong>Total Amount:</strong> ${{ number_format($order->grand_total, 2) }}</p>
                <p><strong>Payment Status:</strong> {{ $order->payment_status->label() }}</p>
            </div>

            <p>Your invoice is attached to this email for your records.</p>

            @if($order->notes)
                <p><strong>Notes:</strong><br>{{ $order->notes }}</p>
            @endif

            <p>If you have any questions about your order, please don't hesitate to contact us.</p>

            <p>Best regards,<br>
            <strong>Colorado Supply & Procurement LLC</strong></p>
        </div>
        <div class="footer">
            <p>Colorado Supply & Procurement LLC<br>
            Email: contact@cogovsupply.com<br>
            This is an automated email. Please do not reply directly to this message.</p>
        </div>
    </div>
</body>
</html>
