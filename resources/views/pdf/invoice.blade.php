<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            background: #ffffff;
        }

        .page {
            padding: 48px;
        }

        /* ── Header ── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 24px;
            border-bottom: 2px solid #1a1a1a;
        }

        .store-name {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: -0.5px;
        }

        .invoice-label {
            text-align: right;
        }

        .invoice-label h1 {
            font-size: 28px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #1a1a1a;
        }

        .invoice-label p {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* ── Meta row ── */
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 32px;
        }

        .meta-block h3 {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .meta-block p {
            font-size: 12px;
            color: #1a1a1a;
            line-height: 1.6;
        }

        .meta-block .value {
            font-weight: 600;
        }

        /* ── Status badge ── */
        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #d1d5db;
            color: #374151;
            background: #f9fafb;
        }

        /* ── Address blocks ── */
        .address-row {
            display: flex;
            gap: 24px;
            margin-bottom: 32px;
        }

        .address-block {
            flex: 1;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 16px;
        }

        .address-block h3 {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .address-block p {
            font-size: 11px;
            color: #374151;
            line-height: 1.7;
        }

        .address-block .name {
            font-weight: 600;
            font-size: 12px;
            color: #1a1a1a;
        }

        /* ── Items table ── */
        .items-section {
            margin-bottom: 0;
        }

        .items-section h3 {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            background: #1a1a1a;
            color: #ffffff;
        }

        thead th {
            padding: 10px 12px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-weight: 600;
        }

        thead th.text-right {
            text-align: right;
        }

        tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody td {
            padding: 10px 12px;
            font-size: 11px;
            color: #374151;
            vertical-align: top;
        }

        tbody td.text-right {
            text-align: right;
        }

        tbody td.sku {
            font-size: 10px;
            color: #9ca3af;
            font-family: monospace;
        }

        tbody td.product-name {
            font-weight: 500;
            color: #1a1a1a;
        }

        /* ── Totals ── */
        .totals-wrapper {
            display: flex;
            justify-content: flex-end;
            margin-top: 16px;
        }

        .totals-table {
            width: 260px;
        }

        .totals-table td {
            padding: 5px 0;
            font-size: 12px;
        }

        .totals-table td.label {
            color: #6b7280;
        }

        .totals-table td.amount {
            text-align: right;
            color: #1a1a1a;
        }

        .totals-table td.discount {
            color: #16a34a;
        }

        .totals-table tr.grand-total td {
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #1a1a1a;
            padding-top: 8px;
            margin-top: 4px;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 48px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="page">

        {{-- ── Header ── --}}
        <div class="header">
            <div>
                <p class="store-name">{{ config('app.name') }}</p>
                @if ($settings->gst_number)
                    <p style="font-size:10px;color:#6b7280;margin-top:4px;">GST/HST No: {{ $settings->gst_number }}</p>
                @endif
            </div>
            <div class="invoice-label">
                <h1>Invoice</h1>
                <p>{{ $order->order_number }}</p>
                <p>{{ $order->created_at->format('F j, Y') }}</p>
            </div>
        </div>

        {{-- ── Order meta ── --}}
        <div class="meta-row">
            <div class="meta-block">
                <h3>Payment Method</h3>
                <p class="value">{{ ucwords(str_replace('_', ' ', $order->payment_gateway ?? 'N/A')) }}</p>
            </div>
            <div class="meta-block">
                <h3>Payment Status</h3>
                <p class="value">{{ ucfirst($order->payment_status ?? 'Pending') }}</p>
            </div>
            <div class="meta-block">
                <h3>Order Status</h3>
                <span class="status-badge">{{ $order->status->label() }}</span>
            </div>
            @if ($order->paid_at)
                <div class="meta-block">
                    <h3>Paid On</h3>
                    <p class="value">{{ $order->paid_at->format('F j, Y') }}</p>
                </div>
            @endif
        </div>

        {{-- ── Bill To / Ship To ── --}}
        <div class="address-row">
            <div class="address-block">
                <h3>Bill To</h3>
                <p class="name">{{ $order->customer->user->name }}</p>
                <p>{{ $order->customer->user->email }}</p>
                @if ($order->customer->phone)
                    <p>{{ $order->customer->phone }}</p>
                @endif
            </div>
            <div class="address-block">
                <h3>Ship To</h3>
                <p class="name">{{ $order->shipping_name }}</p>
                <p>{{ $order->shipping_address_line1 }}</p>
                @if ($order->shipping_address_line2)
                    <p>{{ $order->shipping_address_line2 }}</p>
                @endif
                <p>{{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postcode }}</p>
                <p>{{ $order->shipping_country }}</p>
            </div>
        </div>

        {{-- ── Line Items ── --}}
        <div class="items-section">
            <h3>Items</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="product-name">{{ $item->product_name }}</td>
                            <td class="sku">{{ $item->product_sku ?? '—' }}</td>
                            <td class="text-right">{{ $settings->currency }} {{ number_format($item->unit_price / 100, 2) }}</td>
                            <td class="text-right">{{ $item->quantity }}</td>
                            <td class="text-right">{{ $settings->currency }} {{ number_format($item->subtotal / 100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ── Totals ── --}}
        <div class="totals-wrapper">
            <table class="totals-table">
                <tbody>
                    <tr>
                        <td class="label">Subtotal</td>
                        <td class="amount">{{ $settings->currency }} {{ number_format($order->subtotal / 100, 2) }}</td>
                    </tr>
                    @if ($order->discount_amount > 0)
                        <tr>
                            <td class="label discount">Discount</td>
                            <td class="amount discount">− {{ $settings->currency }} {{ number_format($order->discount_amount / 100, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">Shipping</td>
                        <td class="amount">
                            @if ($order->shipping_amount > 0)
                                {{ $settings->currency }} {{ number_format($order->shipping_amount / 100, 2) }}
                            @else
                                Free
                            @endif
                        </td>
                    </tr>
                    @if ($order->tax_amount > 0)
                        @if ($order->tax_breakdown && count($order->tax_breakdown) > 0)
                            @foreach ($order->tax_breakdown as $line)
                                <tr>
                                    <td class="label">{{ $line['name'] }}{{ isset($line['name_fr']) && $line['name_fr'] ? ' / '.$line['name_fr'] : '' }} ({{ $line['rate'] }}%)</td>
                                    <td class="amount">{{ $settings->currency }} {{ number_format($line['amount_cents'] / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td class="label">Tax ({{ $settings->tax_rate }}%)</td>
                                <td class="amount">{{ $settings->currency }} {{ number_format($order->tax_amount / 100, 2) }}</td>
                            </tr>
                        @endif
                    @endif
                    <tr class="grand-total">
                        <td class="label">Total</td>
                        <td class="amount">{{ $settings->currency }} {{ number_format($order->total_amount / 100, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- ── Footer ── --}}
        <div class="footer">
            <p>Thank you for your order. If you have any questions, please contact us.</p>
        </div>

    </div>
</body>
</html>
