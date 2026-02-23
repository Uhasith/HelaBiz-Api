# Orders API Specification

## Overview
This document specifies the backend API endpoints required for the Orders management system.

---

## 📦 Orders Endpoints

All endpoints require authentication via Bearer token.

**Base URL:** `https://helabiz-api.test/api`

---

### 1. Create Order (Store)
**POST** `/orders` 🔒

Create a new order with line items.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Payload Example:**
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
  "notes": "Customer requested express delivery. Handle with care.",
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

**Field Descriptions:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| customer_name | string | Yes | Full name of the customer |
| customer_phone | string | Optional* | Phone number with country code (+94). Required if email is not provided |
| customer_email | string | Optional* | Email address. Required if phone is not provided |
| address | string | Optional | Delivery or billing address |
| order_date | date | Yes | Order date in YYYY-MM-DD format |
| status | string | Yes | Order status: `pending`, `processing`, `completed`, `cancelled` |
| subtotal | decimal | Yes | Sum of all items before discount and tax |
| tax | decimal | Yes | Tax amount (currently 0.00) |
| discount | decimal | Yes | Discount amount applied |
| total | decimal | Yes | Final total after discount (subtotal - discount + tax) |
| warranty_period | integer | Optional | Warranty duration (e.g., 6, 12, 24) |
| warranty_unit | string | Optional | Warranty unit: `days`, `weeks`, `months`, `years` |
| notes | string | Optional | Special instructions or notes (max 1000 chars) |
| items | array | Yes | Array of order line items (minimum 1 item required) |
| items[].product_id | integer | Yes | ID of the product |
| items[].quantity | integer | Yes | Quantity ordered (minimum 1) |
| items[].unit_price | decimal | Yes | Price per unit at time of order |
| items[].total | decimal | Yes | Line total (unit_price × quantity) |

**Note:** Either `customer_phone` OR `customer_email` must be provided. The backend should:
1. Search for existing customer by phone or email
2. If found, link the order to that customer_id
3. If not found, create a new customer record
4. Store the customer_id in the orders table

**Success Response (201 Created):**
```json
{
  "message": "Order created successfully",
  "data": {
    "id": 123,
    "order_number": "ORD-2026-000123",
    "customer_id": 45,
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
    "notes": "Customer requested express delivery. Handle with care.",
    "items": [
      {
        "id": 456,
        "order_id": 123,
        "product_id": 1,
        "product_name": "Laptop Dell Inspiron",
        "quantity": 2,
        "unit_price": 10000.00,
        "total": 20000.00
      },
      {
        "id": 457,
        "order_id": 123,
        "product_id": 5,
        "product_name": "Wireless Mouse",
        "quantity": 1,
        "unit_price": 5000.00,
        "total": 5000.00
      }
    ],
    "customer": {
      "id": 45,
      "name": "Nimal Perera",
      "phone": "+94 77 123 4567",
      "email": "nimal@example.com"
    },
    "created_at": "2026-02-23T14:30:00.000000Z",
    "updated_at": "2026-02-23T14:30:00.000000Z"
  }
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "customer_name": ["The customer name field is required."],
    "items": ["At least one item is required."],
    "items.0.product_id": ["The selected product does not exist."]
  }
}
```

---

### 2. List Orders (Index)
**GET** `/orders` 🔒

Retrieve paginated list of orders for the authenticated tenant.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | integer | 1 | Page number for pagination |
| per_page | integer | 15 | Items per page (max 100) |
| status | string | - | Filter by status: `pending`, `processing`, `completed`, `cancelled` |
| search | string | - | Search by order_number, customer_name, or customer_phone |
| from_date | date | - | Filter orders from this date (YYYY-MM-DD) |
| to_date | date | - | Filter orders to this date (YYYY-MM-DD) |
| sort_by | string | created_at | Sort field: `created_at`, `order_date`, `total`, `order_number` |
| sort_order | string | desc | Sort direction: `asc`, `desc` |

