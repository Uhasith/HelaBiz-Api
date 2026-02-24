# Order Invoice API Documentation

## Overview
The Order Invoice API allows you to generate, manage, and retrieve PDF invoices from orders. All endpoints require authentication via Sanctum token.

**Base URL:** `/api/orders/invoices`

---

## Authentication
All endpoints require a bearer token in the Authorization header:
```
Authorization: Bearer {your_token}
```

---

## Endpoints

### 1. Generate Invoice from Order
Create a new invoice from an existing order. The invoice will be automatically populated with order data, customer information, and items.

**Endpoint:** `POST /api/orders/invoices`

**Request Body:**
```json
{
  "order_id": 123
}
```

**Success Response:** `201 Created`
```json
{
  "message": "Invoice generated successfully",
  "invoice": {
    "id": 1,
    "tenant_id": 1,
    "customer_id": 5,
    "order_id": 123,
    "invoice_number": "INV-00001",
    "invoice_date": "2026-02-24",
    "due_date": "2026-03-03",
    "status": "draft",
    "subtotal": "100.00",
    "tax": "10.00",
    "discount": "5.00",
    "total": "105.00",
    "notes": "Order notes here",
    "created_at": "2026-02-24T10:30:00.000000Z",
    "updated_at": "2026-02-24T10:30:00.000000Z",
    "items": [
      {
        "id": 1,
        "invoice_id": 1,
        "product_id": 10,
        "quantity": 2,
        "unit_price": "50.00",
        "total": "100.00",
        "created_at": "2026-02-24T10:30:00.000000Z",
        "updated_at": "2026-02-24T10:30:00.000000Z"
      }
    ]
  },
  "pdf_url": "http://your-app.test/storage/1/invoice_INV-00001.pdf"
}
```

**Error Response:** `422 Unprocessable Entity`
```json
{
  "message": "Invoice already exists for this order",
  "invoice": {
    "id": 1,
    "invoice_number": "INV-00001",
    ...
  }
}
```

**Validation Errors:** `422 Unprocessable Entity`
```json
{
  "message": "The order id field is required.",
  "errors": {
    "order_id": [
      "The order id field is required."
    ]
  }
}
```

---

### 2. Get Invoice Details
Retrieve detailed information about a specific invoice including customer, items, order, and tenant data.

**Endpoint:** `GET /api/orders/invoices/{invoice}`

**URL Parameters:**
- `invoice` (integer, required) - Invoice ID

**Success Response:** `200 OK`
```json
{
  "id": 1,
  "tenant_id": 1,
  "customer_id": 5,
  "order_id": 123,
  "invoice_number": "INV-00001",
  "invoice_date": "2026-02-24",
  "due_date": "2026-03-03",
  "status": "draft",
  "subtotal": "100.00",
  "tax": "10.00",
  "discount": "5.00",
  "total": "105.00",
  "notes": "Order notes here",
  "pdf_url": "http://your-app.test/storage/1/invoice_INV-00001.pdf",
  "customer": {
    "id": 5,
    "tenant_id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "address": "123 Main St, City, Country"
  },
  "items": [
    {
      "id": 1,
      "invoice_id": 1,
      "product_id": 10,
      "quantity": 2,
      "unit_price": "50.00",
      "total": "100.00",
      "product": {
        "id": 10,
        "name": "Product Name",
        "sku": "SKU-123",
        ...
      }
    }
  ],
  "order": {
    "id": 123,
    "order_number": "ORD-2026-000123",
    "order_date": "2026-02-20",
    "status": "completed",
    ...
  },
  "tenant": {
    "id": 1,
    "business_name": "My Business",
    "email": "business@example.com",
    "phone": "+1234567890",
    "address": "Business Address",
    "city": "City",
    "country": "Country",
    "currency": "USD"
  },
  "created_at": "2026-02-24T10:30:00.000000Z",
  "updated_at": "2026-02-24T10:30:00.000000Z"
}
```

**Error Response:** `404 Not Found`
```json
{
  "message": "No query results for model [App\\Models\\Invoice] 999"
}
```

---

### 3. Download Invoice PDF
Download the invoice PDF file. The response will be a file download.

**Endpoint:** `GET /api/orders/invoices/{invoice}/download`

**URL Parameters:**
- `invoice` (integer, required) - Invoice ID

**Success Response:** `200 OK`
- Content-Type: `application/pdf`
- Content-Disposition: `attachment; filename="invoice_INV-00001.pdf"`
- Body: PDF file binary data

**Error Response:** `404 Not Found`
```json
{
  "message": "Invoice PDF not found"
}
```

