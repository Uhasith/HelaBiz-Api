# Orders API - Frontend Payload Example

## What the Mobile App is Sending

When a user places an order from the cart, the mobile app sends the following payload to `POST /api/orders`:

### Actual Payload Structure

```json
{
  "customer_name": "Nimal Perera",
  "customer_phone": "+94 77 123 4567",
  "customer_email": "nimal@example.com",
  "address": "No. 123, Galle Road, Colombo 03",
  "order_date": "2026-02-23",
  "status": "pending",
  "subtotal": 25000.00,
  "tax": 0.00,
  "discount": 2500.00,
  "total": 22500.00,
  "warranty_period": 6,
  "warranty_unit": "months",
  "notes": "Customer requested express delivery",
  "items": [
    {
      "product_id": 1,
      "quantity": 2,
      "unit_price": 10000.00,
      "total": 20000.00
    },
    {
      "product_id": 5,
      "quantity": 1,
      "unit_price": 5000.00,
      "total": 5000.00
    }
  ]
}
```

### Field Notes

1. **customer_id is NOT included** - The backend should:
   - Search for existing customer by `customer_phone` OR `customer_email`
   - If found, use that customer's ID
   - If not found, create a new customer record
   - Link the order to the customer_id

2. **Phone Format**: Always sent with +94 prefix and spaces (e.g., "+94 77 123 4567")

3. **status**: Always "pending" for new orders

4. **tax**: Currently always 0.00 (may change in future)

5. **discount**: Calculated amount (not percentage) that was subtracted from subtotal

6. **warranty_period** & **warranty_unit**: 
   - Both can be `null` if no warranty
   - If warranty_period is provided, warranty_unit will be one of: `days`, `weeks`, `months`, `years`

7. **notes**: Can be `null` if user didn't add any notes

8. **address**: Can be `null` if not provided (optional field)

9. **customer_email**: Can be `null` if only phone was provided

10. **customer_phone**: Can be `null` if only email was provided (but at least one must exist)

### Minimal Valid Payload

Minimum required fields for a valid order:

```json
{
  "customer_name": "John Doe",
  "customer_phone": "+94 77 123 4567",
  "customer_email": null,
  "address": null,
  "order_date": "2026-02-23",
  "status": "pending",
  "subtotal": 10000.00,
  "tax": 0.00,
  "discount": 0.00,
  "total": 10000.00,
  "warranty_period": null,
  "warranty_unit": null,
  "notes": null,
  "items": [
    {
      "product_id": 1,
      "quantity": 1,
      "unit_price": 10000.00,
      "total": 10000.00
    }
  ]
}
```

### Frontend Code Reference

The payload is constructed in: `app/Livewire/Pages/Cart/Index.php` in the `placeOrder()` method (lines 142-163).

The API call is made through: `app/Services/OrderService.php` using the `create()` method.

---

## Quick Backend Implementation Guide

### Step 1: Create Order
```php
POST /api/orders

// In your controller:
1. Validate the incoming request
2. Find or create customer:
   - Search by phone OR email
   - If not found, create new customer
3. Create order record with customer_id
4. Create order_items records
5. (Optional) Update product stock
6. Return order with customer details
```

### Step 2: List Orders
```php
GET /api/orders?page=1&per_page=20&status=pending

// Return paginated orders with:
- Basic order info
- Customer relation (id, name, phone, email)
- Items count
- Standard Laravel pagination meta
```

### Step 3: Show Order
```php
GET /api/orders/{id}

// Return single order with:
- Full order details
- All items with product details
- Customer full details
```

### Step 4: Update Order
```php
PUT /api/orders/{id}

// Allow updating:
- Customer details
- Status
- Items (add/modify/remove)
- Totals
- Warranty
- Notes
```

### Step 5: Delete Order
```php
DELETE /api/orders/{id}

// Soft delete
// Consider restricting based on status
```

---

## Expected Response Format

When the mobile app creates an order, it expects a response like:

```json
{
  "message": "Order created successfully",
  "data": {
    "id": 123,
    "order_number": "ORD-2026-000123",
    "customer_id": 45,
    "customer": {
      "id": 45,
      "name": "Nimal Perera",
      "phone": "+94 77 123 4567",
      "email": "nimal@example.com"
    },
    "order_date": "2026-02-23",
    "status": "pending",
    "total": 22500.00,
    "items": [...],
    "created_at": "2026-02-23T14:30:00.000000Z"
  }
}
```

Currently, the mobile app shows a success/error dialog and doesn't process the response deeply, but including detailed data helps with future features like order history viewing.

---

## Testing the Integration

### Using the Mobile App:
1. Add products to cart
2. Go to cart page
3. Fill in customer details (name + phone/email)
4. Optionally add: address, discount, warranty, notes
5. Click "Place Order"
6. App will send POST request to your API
7. On success: Shows success dialog, clears cart, resets form
8. On error: Shows error dialog with message

### Manual API Testing:
Use the example payloads above with your favorite API client (Postman, Insomnia, Thunder Client, etc.)

### Test Cases:
- ✅ New customer (should create customer record)
- ✅ Existing customer by phone (should link to existing)
- ✅ Existing customer by email (should link to existing)
- ✅ Order with discount
- ✅ Order with warranty
- ✅ Order with multiple items
- ✅ Order with just phone (no email)
- ✅ Order with just email (no phone)
- ❌ Order with no phone AND no email (should fail validation)
- ❌ Order with invalid product_id (should fail validation)
- ❌ Order with empty items array (should fail validation)
