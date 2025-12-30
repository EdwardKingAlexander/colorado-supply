<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Quote {{ $quote['number'] ?? '' }}</title>
</head>

<body style="font-family: 'Inter', Helvetica, Arial, sans-serif; color:#111827;">
    <h2 style="margin-bottom: 8px;">Colorado Supply & Procurement LLC</h2>
    <p style="margin: 0 0 16px 0;">Attached is your quote {{ $quote['number'] ?? '' }} dated {{ $quote['date'] ?? '' }}.</p>

    <p style="margin: 0 0 8px 0;">Summary:</p>
    <ul style="margin:0 0 16px 20px; padding:0;">
        <li><strong>Items:</strong> {{ count($quote['items'] ?? []) }}</li>
        <li><strong>Total:</strong> ${{ number_format($quote['total'] ?? 0, 2) }}</li>
    </ul>

    <p style="margin: 0 0 16px 0;">If you have any questions, reply to this email and our team will assist.</p>

    <p style="margin: 0;">Thank you,<br>Colorado Supply & Procurement LLC</p>
</body>

</html>