**Usage Example (JavaScript):**
```javascript
async function downloadInvoice(invoiceId) {
  const response = await fetch(`/api/orders/invoices/${invoiceId}/download`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const blob = await response.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `invoice_${invoiceId}.pdf`;
  a.click();
}
```

---

### 4. Get Invoice PDF URL for Preview
Get the invoice PDF URL to display in the browser. This is useful for previewing the PDF in an iframe or displaying in a PDF viewer.

**Endpoint:** `GET /api/orders/invoices/{invoice}/stream`

**URL Parameters:**
- `invoice` (integer, required) - Invoice ID

**Success Response:** `200 OK`
```json
{
  "pdf_url": "https://your-domain.test/storage/1/invoice_INV-00001.pdf",
  "invoice_number": "INV-00001"
}
```

**Error Response:** `404 Not Found`
```json
{
  "message": "Invoice PDF not found"
}
```

**Usage Example (JavaScript):**
```javascript
// Display in iframe
async function viewInvoice(invoiceId) {
  const response = await fetch(`/api/orders/invoices/${invoiceId}/stream`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const data = await response.json();
  
  // Option 1: Display in iframe
  document.getElementById('pdf-viewer').src = data.pdf_url;
  
  // Option 2: Open in new tab
  window.open(data.pdf_url, '_blank');
}
```

**Usage Example (React):**
```jsx
function InvoicePreview({ invoiceId }) {
  const [pdfUrl, setPdfUrl] = useState(null);
  
  useEffect(() => {
    async function loadPdf() {
      const response = await fetch(`/api/orders/invoices/${invoiceId}/stream`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      const data = await response.json();
      setPdfUrl(data.pdf_url);
    }
    loadPdf();
  }, [invoiceId]);
  
  return (
    <iframe 
      src={pdfUrl} 
      width="100%" 
      height="600px"
      title="Invoice Preview"
    />
  );
}
```

**Usage Example (Vue):**
```vue
<template>
  <iframe 
    v-if="pdfUrl" 
    :src="pdfUrl" 
    width="100%" 
    height="600px"
    title="Invoice Preview"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps(['invoiceId']);
const pdfUrl = ref(null);

onMounted(async () => {
  const response = await fetch(`/api/orders/invoices/${props.invoiceId}/stream`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  const data = await response.json();
  pdfUrl.value = data.pdf_url;
});
</script>
```

---

### 5. Delete Invoice PDF
Delete the PDF file associated with an invoice. This only removes the PDF file, not the invoice record itself.

**Endpoint:** `DELETE /api/orders/invoices/{invoice}/pdf`

**URL Parameters:**
- `invoice` (integer, required) - Invoice ID

**Success Response:** `200 OK`
```json
{
  "message": "Invoice PDF deleted successfully"
}
```

**Error Response:** `404 Not Found`
```json
{
  "message": "No query results for model [App\\Models\\Invoice] 999"
}
```

---

### 6. Regenerate Invoice PDF
Regenerate the invoice PDF with current data. Useful if invoice data was updated or if the PDF was deleted.

**Endpoint:** `POST /api/orders/invoices/{invoice}/regenerate`

**URL Parameters:**
- `invoice` (integer, required) - Invoice ID

**Success Response:** `200 OK`
```json
{
  "message": "Invoice PDF regenerated successfully",
  "pdf_url": "http://your-app.test/storage/1/invoice_INV-00001.pdf"
}
```

**Error Response:** `404 Not Found`
```json
{
  "message": "No query results for model [App\\Models\\Invoice] 999"
}
```

---

## Invoice Status Values
The `status` field can have the following values:
- `draft` - Invoice is created but not sent
- `sent` - Invoice has been sent to customer
- `paid` - Invoice has been paid
- `overdue` - Invoice payment is overdue
- `cancelled` - Invoice has been cancelled

---

## Common Error Responses

### Unauthorized (401)
```json
{
  "message": "Unauthenticated."
}
```

### Forbidden (403)
```json
{
  "message": "Unauthorized"
}
```
*Returned when trying to access an invoice from a different tenant.*

### Not Found (404)
```json
{
  "message": "No query results for model [App\\Models\\Invoice] {id}"
}
```

### Server Error (500)
```json
{
  "message": "Server Error"
}
```

---

## Frontend Integration Examples

### React/Next.js Example

