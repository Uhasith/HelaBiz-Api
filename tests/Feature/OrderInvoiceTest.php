<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

test('authenticated user can generate invoice from order', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'subtotal' => 100.00,
        'tax' => 10.00,
        'discount' => 5.00,
        'total' => 105.00,
    ]);

    $order->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => 2,
        'unit_price' => 50.00,
        'total' => 100.00,
    ]);

    actingAs($user)
        ->postJson('/api/orders/invoices', [
            'order_id' => $order->id,
        ])
        ->assertCreated()
        ->assertJsonStructure([
            'message',
            'invoice' => [
                'id',
                'invoice_number',
                'invoice_date',
                'due_date',
                'status',
                'subtotal',
                'tax',
                'discount',
                'total',
                'items',
            ],
            'pdf_url',
        ]);

    expect(Invoice::where('order_id', $order->id)->exists())->toBeTrue();
});

test('cannot generate duplicate invoice for same order', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
    ]);

    // Create first invoice
    Invoice::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'order_id' => $order->id,
    ]);

    // Try to create duplicate
    actingAs($user)
        ->postJson('/api/orders/invoices', [
            'order_id' => $order->id,
        ])
        ->assertStatus(422)
        ->assertJson([
            'message' => 'Invoice already exists for this order',
        ]);
});

test('user can view invoice details', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $order = Order::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
    ]);

    $invoice = Invoice::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'order_id' => $order->id,
    ]);

    actingAs($user)
        ->getJson("/api/orders/invoices/{$invoice->id}")
        ->assertOk()
        ->assertJsonStructure([
            'id',
            'invoice_number',
            'invoice_date',
            'due_date',
            'status',
            'subtotal',
            'tax',
            'discount',
            'total',
            'customer',
            'items',
            'order',
            'tenant',
        ]);
});

test('user cannot access invoice from different tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);

    $customer2 = Customer::factory()->create(['tenant_id' => $tenant2->id]);
    $order2 = Order::factory()->create([
        'tenant_id' => $tenant2->id,
        'customer_id' => $customer2->id,
    ]);

    $invoice2 = Invoice::factory()->create([
        'tenant_id' => $tenant2->id,
        'customer_id' => $customer2->id,
        'order_id' => $order2->id,
    ]);

    actingAs($user1)
        ->getJson("/api/orders/invoices/{$invoice2->id}")
        ->assertNotFound();
});

test('unauthenticated user cannot generate invoice', function () {
    postJson('/api/orders/invoices', [
        'order_id' => 1,
    ])->assertUnauthorized();
});

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
