<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
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
            'business_name' => 'DemoStore',
            'email' => 'info@dayzsolutions.com',
            'phone' => '+94 77 123 4567',
            'address' => '123 Main Street, Business Park',
            'city' => 'Colombo',
            'country' => 'Sri Lanka',
            'currency' => 'LKR',
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
                'sku' => 'SKU-'.str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'barcode' => '123456789'.str_pad($index, 4, '0', STR_PAD_LEFT),
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
                'name' => $isCompany ? $companies[array_rand($companies)]." ($i)" : fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'phone' => '+94 '.fake()->numerify('## ### ####'),
                'address' => fake()->city().', Sri Lanka',
            ]);
        }

        // Create 50 orders with items
        $orderStatuses = ['pending', 'processing', 'completed', 'cancelled'];
        for ($i = 1; $i <= 50; $i++) {
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

            // Generate random date within past 12 months
            $randomDate = now()->subDays(fake()->numberBetween(0, 365));

            DB::transaction(function () use ($tenant, $customer, $status, $subtotal, $tax, $discount, $total, $items, $i, $randomDate) {
                $order = Order::create([
                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'order_number' => 'ORD-'.$randomDate->format('Ymd').'-'.str_pad($i, 4, '0', STR_PAD_LEFT),
                    'order_date' => $randomDate,
                    'status' => $status,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'discount' => $discount,
                    'total' => $total,
                    'notes' => fake()->boolean(40) ? fake()->sentence() : null,
                    'created_at' => $randomDate,
                    'updated_at' => $randomDate,
                ]);

                foreach ($items as $item) {
                    OrderItem::create(array_merge(['order_id' => $order->id], $item));
                }
            });
        }

        $this->command->info('✅ Seeded successfully:');
        $this->command->info('   - 1 Tenant');
        $this->command->info('   - 1 User');
        $this->command->info('   - 15 Products');
        $this->command->info('   - 15 Customers');
        $this->command->info('   - 50 Orders (with items spanning past 12 months)');
    }
}
