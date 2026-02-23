<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create tenant
        $tenant = Tenant::create([
            'name' => 'DemoStore',
            'email' => 'info@dayzsolutions.com',
            'phone' => '+94 77 123 4567',
            'address' => 'Colombo, Sri Lanka',
        ]);

        // Create user
        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Demo User',
            'email' => 'info@dayzsolutions.com',
            'password' => Hash::make('elakiri123'),
        ]);

        // Create 15 products
        $productNames = [
            'Laptop - Dell XPS 15',
            'MacBook Pro 16"',
            'iPhone 15 Pro',
            'Samsung Galaxy S24',
            'Wireless Mouse',
            'Mechanical Keyboard',
            'USB-C Cable',
            'HDMI Cable 2m',
            '27" Monitor - LG',
            'Webcam HD 1080p',
            'Laptop Bag',
            'Wireless Earbuds',
            'Phone Case',
            'Screen Protector',
            'External SSD 1TB',
        ];

        $products = [];
        foreach ($productNames as $index => $name) {
            $products[] = Product::create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'sku' => 'SKU-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'barcode' => '123456789' . str_pad($index, 4, '0', STR_PAD_LEFT),
                'description' => fake()->sentence(),
                'cost_price' => fake()->numberBetween(500, 100000),
                'selling_price' => fake()->numberBetween(1000, 150000),
                'stock_quantity' => fake()->numberBetween(0, 100),
                'low_stock_alert' => fake()->numberBetween(5, 15),
            ]);
        }

        // Create 15 customers
        $customers = [];
        $companies = ['Tech Solutions', 'ABC Trading', 'XYZ Enterprises', 'Global Systems', 'Digital Hub'];
        for ($i = 1; $i <= 15; $i++) {
            $isCompany = $i % 3 === 0;
            $customers[] = Customer::create([
                'tenant_id' => $tenant->id,
                'name' => $isCompany ? $companies[array_rand($companies)] . " ($i)" : fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'phone' => '+94 ' . fake()->numerify('## ### ####'),
                'address' => fake()->city() . ', Sri Lanka',
            ]);
        }

        // Create 15 orders with items
        $orderStatuses = ['pending', 'processing', 'completed', 'cancelled'];
        for ($i = 1; $i <= 15; $i++) {
            $customer = $customers[array_rand($customers)];
            $status = $orderStatuses[array_rand($orderStatuses)];

            $subtotal = 0;
            $items = [];

            // Each order has 1-5 items
            $itemCount = fake()->numberBetween(1, 5);
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $quantity = fake()->numberBetween(1, 5);
                $unitPrice = $product->selling_price;
                $total = $quantity * $unitPrice;
                $subtotal += $total;

                $items[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                ];
            }

            $tax = $subtotal * 0.1; // 10% tax
            $discount = fake()->boolean(30) ? fake()->numberBetween(100, 5000) : 0;
            $total = $subtotal + $tax - $discount;

            DB::transaction(function () use ($tenant, $customer, $status, $subtotal, $tax, $discount, $total, $items, $i) {
                $order = Order::create([
                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'order_number' => 'ORD-' . now()->format('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'order_date' => now()->subDays(fake()->numberBetween(0, 30)),
                    'status' => $status,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'discount' => $discount,
                    'total' => $total,
                    'notes' => fake()->boolean(40) ? fake()->sentence() : null,
                ]);

                foreach ($items as $item) {
                    OrderItem::create(array_merge(['order_id' => $order->id], $item));
                }
            });
        }

        // Create 12 invoices with items
        $invoiceStatuses = ['draft', 'sent', 'paid', 'overdue', 'cancelled'];
        for ($i = 1; $i <= 12; $i++) {
            $customer = $customers[array_rand($customers)];
            $status = $invoiceStatuses[array_rand($invoiceStatuses)];

            $subtotal = 0;
            $items = [];

            // Each invoice has 1-4 items
            $itemCount = fake()->numberBetween(1, 4);
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $quantity = fake()->numberBetween(1, 5);
                $unitPrice = $product->selling_price;
                $total = $quantity * $unitPrice;
                $subtotal += $total;

                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                ];
            }

            $taxAmount = $subtotal * 0.1;
            $discountAmount = fake()->boolean(20) ? fake()->numberBetween(100, 3000) : 0;
            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            DB::transaction(function () use ($tenant, $customer, $status, $subtotal, $taxAmount, $discountAmount, $totalAmount, $items, $i) {
                $invoice = Invoice::create([
                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'order_id' => null,
                    'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'invoice_date' => now()->subDays(fake()->numberBetween(0, 25)),
                    'due_date' => now()->addDays(fake()->numberBetween(15, 45)),
                    'status' => $status,
                    'subtotal' => $subtotal,
                    'tax' => $taxAmount,
                    'discount' => $discountAmount,
                    'total' => $totalAmount,
                    'notes' => fake()->boolean(30) ? fake()->sentence() : null,
                ]);

                foreach ($items as $item) {
                    InvoiceItem::create(array_merge(['invoice_id' => $invoice->id], $item));
                }
            });
        }

        // Create 10 quotations with items
        $quotationStatuses = ['draft', 'sent', 'accepted', 'rejected', 'expired'];
        for ($i = 1; $i <= 10; $i++) {
            $customer = $customers[array_rand($customers)];
            $status = $quotationStatuses[array_rand($quotationStatuses)];

            $subtotal = 0;
            $items = [];

            // Each quotation has 1-6 items
            $itemCount = fake()->numberBetween(1, 6);
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $quantity = fake()->numberBetween(1, 10);
                $unitPrice = $product->selling_price;
                $total = $quantity * $unitPrice;
                $subtotal += $total;

                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                ];
            }

            $taxAmount = $subtotal * 0.1;
            $discountAmount = fake()->boolean(50) ? fake()->numberBetween(500, 10000) : 0;
            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            DB::transaction(function () use ($tenant, $customer, $status, $subtotal, $taxAmount, $discountAmount, $totalAmount, $items, $i) {
                $quotation = Quotation::create([
                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'quotation_number' => 'QUO-' . now()->format('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'quotation_date' => now()->subDays(fake()->numberBetween(0, 20)),
                    'valid_until' => now()->addDays(fake()->numberBetween(20, 60)),
                    'status' => $status,
                    'subtotal' => $subtotal,
                    'tax' => $taxAmount,
                    'discount' => $discountAmount,
                    'total' => $totalAmount,
                    'notes' => fake()->boolean(40) ? fake()->sentence() : null,
                ]);

                foreach ($items as $item) {
                    QuotationItem::create(array_merge(['quotation_id' => $quotation->id], $item));
                }
            });
        }

        $this->command->info('✅ Seeded successfully:');
        $this->command->info("   - 1 Tenant");
        $this->command->info("   - 1 User");
        $this->command->info("   - 15 Products");
        $this->command->info("   - 15 Customers");
        $this->command->info("   - 15 Orders (with items)");
        $this->command->info("   - 12 Invoices (with items)");
        $this->command->info("   - 10 Quotations (with items)");
    }
}
