# AutoPulse - Complete API Endpoints Reference

**Base URL:** `{tenant-domain}/api/v1`

**Authentication:** All endpoints (except login/register) require Bearer token authentication via Laravel Sanctum.

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

---

## 🔐 Authentication Endpoints

### POST /api/v1/auth/login
**Description:** Login to get access token  
**Access:** Public  
**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password"
}
```
**Response:**
```json
{
  "success": true,
  "data": {
    "token": "1|abc123...",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "role": "owner"
    }
  },
  "message": "Logged in successfully"
}
```

### POST /api/v1/auth/register
**Description:** Register a new user  
**Access:** Public  
**Request Body:**
```json
{
  "name": "John Doe",
  "email": "user@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

### POST /api/v1/auth/logout
**Description:** Logout and revoke current token  
**Access:** Protected (auth:sanctum)

### POST /api/v1/auth/refresh
**Description:** Refresh authentication token  
**Access:** Protected (auth:sanctum)

### GET /api/v1/auth/me
**Description:** Get current authenticated user details  
**Access:** Protected (auth:sanctum)

---

## 👥 Customer Endpoints

### GET /api/v1/customers
**Description:** List all customers with pagination and filtering  
**Access:** Protected  
**Query Parameters:**
- `filter[name]` - Filter by name
- `filter[phone]` - Filter by phone
- `filter[email]` - Filter by email
- `filter[city]` - Filter by city
- `sort` - Sort by field (e.g., `name`, `-created_at`)
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15, max: 100)

**Example:** `GET /api/v1/customers?filter[name]=john&sort=-created_at&page=1`

### POST /api/v1/customers
**Description:** Create a new customer  
**Access:** Protected  
**Request Body:**
```json
{
  "name": "John Smith",
  "email": "john@example.com",
  "phone": "+1234567890",
  "phone_alt": "+1234567891",
  "address": "123 Main St",
  "city": "New York",
  "postal_code": "10001",
  "preferred_language": "en",
  "notes": "VIP customer"
}
```

### GET /api/v1/customers/{id}
**Description:** Get customer details with relationships  
**Access:** Protected

### PUT /api/v1/customers/{id}
**Description:** Update customer information  
**Access:** Protected

### DELETE /api/v1/customers/{id}
**Description:** Soft delete a customer  
**Access:** Protected

---

## 🚗 Vehicle Endpoints

### GET /api/v1/vehicles
**Description:** List all vehicles with filtering  
**Access:** Protected  
**Query Parameters:**
- `filter[registration_number]` - Filter by registration
- `filter[customer_id]` - Filter by customer
- `filter[make]` - Filter by make
- `filter[model]` - Filter by model
- `sort` - Sort by field
- `page` - Page number
- `per_page` - Items per page

### POST /api/v1/vehicles
**Description:** Create a new vehicle  
**Access:** Protected  
**Request Body:**
```json
{
  "customer_id": 1,
  "registration_number": "ABC-1234",
  "make": "Toyota",
  "model": "Camry",
  "year": 2020,
  "color": "Black",
  "vin": "1HGBH41JXMN109186",
  "engine_number": "ABC123456",
  "current_mileage": 50000,
  "notes": "Regular maintenance every 5000 km"
}
```

### GET /api/v1/vehicles/{id}
**Description:** Get vehicle details with customer information  
**Access:** Protected

### PUT /api/v1/vehicles/{id}
**Description:** Update vehicle information  
**Access:** Protected

### DELETE /api/v1/vehicles/{id}
**Description:** Soft delete a vehicle  
**Access:** Protected

---

## 📋 Job Card Endpoints

### GET /api/v1/job-cards
**Description:** List all job cards with filtering  
**Access:** Protected  
**Query Parameters:**
- `filter[status]` - Filter by status (pending, diagnosis, approval, working, qc, ready, delivered)
- `filter[priority]` - Filter by priority (low, normal, high, urgent)
- `filter[customer_id]` - Filter by customer
- `filter[vehicle_id]` - Filter by vehicle
- `filter[assigned_to]` - Filter by assigned user
- `sort` - Sort by field
- `page` - Page number
- `per_page` - Items per page

### POST /api/v1/job-cards
**Description:** Create a new job card (auto-generates job_number)  
**Access:** Protected  
**Request Body:**
```json
{
  "customer_id": 1,
  "vehicle_id": 1,
  "assigned_to": 2,
  "status": "pending",
  "priority": "normal",
  "mileage_in": 55000,
  "estimated_completion": "2024-02-01 16:00:00",
  "customer_notes": "Strange noise from engine",
  "internal_notes": "Check timing belt"
}
```
**Response:** Auto-generates `job_number` (e.g., JOB-2024020001)

### GET /api/v1/job-cards/{id}
**Description:** Get job card details with all items and relationships  
**Access:** Protected

### PUT /api/v1/job-cards/{id}
**Description:** Update job card information  
**Access:** Protected

### DELETE /api/v1/job-cards/{id}
**Description:** Soft delete a job card  
**Access:** Protected

### POST /api/v1/job-cards/{id}/items
**Description:** Add items (parts or labor) to a job card  
**Access:** Protected  
**Request Body:**
```json
{
  "product_id": 5,
  "item_type": "part",
  "quantity": 2,
  "unit_price": 150.00,
  "tax_rate": 15.00,
  "discount": 10.00
}
```

### PATCH /api/v1/job-cards/{id}/status
**Description:** Update job card status with workflow validation  
**Access:** Protected  
**Request Body:**
```json
{
  "status": "working"
}
```
**Status Flow:** `pending → diagnosis → approval → working → qc → ready → delivered`