**Example Request:**
```
GET /api/orders?page=1&per_page=20&status=pending&search=Nimal&sort_by=order_date&sort_order=desc
```

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "id": 123,
      "order_number": "ORD-2026-000123",
      "customer_id": 45,
      "customer_name": "Nimal Perera",
      "customer_phone": "+94 77 123 4567",
      "customer_email": "nimal@example.com",
      "order_date": "2026-02-23",
      "status": "pending",
      "subtotal": 25000.00,
      "tax": 0.00,
      "discount": 2500.00,
      "total": 22500.00,
      "warranty_period": 6,
      "warranty_unit": "months",
      "items_count": 2,
      "customer": {
        "id": 45,
        "name": "Nimal Perera",
        "phone": "+94 77 123 4567",
        "email": "nimal@example.com"
      },
      "created_at": "2026-02-23T14:30:00.000000Z",
      "updated_at": "2026-02-23T14:30:00.000000Z"
    },
    {
      "id": 122,
      "order_number": "ORD-2026-000122",
      "customer_id": 44,
      "customer_name": "Kamal Silva",
      "customer_phone": "+94 71 987 6543",
      "customer_email": "kamal@example.com",
      "order_date": "2026-02-22",
      "status": "completed",
      "subtotal": 15000.00,
      "tax": 0.00,
      "discount": 0.00,
      "total": 15000.00,
      "warranty_period": 12,
      "warranty_unit": "months",
      "items_count": 1,
      "customer": {
        "id": 44,
        "name": "Kamal Silva",
        "phone": "+94 71 987 6543",
        "email": "kamal@example.com"
      },
      "created_at": "2026-02-22T10:15:00.000000Z",
      "updated_at": "2026-02-22T16:45:00.000000Z"
    }
  ],
  "links": {
    "first": "https://helabiz-api.test/api/orders?page=1",
    "last": "https://helabiz-api.test/api/orders?page=10",
    "prev": null,
    "next": "https://helabiz-api.test/api/orders?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "path": "https://helabiz-api.test/api/orders",
    "per_page": 20,
    "to": 20,
    "total": 198
  }
}
```

---

### 3. Show Order Details (Show)
**GET** `/orders/{id}` 🔒

Retrieve detailed information for a specific order including all line items.

**Headers:**
```
Authorization: Bearer {token}
```

**URL Parameters:**
- `{id}` - Order ID

**Example Request:**
```
GET /api/orders/123
```

**Success Response (200 OK):**
```json
{
  "data": {
    "id": 123,
    "order_number": "ORD-2026-000123",
    "customer_id": 45,
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
    "notes": "Customer requested express delivery. Handle with care.",
    "items": [
      {
        "id": 456,
        "order_id": 123,
        "product_id": 1,
        "product_name": "Laptop Dell Inspiron",
        "product_sku": "LAP-DELL-001",
        "quantity": 2,
        "unit_price": 10000.00,
        "total": 20000.00,
        "product": {
          "id": 1,
          "name": "Laptop Dell Inspiron",
          "sku": "LAP-DELL-001",
          "price": 10500.00,
          "stock": 8
        }
      },
      {
        "id": 457,
        "order_id": 123,
        "product_id": 5,
        "product_name": "Wireless Mouse",
        "product_sku": "ACC-MOU-005",
        "quantity": 1,
        "unit_price": 5000.00,
        "total": 5000.00,
        "product": {
          "id": 5,
          "name": "Wireless Mouse",
          "sku": "ACC-MOU-005",
          "price": 5000.00,
          "stock": 25
        }
      }
    ],
    "customer": {
      "id": 45,
      "name": "Nimal Perera",
      "phone": "+94 77 123 4567",
      "email": "nimal@example.com",
      "address": "No. 123, Galle Road, Colombo 03",
      "orders_count": 5,
      "total_spent": 125000.00
    },
    "created_at": "2026-02-23T14:30:00.000000Z",
    "updated_at": "2026-02-23T14:30:00.000000Z"
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "Order not found"
}
```

---

### 4. Update Order (Update)
**PUT/PATCH** `/orders/{id}` 🔒

Update an existing order. Can update status, customer details, or add/modify line items.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
- `{id}` - Order ID

**Request Payload Example:**
```json
{
  "customer_name": "Nimal Perera (Updated)",
  "customer_phone": "+94 77 123 4567",
  "customer_email": "nimal.updated@example.com",
  "address": "No. 456, New Address, Colombo 05",
  "order_date": "2026-02-23",
  "status": "processing",
  "subtotal": 30000.00,
  "tax": 0.00,
  "discount": 3000.00,
  "total": 27000.00,
  "warranty_period": 12,
  "warranty_unit": "months",
  "notes": "Updated notes",
  "items": [
    {
      "id": 456,
      "product_id": 1,
      "quantity": 3,
      "unit_price": 10000.00,
      "total": 30000.00
    }
  ]
}
```

**Note:** 
- When updating items, include the `id` field for existing items to update them
- Omit the `id` field for new items to add them
- Items not included in the request will be removed
- Some businesses may want to restrict updates to `completed` orders

**Success Response (200 OK):**
```json
{
  "message": "Order updated successfully",
  "data": {
    "id": 123,
    "order_number": "ORD-2026-000123",
    "customer_id": 45,
    "customer_name": "Nimal Perera (Updated)",
    "customer_phone": "+94 77 123 4567",
    "customer_email": "nimal.updated@example.com",
    "address": "No. 456, New Address, Colombo 05",
    "order_date": "2026-02-23",
    "status": "processing",
    "subtotal": 30000.00,
    "tax": 0.00,
    "discount": 3000.00,
    "total": 27000.00,
    "warranty_period": 12,
    "warranty_unit": "months",
    "notes": "Updated notes",
    "items": [
      {
        "id": 456,
        "order_id": 123,
        "product_id": 1,
        "product_name": "Laptop Dell Inspiron",
        "quantity": 3,
        "unit_price": 10000.00,
        "total": 30000.00
      }
    ],
    "created_at": "2026-02-23T14:30:00.000000Z",
    "updated_at": "2026-02-23T15:45:00.000000Z"
  }
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "status": ["The selected status is invalid."]
  }
}
```

---

### 5. Delete Order (Destroy)
**DELETE** `/orders/{id}` 🔒

Soft delete an order. Consider restrictions based on order status.

**Headers:**
```
Authorization: Bearer {token}
```

**URL Parameters:**
- `{id}` - Order ID

**Example Request:**
```
DELETE /api/orders/123
```

**Success Response (200 OK):**
```json
{
  "message": "Order deleted successfully"
}
```

**Error Response (403 Forbidden):**
```json
{
  "message": "Cannot delete completed orders"
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "Order not found"
}
```

---

### 6. Update Order Status (Additional Endpoint - Optional)
**PATCH** `/orders/{id}/status` 🔒

Quickly update only the order status without modifying other fields.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
- `{id}` - Order ID

**Request Payload:**
```json
{
  "status": "completed"
}
```

**Success Response (200 OK):**
```json
{
  "message": "Order status updated successfully",
  "data": {
    "id": 123,
    "order_number": "ORD-2026-000123",
    "status": "completed",
    "updated_at": "2026-02-23T16:00:00.000000Z"
  }
}
```

---

## Database Schema Recommendations

### orders table
```sql
id (bigint, primary key, auto_increment)
tenant_id (bigint, foreign key -> tenants.id)
customer_id (bigint, foreign key -> customers.id)
order_number (string, unique, indexed) -- e.g., ORD-2026-000123
order_date (date, indexed)
status (enum: pending, processing, completed, cancelled, default: pending, indexed)
subtotal (decimal 10,2)
tax (decimal 10,2, default: 0.00)
discount (decimal 10,2, default: 0.00)
total (decimal 10,2)
warranty_period (integer, nullable)
warranty_unit (enum: days, weeks, months, years, nullable)
notes (text, nullable)
created_at (timestamp)
updated_at (timestamp)
deleted_at (timestamp, nullable) -- for soft deletes
```

### order_items table
```sql
id (bigint, primary key, auto_increment)
order_id (bigint, foreign key -> orders.id, on delete cascade)
product_id (bigint, foreign key -> products.id)
product_name (string) -- snapshot of product name at time of order
product_sku (string, nullable) -- snapshot of SKU at time of order
quantity (integer, min: 1)
unit_price (decimal 10,2) -- price at time of order
total (decimal 10,2) -- quantity × unit_price
created_at (timestamp)
updated_at (timestamp)
```

---

## Business Logic Requirements

1. **Customer Management:**
   - When creating an order, check if customer exists by phone OR email
   - If exists, use existing customer_id
   - If not, create new customer record with provided details
   - Store customer_id in orders table (not just customer_name)

2. **Order Number Generation:**
   - Auto-generate unique order numbers (e.g., ORD-2026-000123)
   - Format: ORD-[YEAR]-[SEQUENCE]
   - Sequence should be zero-padded (6 digits recommended)

3. **Stock Management:**
   - Consider implementing stock deduction when order status changes to 'completed'
   - Consider stock reservation when order is created
   - Handle stock restoration if order is cancelled

4. **Authorization:**
   - All orders should be scoped to authenticated tenant_id
   - Users can only view/modify orders belonging to their tenant

5. **Data Snapshots:**
   - Store product name, SKU, and price at the time of order
   - Don't rely on current product data for historical orders
   - This ensures order details remain accurate even if product is updated/deleted

6. **Validation:**
   - Validate that all product_ids exist before creating order
   - Ensure at least one item in items array
   - Validate quantity > 0 for all items
   - Ensure subtotal, discount, and total calculations are correct
   - Validate status transitions (optional: restrict certain status changes)

7. **Soft Deletes:**
   - Use soft deletes for orders to maintain data integrity
   - Consider restricting deletion of completed orders

8. **Status Management:**
   - Valid statuses: pending, processing, completed, cancelled
   - Consider implementing status transition rules
   - Log status changes for audit trail (optional)

---

## Error Handling

All error responses should follow Laravel's standard format:

**Validation Errors (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

**Authentication Errors (401):**
```json
{
  "message": "Unauthenticated."
}
```

**Authorization Errors (403):**
```json
{
  "message": "This action is unauthorized."
}
```

**Not Found Errors (404):**
```json
{
  "message": "Resource not found"
}
```

**Server Errors (500):**
```json
{
  "message": "Server error occurred"
}
```

---

## Testing Checklist

- [ ] Create order with valid data
- [ ] Create order with new customer (auto-create customer)
- [ ] Create order with existing customer (link to existing)
- [ ] Create order with invalid product_id
- [ ] Create order without items
- [ ] List orders with pagination
- [ ] List orders with filters (status, date range, search)
- [ ] Show single order details
- [ ] Update order customer details
- [ ] Update order status
- [ ] Update order items (add/modify/remove)
- [ ] Delete order (soft delete)
- [ ] Prevent access to other tenant's orders
- [ ] Handle concurrent updates
- [ ] Validate calculations (subtotal, discount, total)

---

## Notes for Frontend Team

The mobile app is currently sending orders in the format shown in the "Create Order" payload above. The endpoint should accept exactly that structure without requiring the frontend to send `customer_id` directly - the backend will handle customer lookup/creation and assign the appropriate customer_id.
