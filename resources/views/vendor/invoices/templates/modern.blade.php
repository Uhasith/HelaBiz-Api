<!DOCTYPE html>
<html lang="en">
    <head>
        <title>{{ $invoice->name }}</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

        <style type="text/css" media="screen">
            * {
                font-family: "DejaVu Sans", sans-serif;
            }

            html {
                margin: 0;
                padding: 0;
            }

            body {
                font-family: "DejaVu Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                font-weight: 400;
                line-height: 1.6;
                color: #1f2937;
                background-color: #ffffff;
                font-size: 10px;
                margin: 0;
                padding: 30px;
            }

            h1, h2, h3, h4, h5, h6 {
                margin-top: 0;
                line-height: 1.2;
            }

            p {
                margin-top: 0;
                margin-bottom: 0.5rem;
            }

            strong {
                font-weight: 600;
            }

            img {
                vertical-align: middle;
                border-style: none;
            }

            table {
                border-collapse: collapse;
                width: 100%;
            }

            /* Header Styles */
            .invoice-header {
                margin-bottom: 40px;
                padding-bottom: 20px;
                border-bottom: 3px solid #3b82f6;
            }

            .logo-section {
                text-align: left;
                margin-bottom: 20px;
            }

            .invoice-title {
                font-size: 32px;
                font-weight: 700;
                color: #1f2937;
                margin: 0;
                letter-spacing: -0.5px;
            }

            .invoice-info {
                display: table;
                width: 100%;
                margin-top: 15px;
            }

            .invoice-info-left {
                display: table-cell;
                width: 50%;
                vertical-align: top;
            }

            .invoice-info-right {
                display: table-cell;
                width: 50%;
                text-align: right;
                vertical-align: top;
            }

            .status-badge {
                display: inline-block;
                padding: 6px 16px;
                background-color: #dbeafe;
                color: #1e40af;
                font-weight: 600;
                border-radius: 20px;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .invoice-meta {
                margin-top: 10px;
            }

            .invoice-meta p {
                margin: 4px 0;
                font-size: 10px;
                color: #6b7280;
            }

            .invoice-meta strong {
                color: #1f2937;
            }

            /* Party Information */
            .parties-section {
                margin: 30px 0;
                display: table;
                width: 100%;
            }

            .party-box {
                display: table-cell;
                width: 48%;
                padding: 20px;
                background-color: #f9fafb;
                border-radius: 8px;
                vertical-align: top;
            }

            .party-gap {
                display: table-cell;
                width: 4%;
            }

            .party-header {
                font-size: 12px;
                font-weight: 700;
                color: #3b82f6;
                text-transform: uppercase;
                margin-bottom: 12px;
                letter-spacing: 0.5px;
            }

            .party-name {
                font-size: 13px;
                font-weight: 700;
                color: #1f2937;
                margin-bottom: 8px;
            }

            .party-details {
                font-size: 9px;
                color: #6b7280;
                line-height: 1.6;
            }

            .party-details p {
                margin: 3px 0;
            }

            /* Items Table */
            .items-table {
                width: 100%;
                margin: 30px 0;
                border-radius: 8px;
                overflow: hidden;
            }

            .items-table thead {
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            }

            .items-table thead th {
                color: #ffffff;
                font-weight: 600;
                font-size: 10px;
                text-transform: uppercase;
                padding: 12px 10px;
                text-align: left;
                letter-spacing: 0.5px;
            }

            .items-table thead th.text-right {
                text-align: right;
            }

            .items-table tbody tr {
                border-bottom: 1px solid #e5e7eb;
            }

            .items-table tbody tr:last-child {
                border-bottom: none;
            }

            .items-table tbody tr:nth-child(even) {
                background-color: #f9fafb;
            }

            .items-table tbody td {
                padding: 12px 10px;
                font-size: 9px;
                color: #374151;
            }

            .items-table tbody td.text-right {
                text-align: right;
            }

            .item-title {
                font-weight: 600;
                color: #1f2937;
                font-size: 10px;
            }

            .item-description {
                color: #6b7280;
                font-size: 8px;
                margin-top: 2px;
            }

            /* Totals Section */
            .totals-section {
                margin-top: 30px;
                display: table;
                width: 100%;
            }

            .totals-left {
                display: table-cell;
                width: 50%;
                vertical-align: top;
            }

            .totals-right {
                display: table-cell;
                width: 50%;
                vertical-align: top;
            }

            .totals-table {
                width: 100%;
                margin-left: auto;
            }

            .totals-table tr td {
                padding: 8px 10px;
                font-size: 10px;
            }

            .totals-table tr td:first-child {
                color: #6b7280;
                text-align: left;
            }

            .totals-table tr td:last-child {
                text-align: right;
                font-weight: 600;
                color: #1f2937;
            }

            .totals-table tr.total-row {
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            }

            .totals-table tr.total-row td {
                color: #ffffff;
                font-size: 12px;
                font-weight: 700;
                padding: 12px 10px;
            }

            /* Notes Section */
            .notes-section {
                margin-top: 30px;
                padding: 20px;
                background-color: #fffbeb;
                border-left: 4px solid #f59e0b;
                border-radius: 4px;
            }

            .notes-title {
                font-size: 11px;
                font-weight: 700;
                color: #92400e;
                margin-bottom: 8px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .notes-content {
                font-size: 9px;
                color: #78350f;
                line-height: 1.6;
            }

            /* Footer */
            .invoice-footer {
                margin-top: 50px;
                padding-top: 20px;
                border-top: 2px solid #e5e7eb;
                text-align: center;
            }

            .invoice-footer p {
                font-size: 8px;
                color: #9ca3af;
                margin: 3px 0;
            }

            /* Utility Classes */
            .text-right {
                text-align: right;
            }

            .text-center {
                text-align: center;
            }

            .mt-10 {
                margin-top: 10px;
            }

            .mt-20 {
                margin-top: 20px;
            }

            .mb-10 {
                margin-bottom: 10px;
            }
        </style>
    </head>

    <body>
        {{-- Header Section --}}
        <div class="invoice-header">
            @if($invoice->logo)
                <div class="logo-section">
                    <img src="{{ $invoice->getLogo() }}" alt="logo" height="60">
                </div>
            @endif

            <div class="invoice-info">
                <div class="invoice-info-left">
                    <h1 class="invoice-title">{{ strtoupper($invoice->name) }}</h1>
                </div>
                <div class="invoice-info-right">
                    @if($invoice->status)
                        <div class="status-badge">{{ $invoice->status }}</div>
                    @endif
                    <div class="invoice-meta">
                        <p><strong>{{ __('invoices::invoice.serial') }}:</strong> {{ $invoice->getSerialNumber() }}</p>
                        @if($invoice->getCustomData() && isset($invoice->getCustomData()['order_number']))
                            <p><strong>Order No:</strong> {{ $invoice->getCustomData()['order_number'] }}</p>
                        @endif
                        <p><strong>{{ __('invoices::invoice.date') }}:</strong> {{ $invoice->getDate() }}</p>
                        @if($invoice->getPayUntilDate())
                            <p><strong>Due Date:</strong> {{ $invoice->getPayUntilDate() }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Parties Section --}}
        <div class="parties-section">
            <div class="party-box">
                <div class="party-header">{{ __('invoices::invoice.seller') }}</div>
                @if($invoice->seller->name)
                    <div class="party-name">{{ $invoice->seller->name }}</div>
                @endif
                <div class="party-details">
                    @if($invoice->seller->address)
                        <p>{{ $invoice->seller->address }}</p>
                    @endif

                    @foreach($invoice->seller->custom_fields as $key => $value)
                        <p><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</p>
                    @endforeach

                    @if($invoice->seller->phone)
                        <p><strong>{{ __('invoices::invoice.phone') }}:</strong> {{ $invoice->seller->phone }}</p>
                    @endif

                    @if($invoice->seller->code)
                        <p><strong>{{ __('invoices::invoice.code') }}:</strong> {{ $invoice->seller->code }}</p>
                    @endif

                    @if($invoice->seller->vat)
                        <p><strong>{{ __('invoices::invoice.vat') }}:</strong> {{ $invoice->seller->vat }}</p>
                    @endif
                </div>
            </div>

            <div class="party-gap"></div>

            <div class="party-box">
                <div class="party-header">{{ __('invoices::invoice.buyer') }}</div>
                @if($invoice->buyer->name)
                    <div class="party-name">{{ $invoice->buyer->name }}</div>
                @endif
                <div class="party-details">
                    @if($invoice->buyer->address)
                        <p>{{ $invoice->buyer->address }}</p>
                    @endif

                    @foreach($invoice->buyer->custom_fields as $key => $value)
                        <p><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</p>
                    @endforeach

                    @if($invoice->buyer->phone)
                        <p><strong>{{ __('invoices::invoice.phone') }}:</strong> {{ $invoice->buyer->phone }}</p>
                    @endif

                    @if($invoice->buyer->code)
                        <p><strong>{{ __('invoices::invoice.code') }}:</strong> {{ $invoice->buyer->code }}</p>
                    @endif

                    @if($invoice->buyer->vat)
                        <p><strong>{{ __('invoices::invoice.vat') }}:</strong> {{ $invoice->buyer->vat }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th width="50%">{{ __('invoices::invoice.description') }}</th>
                    @if($invoice->hasItemUnits)
                        <th>{{ __('invoices::invoice.units') }}</th>
                    @endif
                    <th class="text-right">{{ __('invoices::invoice.quantity') }}</th>
                    <th class="text-right">{{ __('invoices::invoice.price') }}</th>
                    @if($invoice->hasItemDiscount)
                        <th class="text-right">{{ __('invoices::invoice.discount') }}</th>
                    @endif
                    @if($invoice->hasItemTax)
                        <th class="text-right">{{ __('invoices::invoice.tax') }}</th>
                    @endif
                    <th class="text-right">{{ __('invoices::invoice.sub_total') }}</th>
                </tr>
            </thead>
            <tbody>
                {{-- Items --}}
                @foreach($invoice->items as $item)
                    <tr>
                        <td>
                            <div class="item-title">{{ $item->title }}</div>
                            @if($item->description)
                                <div class="item-description">{{ $item->description }}</div>
                            @endif
                        </td>
                        @if($invoice->hasItemUnits)
                            <td>{{ $item->units }}</td>
                        @endif
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">{{ $invoice->formatCurrency($item->price_per_unit) }}</td>
                        @if($invoice->hasItemDiscount)
                            <td class="text-right">{{ $invoice->formatCurrency($item->discount) }}</td>
                        @endif
                        @if($invoice->hasItemTax)
                            <td class="text-right">{{ $invoice->formatCurrency($item->tax) }}</td>
                        @endif
                        <td class="text-right">{{ $invoice->formatCurrency($item->sub_total_price) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals Section --}}
        <div class="totals-section">
            <div class="totals-left">
                {{-- This space intentionally left for future extensions --}}
            </div>
            <div class="totals-right">
                <table class="totals-table">
                    @if($invoice->taxable_amount)
                        <tr>
                            <td>{{ __('invoices::invoice.sub_total') }}</td>
                            <td>{{ $invoice->formatCurrency($invoice->taxable_amount) }}</td>
                        </tr>
                    @endif
                    @if($invoice->hasItemOrInvoiceDiscount())
                        <tr>
                            <td>{{ __('invoices::invoice.total_discount') }}</td>
                            <td>{{ $invoice->formatCurrency($invoice->total_discount) }}</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td>{{ __('invoices::invoice.total_amount') }}</td>
                        <td>{{ $invoice->formatCurrency($invoice->total_amount) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Notes Section --}}
        @if($invoice->notes)
            <div class="notes-section">
                <div class="notes-title">{{ __('invoices::invoice.notes') }}</div>
                <div class="notes-content">
                    {!! str_replace(
                        'WARRANTY:', 
                        '<strong style="color: #dc2626;">WARRANTY:</strong>', 
                        nl2br(e($invoice->notes))
                    ) !!}
                </div>
            </div>
        @endif

        {{-- Footer --}}
        <div class="invoice-footer">
            <p>Thank you for your business!</p>
            <p>This is a computer-generated invoice and does not require a signature.</p>
        </div>

        <script type="text/php">
            if (isset($pdf) && $PAGE_COUNT > 1) {
                $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
                $size = 10;
                $font = $fontMetrics->getFont("DejaVu Sans");
                $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
                $x = ($pdf->get_width() - $width) / 2;
                $y = $pdf->get_height() - 35;
                $pdf->page_text($x, $y, $text, $font, $size);
            }
        </script>
    </body>
</html>