---

## 💰 Invoice Endpoints

### GET /api/v1/invoices
**Description:** List all invoices with filtering  
**Access:** Protected  
**Query Parameters:**
- `filter[status]` - Filter by status (draft, sent, paid, overdue, cancelled)
- `filter[customer_id]` - Filter by customer
- `filter[job_card_id]` - Filter by job card
- `sort` - Sort by field
- `page` - Page number
- `per_page` - Items per page

### POST /api/v1/invoices
**Description:** Create a new invoice (auto-generates invoice_number)  
**Access:** Protected  
**Request Body:**
```json
{
  "customer_id": 1,
  "job_card_id": 1,
  "invoice_date": "2024-02-01",
  "due_date": "2024-02-15",
  "status": "sent",
  "subtotal": 500.00,
  "tax_amount": 75.00,
  "discount_amount": 25.00,
  "notes": "Thank you for your business"
}
```
**Response:** Auto-generates `invoice_number` (e.g., INV-2024020001)

### GET /api/v1/invoices/{id}
**Description:** Get invoice details with payments and customer info  
**Access:** Protected

### PUT /api/v1/invoices/{id}
**Description:** Update invoice information  
**Access:** Protected

### DELETE /api/v1/invoices/{id}
**Description:** Soft delete an invoice  
**Access:** Protected

---

## 💳 Payment Endpoints

### GET /api/v1/payments
**Description:** List all payments  
**Access:** Protected  
**Query Parameters:**
- `filter[invoice_id]` - Filter by invoice
- `filter[payment_method]` - Filter by payment method
- `sort` - Sort by field
- `page` - Page number
- `per_page` - Items per page

### POST /api/v1/payments
**Description:** Record a new payment (automatically updates invoice status)  
**Access:** Protected  
**Request Body:**
```json
{
  "invoice_id": 1,
  "payment_date": "2024-02-01",
  "amount": 575.00,
  "payment_method": "card",
  "transaction_reference": "TXN-12345",
  "notes": "Paid in full"
}
```
**Behavior:** Automatically updates invoice `paid_amount`, `balance`, and `status`

### GET /api/v1/payments/{id}
**Description:** Get payment details  
**Access:** Protected

### PUT /api/v1/payments/{id}
**Description:** Update payment information  
**Access:** Protected

### DELETE /api/v1/payments/{id}
**Description:** Delete a payment  
**Access:** Protected

---

## 📅 Appointment Endpoints

### GET /api/v1/appointments
**Description:** List all appointments  
**Access:** Protected  
**Query Parameters:**
- `filter[status]` - Filter by status (pending, confirmed, in_progress, completed, cancelled)
- `filter[appointment_date]` - Filter by date (YYYY-MM-DD)
- `filter[customer_id]` - Filter by customer
- `filter[vehicle_id]` - Filter by vehicle
- `sort` - Sort by field
- `page` - Page number
- `per_page` - Items per page

### POST /api/v1/appointments
**Description:** Create a new appointment  
**Access:** Protected  
**Request Body:**
```json
{
  "customer_id": 1,
  "vehicle_id": 1,
  "appointment_date": "2024-02-05",
  "start_time": "10:00:00",
  "end_time": "11:30:00",
  "service_type": "Regular Maintenance",
  "status": "pending",
  "notes": "Customer prefers morning slots"
}
```

### GET /api/v1/appointments/{id}
**Description:** Get appointment details  
**Access:** Protected

### PUT /api/v1/appointments/{id}
**Description:** Update appointment information  
**Access:** Protected

### DELETE /api/v1/appointments/{id}
**Description:** Delete an appointment  
**Access:** Protected

### POST /api/v1/appointments/{id}/confirm
**Description:** Confirm an appointment  
**Access:** Protected  
**Request Body:**
```json
{
  "confirmed_by": 1
}
```

---

## 📊 Dashboard Endpoint

### GET /api/v1/dashboard/stats
**Description:** Get dashboard statistics  
**Access:** Protected  
**Response:**
```json
{
  "success": true,
  "data": {
    "total_customers": 150,
    "active_job_cards": 25,
    "pending_invoices": 12,
    "today_appointments": 8
  },
  "message": "Dashboard statistics retrieved successfully"
}
```

---

## 📝 Response Format

### Success Response
```json
{
  "success": true,
  "data": {...},
  "message": "Operation successful",
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

---

## 🔢 HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |

---

## 📊 Pagination

All list endpoints support pagination:
```
GET /api/v1/customers?page=2&per_page=20
```
- Default: 15 items per page
- Maximum: 100 items per page

---

## 🔍 Filtering & Sorting

Use Spatie Query Builder syntax:

**Filtering:**
```
GET /api/v1/job-cards?filter[status]=working&filter[priority]=high
```

**Sorting:**
```
GET /api/v1/customers?sort=name           # Ascending
GET /api/v1/customers?sort=-created_at    # Descending (prefix with -)
```

**Multiple sorts:**
```
GET /api/v1/vehicles?sort=make,-year
```

---

## 🎯 Summary

**Total Endpoints:** 47

| Category | Count |
|----------|-------|
| Authentication | 5 |
| Customers | 5 |
| Vehicles | 5 |
| Job Cards | 7 |
| Invoices | 5 |
| Payments | 5 |
| Appointments | 6 |
| Dashboard | 1 |

All endpoints support:
- ✅ Bearer token authentication
- ✅ Tenant isolation (automatic)
- ✅ Consistent response format
- ✅ Proper error handling
- ✅ Validation
- ✅ Pagination (where applicable)
- ✅ Filtering & sorting (list endpoints)
