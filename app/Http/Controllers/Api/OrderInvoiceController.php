<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice as InvoiceModel;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use LaravelDaily\Invoices\Classes\Party;
use LaravelDaily\Invoices\Invoice;

class OrderInvoiceController extends Controller
{
    /**
     * Generate and store an invoice from an order.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::with(['customer', 'items', 'tenant'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($validated['order_id']);

        // Check if invoice already exists for this order
        $existingInvoice = InvoiceModel::where('order_id', $order->id)->first();
        if ($existingInvoice) {
            return response()->json([
                'message' => 'Invoice already exists for this order',
                'invoice' => $existingInvoice,
            ], 422);
        }

        return DB::transaction(function () use ($order) {
            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber();

            // Create invoice record in database
            $invoiceRecord = InvoiceModel::create([
                'tenant_id' => $order->tenant_id,
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => now(),
                'due_date' => now()->addDays(config('invoices.date.pay_until_days', 7)),
                'status' => 'draft',
                'subtotal' => $order->subtotal,
                'tax' => $order->tax,
                'discount' => $order->discount,
                'total' => $order->total,
                'notes' => $order->notes,
            ]);

            // Create invoice items
            foreach ($order->items as $orderItem) {
                $invoiceRecord->items()->create([
                    'product_id' => $orderItem->product_id,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'total' => $orderItem->total,
                ]);
            }

            // Generate PDF invoice
            $pdfInvoice = $this->generatePdfInvoice($order, $invoiceNumber);

            // Get PDF content as string
            $pdfContent = $pdfInvoice->stream()->getContent();

            // Save to temporary file
            $tempPath = sys_get_temp_dir().'/invoice_'.$invoiceNumber.'_'.uniqid().'.pdf';
            file_put_contents($tempPath, $pdfContent);

            try {
                // Add to media collection
                $invoiceRecord->addMedia($tempPath)
                    ->usingFileName("invoice_{$invoiceNumber}.pdf")
                    ->toMediaCollection('invoice_pdf');

                return response()->json([
                    'message' => 'Invoice generated successfully',
                    'invoice' => $invoiceRecord->load('items'),
                    'pdf_url' => $invoiceRecord->getFirstMediaUrl('invoice_pdf'),
                ], 201);
            } finally {
                // Clean up temp file
                @unlink($tempPath);
            }
        });
    }

    /**
     * Display the specified invoice.
     */
    public function show(Request $request, int $invoiceId): JsonResponse
    {
        $invoice = InvoiceModel::with(['customer', 'items', 'order', 'tenant'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($invoiceId);

        return response()->json([
            'id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'customer_id' => $invoice->customer_id,
            'order_id' => $invoice->order_id,
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date,
            'due_date' => $invoice->due_date,
            'status' => $invoice->status,
            'subtotal' => $invoice->subtotal,
            'tax' => $invoice->tax,
            'discount' => $invoice->discount,
            'total' => $invoice->total,
            'notes' => $invoice->notes,
            'pdf_url' => $invoice->getFirstMediaUrl('invoice_pdf'),
            'customer' => $invoice->customer,
            'items' => $invoice->items,
            'order' => $invoice->order,
            'tenant' => $invoice->tenant,
            'created_at' => $invoice->created_at,
            'updated_at' => $invoice->updated_at,
        ]);
    }

    /**
     * Download the invoice PDF.
     */
    public function download(Request $request, int $invoiceId)
    {
        $invoice = InvoiceModel::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($invoiceId);

        $media = $invoice->getFirstMedia('invoice_pdf');

        if (! $media) {
            return response()->json(['message' => 'Invoice PDF not found'], 404);
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    /**
     * Get the invoice PDF URL for streaming/preview.
     */
    public function stream(Request $request, int $invoiceId): JsonResponse
    {
        $invoice = InvoiceModel::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($invoiceId);

        $pdfUrl = $invoice->getFirstMediaUrl('invoice_pdf');

        if (! $pdfUrl) {
            return response()->json(['message' => 'Invoice PDF not found'], 404);
        }

        return response()->json([
            'pdf_url' => $pdfUrl,
            'invoice_number' => $invoice->invoice_number,
        ]);
    }

    /**
     * Delete the invoice PDF.
     */
    public function deletePdf(Request $request, int $invoiceId): JsonResponse
    {
        $invoice = InvoiceModel::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($invoiceId);

        $invoice->clearMediaCollection('invoice_pdf');

        return response()->json([
            'message' => 'Invoice PDF deleted successfully',
        ]);
    }

    /**
     * Regenerate the invoice PDF.
     */
    public function regenerate(Request $request, int $invoiceId): JsonResponse
    {
        $invoice = InvoiceModel::with(['customer', 'items', 'order.items', 'tenant'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($invoiceId);

        $order = $invoice->order;

        // Generate PDF invoice
        $pdfInvoice = $this->generatePdfInvoice($order, $invoice->invoice_number);

        // Get PDF content as string
        $pdfContent = $pdfInvoice->stream()->getContent();

        // Save to temporary file
        $tempPath = sys_get_temp_dir().'/invoice_'.$invoice->invoice_number.'_'.uniqid().'.pdf';
        file_put_contents($tempPath, $pdfContent);

        try {
            // Clear existing PDF and add new one
            $invoice->clearMediaCollection('invoice_pdf');
            $invoice->addMedia($tempPath)
                ->usingFileName("invoice_{$invoice->invoice_number}.pdf")
                ->toMediaCollection('invoice_pdf');

            return response()->json([
                'message' => 'Invoice PDF regenerated successfully',
                'pdf_url' => $invoice->getFirstMediaUrl('invoice_pdf'),
            ]);
        } finally {
            // Clean up temp file
            @unlink($tempPath);
        }
    }

    /**
     * Generate PDF invoice from order data.
     */
    private function generatePdfInvoice(Order $order, string $invoiceNumber): Invoice
    {
        $tenant = $order->tenant;
        $customer = $order->customer;

        // Create seller (business) party
        $seller = new Party([
            'name' => $tenant->business_name,
            'phone' => $tenant->phone,
            'custom_fields' => [
                'email' => $tenant->email,
                'address' => $tenant->address,
                'city' => $tenant->city,
                'country' => $tenant->country,
            ],
        ]);

        // Create buyer (customer) party
        $buyer = new Party([
            'name' => $customer->name,
            'phone' => $customer->phone,
            'custom_fields' => [
                'email' => $customer->email,
                'address' => $customer->address,
            ],
        ]);

        // Create invoice items
        $items = [];
        foreach ($order->items as $orderItem) {
            $items[] = InvoiceItem::make($orderItem->product_name)
                ->description($orderItem->product_sku ? "SKU: {$orderItem->product_sku}" : '')
                ->pricePerUnit($orderItem->unit_price)
                ->quantity($orderItem->quantity);
        }

        // Build invoice
        $invoice = Invoice::make('invoice')
            ->template('modern')
            ->series('INV')
            ->sequence($this->extractSequence($invoiceNumber))
            ->serialNumberFormat('{SERIES}-{SEQUENCE}')
            ->seller($seller)
            ->buyer($buyer)
            ->date($order->order_date)
            ->dateFormat('Y-m-d')
            ->payUntilDays(config('invoices.date.pay_until_days', 7))
            ->currencyCode($tenant->currency ?? 'USD')
            ->currencySymbol($this->getCurrencySymbol($tenant->currency ?? 'USD'))
            ->addItems($items)
            ->filename("invoice_{$invoiceNumber}");

        // Add discount if present
        if ($order->discount > 0) {
            $invoice->totalDiscount($order->discount);
        }

        // Add tax if present
        // if ($order->tax > 0) {
        //     $invoice->totalTaxes($order->tax);
        // }

        // Combine notes with warranty information
        $notesArray = [];
        
        if ($order->warranty_period && $order->warranty_unit) {
            $notesArray[] = "WARRANTY: {$order->warranty_period} {$order->warranty_unit}";
        }
        
        if ($order->notes) {
            $notesArray[] = $order->notes;
        }
        
        if (!empty($notesArray)) {
            $invoice->notes(implode("<br><br>", $notesArray));
        }

        // Add logo if available (check Spatie Media first)
        if ($tenant->hasMedia('logo')) {
            $media = $tenant->getFirstMedia('logo');
            if ($media) {
                $logoPath = $media->getPath();
                if (file_exists($logoPath)) {
                    $invoice->logo($logoPath);
                }
            }
        } elseif ($tenant->logo) {
            // Fallback to traditional logo column
            $logoPath = storage_path('app/public/'.$tenant->logo);
            if (file_exists($logoPath)) {
                $invoice->logo($logoPath);
            }
        }

        return $invoice;
    }

    /**
     * Generate unique invoice number.
     */
    private function generateInvoiceNumber(): string
    {
        $lastInvoice = InvoiceModel::orderBy('id', 'desc')->first();
        $sequence = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, 4)) + 1 : 1;

        return 'INV-'.str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Extract sequence number from invoice number.
     */
    private function extractSequence(string $invoiceNumber): int
    {
        return (int) str_replace('INV-', '', $invoiceNumber);
    }

    /**
     * Get currency symbol.
     */
    private function getCurrencySymbol(string $currency): string
    {
        return match (strtoupper($currency)) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'INR' => '₹',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'CHF' => 'CHF',
            'CNY' => '¥',
            'LKR' => 'Rs',
            default => $currency,
        };
    }
}
