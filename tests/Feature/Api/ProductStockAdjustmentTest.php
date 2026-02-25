<?php

use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;

test('authenticated user can increase product stock', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'stock_quantity' => 50,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/products/{$product->id}/adjust-stock", [
            'adjustment' => 10,
            'note' => 'Restocked from supplier',
        ]);

    $response->assertOk()
        ->assertJson([
            'id' => $product->id,
            'stock_quantity' => 60,
        ]);

    expect($product->fresh()->stock_quantity)->toBe(60);
});

test('authenticated user can decrease product stock', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'stock_quantity' => 50,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/products/{$product->id}/adjust-stock", [
            'adjustment' => -15,
            'note' => 'Damaged items removed',
        ]);

    $response->assertOk()
        ->assertJson([
            'id' => $product->id,
            'stock_quantity' => 35,
        ]);

    expect($product->fresh()->stock_quantity)->toBe(35);
});

test('adjustment cannot result in negative stock', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'stock_quantity' => 10,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/products/{$product->id}/adjust-stock", [
            'adjustment' => -15,
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Insufficient stock',
            'errors' => [
                'adjustment' => ['Cannot reduce stock below zero'],
            ],
        ]);

    expect($product->fresh()->stock_quantity)->toBe(10);
});

test('adjustment must be a non-zero integer', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'stock_quantity' => 50,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/products/{$product->id}/adjust-stock", [
            'adjustment' => 0,
        ]);

    $response->assertStatus(422);

    expect($product->fresh()->stock_quantity)->toBe(50);
});

test('adjustment must be an integer', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'stock_quantity' => 50,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/products/{$product->id}/adjust-stock", [
            'adjustment' => 'invalid',
        ]);

    $response->assertStatus(422);

    expect($product->fresh()->stock_quantity)->toBe(50);
});

test('user cannot adjust stock for product from different tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant1->id]);
    $product = Product::factory()->create([
        'tenant_id' => $tenant2->id,
        'stock_quantity' => 50,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/products/{$product->id}/adjust-stock", [
            'adjustment' => 10,
        ]);

    $response->assertForbidden();

    expect($product->fresh()->stock_quantity)->toBe(50);
});

test('stock adjustment logs note when provided', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Stock adjustment', \Mockery::on(function ($data) {
            return isset($data['note']) && $data['note'] === 'Test note';
        }));

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'stock_quantity' => 50,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/products/{$product->id}/adjust-stock", [
            'adjustment' => 10,
            'note' => 'Test note',
        ]);

    $response->assertOk();
});

test('unauthenticated user cannot adjust stock', function () {
    $tenant = Tenant::factory()->create();
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'stock_quantity' => 50,
    ]);

    $response = $this->postJson("/api/products/{$product->id}/adjust-stock", [
        'adjustment' => 10,
    ]);

    $response->assertUnauthorized();

    expect($product->fresh()->stock_quantity)->toBe(50);
});