```typescript
// types/invoice.ts
export interface Invoice {
  id: number;
  tenant_id: number;
  customer_id: number;
  order_id: number;
  invoice_number: string;
  invoice_date: string;
  due_date: string;
  status: 'draft' | 'sent' | 'paid' | 'overdue' | 'cancelled';
  subtotal: string;
  tax: string;
  discount: string;
  total: string;
  notes: string | null;
  pdf_url: string;
  created_at: string;
  updated_at: string;
}

// services/invoiceService.ts
const API_BASE = '/api/orders/invoices';

export const invoiceService = {
  // Generate invoice from order
  async create(orderId: number): Promise<Invoice> {
    const response = await fetch(API_BASE, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getToken()}`
      },
      body: JSON.stringify({ order_id: orderId })
    });
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message);
    }
    
    const data = await response.json();
    return data.invoice;
  },
  
  // Get invoice details
  async get(invoiceId: number): Promise<Invoice> {
    const response = await fetch(`${API_BASE}/${invoiceId}`, {
      headers: {
        'Authorization': `Bearer ${getToken()}`
      }
    });
    
    if (!response.ok) throw new Error('Failed to fetch invoice');
    return response.json();
  },
  
  // Download invoice PDF
  async download(invoiceId: number): Promise<void> {
    const response = await fetch(`${API_BASE}/${invoiceId}/download`, {
      headers: {
        'Authorization': `Bearer ${getToken()}`
      }
    });
    
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `invoice_${invoiceId}.pdf`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
  },
  
  // Get stream URL for viewing
  getStreamUrl(invoiceId: number): string {
    return `${API_BASE}/${invoiceId}/stream`;
  },
  
  // Delete PDF
  async deletePdf(invoiceId: number): Promise<void> {
    const response = await fetch(`${API_BASE}/${invoiceId}/pdf`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${getToken()}`
      }
    });
    
    if (!response.ok) throw new Error('Failed to delete PDF');
  },
  
  // Regenerate PDF
  async regenerate(invoiceId: number): Promise<string> {
    const response = await fetch(`${API_BASE}/${invoiceId}/regenerate`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${getToken()}`
      }
    });
    
    if (!response.ok) throw new Error('Failed to regenerate PDF');
    
    const data = await response.json();
    return data.pdf_url;
  }
};
```

### Vue/Nuxt Example

```javascript
// composables/useInvoice.js
export const useInvoice = () => {
  const config = useRuntimeConfig();
  const token = useCookie('auth_token');
  
  const generateInvoice = async (orderId) => {
    try {
      const response = await $fetch('/api/orders/invoices', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token.value}`
        },
        body: {
          order_id: orderId
        }
      });
      return response;
    } catch (error) {
      console.error('Failed to generate invoice:', error);
      throw error;
    }
  };
  
  const downloadInvoice = async (invoiceId) => {
    const url = `/api/orders/invoices/${invoiceId}/download`;
    window.location.href = url;
  };
  
  const viewInvoice = (invoiceId) => {
    window.open(`/api/orders/invoices/${invoiceId}/stream`, '_blank');
  };
  
  return {
    generateInvoice,
    downloadInvoice,
    viewInvoice
  };
};
```

---

## Notes

### PDF Generation
- PDFs are automatically generated when creating an invoice from an order
- The PDF uses a modern, professional template with gradient styling
- Business logo is automatically included if uploaded to tenant settings
- PDFs are stored using Spatie Media Library for efficient storage management

### Tenant Isolation
- All endpoints are tenant-isolated
- Users can only access invoices belonging to their tenant
- Attempting to access another tenant's invoice returns a 404 error

### Invoice Numbering
- Invoice numbers are auto-generated in format: `INV-00001`, `INV-00002`, etc.
- Sequence is global across all tenants but each tenant can only see their own

### Currency & Localization
- Currency is automatically set from tenant settings
- Supported currencies: USD, EUR, GBP, JPY, INR, AUD, CAD, CHF, CNY, LKR
- Date format: ISO 8601 (YYYY-MM-DD)

### Performance Considerations
- PDF generation may take 1-2 seconds for complex invoices
- Consider showing a loading state when generating invoices
- PDFs are cached in media library - regeneration only needed if data changes
- Use the stream endpoint for preview, download for saving

---

## Testing

### cURL Examples

```bash
# Generate invoice from order
curl -X POST http://your-app.test/api/orders/invoices \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"order_id": 123}'

# Get invoice details
curl http://your-app.test/api/orders/invoices/1 \
  -H "Authorization: Bearer YOUR_TOKEN"

# Download PDF
curl http://your-app.test/api/orders/invoices/1/download \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -o invoice.pdf

# Stream PDF
curl http://your-app.test/api/orders/invoices/1/stream \
  -H "Authorization: Bearer YOUR_TOKEN"

# Delete PDF
curl -X DELETE http://your-app.test/api/orders/invoices/1/pdf \
  -H "Authorization: Bearer YOUR_TOKEN"

# Regenerate PDF
curl -X POST http://your-app.test/api/orders/invoices/1/regenerate \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Support
For issues or questions, please contact the development team.

**API Version:** 1.0  
**Last Updated:** February 24, 2026
