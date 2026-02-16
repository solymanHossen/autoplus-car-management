# AutoPulse API Documentation

## Table of Contents
- [Introduction](#introduction)
- [Base URL](#base-url)
- [Authentication](#authentication)
- [Rate Limiting](#rate-limiting)
- [Pagination](#pagination)
- [Error Handling](#error-handling)
- [Endpoints](#endpoints)
  - [Authentication](#authentication-endpoints)
  - [Customers](#customers)
  - [Vehicles](#vehicles)
  - [Job Cards](#job-cards)
  - [Invoices](#invoices)
  - [Payments](#payments)
  - [Appointments](#appointments)
  - [Dashboard](#dashboard)
- [Webhooks](#webhooks)

---

## Introduction

The AutoPulse API is a RESTful API that enables integration with the AutoPulse Car Management System. This API supports multi-tenant architecture and uses Laravel Sanctum for authentication.

**API Version:** v1  
**Response Format:** JSON  
**Character Encoding:** UTF-8

---

## Base URL

```
https://your-domain.com/api/v1
```

For multi-tenant installations, the tenant is identified via domain, subdomain, or header (see [Multi-Tenancy Guide](./multi-tenancy.md)).

---

## Authentication

AutoPulse uses **Laravel Sanctum** token-based authentication for API access.

### Obtaining a Token

**Endpoint:** `POST /auth/login`

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "tenant_id": "uuid-tenant-id",
      "name": "John Doe",
      "email": "user@example.com",
      "role": "manager",
      "phone": "+1234567890",
      "avatar_url": null
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz123456789"
  },
  "message": "Login successful"
}
```

### Using the Token

Include the token in all authenticated requests via the `Authorization` header:

```
Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789
```

### Token Refresh

**Endpoint:** `POST /auth/refresh`  
**Auth:** Required

Generates a new token and revokes the old one.

### Logout

**Endpoint:** `POST /auth/logout`  
**Auth:** Required

Revokes the current authentication token.

---

## Rate Limiting

API requests are rate-limited to prevent abuse. Current limits:

- **Authenticated requests:** 60 requests per minute
- **Login endpoint:** 5 requests per minute
- **Registration endpoint:** 10 requests per minute

Rate limit information is included in response headers:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1643723400
```

When rate limit is exceeded:
```json
{
  "success": false,
  "message": "Too Many Requests",
  "error": "Rate limit exceeded. Please try again later."
}
```

**HTTP Status Code:** `429 Too Many Requests`

---

## Pagination

List endpoints support pagination using query parameters:

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15, max: 100)

**Example Request:**
```
GET /customers?page=2&per_page=20
```

**Response Format:**
```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 2,
    "from": 21,
    "last_page": 5,
    "per_page": 20,
    "to": 40,
    "total": 95
  },
  "links": {
    "first": "https://api.example.com/v1/customers?page=1",
    "last": "https://api.example.com/v1/customers?page=5",
    "prev": "https://api.example.com/v1/customers?page=1",
    "next": "https://api.example.com/v1/customers?page=3"
  }
}
```

---

## Error Handling

### Standard Error Response

```json
{
  "success": false,
  "message": "Error message here",
  "errors": {
    "field_name": [
      "Validation error message"
    ]
  }
}
```

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request succeeded |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request format or parameters |
| 401 | Unauthorized | Missing or invalid authentication token |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

### Common Error Codes

| Code | Message | Description |
|------|---------|-------------|
| `UNAUTHENTICATED` | Unauthenticated | No valid token provided |
| `UNAUTHORIZED` | Unauthorized | Insufficient permissions |
| `VALIDATION_ERROR` | Validation failed | Input validation errors |
| `NOT_FOUND` | Resource not found | Requested resource doesn't exist |
| `TENANT_NOT_FOUND` | Tenant not found | Invalid tenant identification |
| `RATE_LIMIT_EXCEEDED` | Too many requests | API rate limit exceeded |

---

## Endpoints

### Authentication Endpoints

#### Register Tenant

Create a new tenant workspace and owner account.

**Endpoint:** `POST /auth/register`  
**Auth:** None

**Request:**
```json
{
  "company_name": "Acme Garage",
  "domain": "acme.example.com",
  "subdomain": "acme",
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePassword123",
  "password_confirmation": "SecurePassword123",
  "phone": "+1234567890"
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "data": {
    "tenant": {
      "id": "uuid-tenant-id",
      "name": "Acme Garage",
      "domain": "acme.example.com",
      "subdomain": "acme"
    },
    "user": {
      "id": 1,
      "tenant_id": "uuid-tenant-id",
      "name": "John Doe",
      "email": "john@example.com",
      "role": "owner",
      "phone": "+1234567890"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz"
  },
  "message": "Tenant registered successfully"
}
```

#### Get Current User

Retrieve authenticated user details.

**Endpoint:** `GET /auth/me`  
**Auth:** Required

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "id": 1,
    "tenant_id": "uuid-tenant-id",
    "name": "John Doe",
    "email": "john@example.com",
    "role": "manager",
    "phone": "+1234567890",
    "avatar_url": "https://example.com/avatars/user.jpg",
    "created_at": "2024-01-31T10:00:00.000000Z"
  }
}
```

---

### Customers

#### List Customers

Retrieve a paginated list of customers.

**Endpoint:** `GET /customers`  
**Auth:** Required  
**Permission:** `view-customers`

**Query Parameters:**
- `page` - Page number
- `per_page` - Items per page (max: 100)
- `search` - Search by name, email, or phone
- `sort_by` - Field to sort by (name, email, created_at)
- `sort_order` - Sort direction (asc, desc)

**Example Request:**
```
GET /customers?search=john&sort_by=name&sort_order=asc&per_page=20
```

**Response:** `200 OK`
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "tenant_id": "uuid-tenant-id",
      "name": "John Smith",
      "email": "john@example.com",
      "phone": "+1234567890",
      "phone_alt": null,
      "address": "123 Main St",
      "city": "New York",
      "postal_code": "10001",
      "national_id": "ABC123456",
      "company_name": null,
      "preferred_language": "en",
      "notes": null,
      "created_at": "2024-01-31T10:00:00.000000Z",
      "updated_at": "2024-01-31T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 45
  }
}
```

#### Create Customer

Create a new customer.

**Endpoint:** `POST /customers`  
**Auth:** Required  
**Permission:** `create-customers`

**Request:**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "phone": "+1234567890",
  "phone_alt": "+0987654321",
  "address": "456 Oak Avenue",
  "city": "Los Angeles",
  "postal_code": "90001",
  "national_id": "XYZ789012",
  "company_name": "Acme Corp",
  "preferred_language": "en",
  "notes": "VIP customer"
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "data": {
    "id": 2,
    "tenant_id": "uuid-tenant-id",
    "name": "Jane Doe",
    "email": "jane@example.com",
    "phone": "+1234567890",
    "phone_alt": "+0987654321",
    "address": "456 Oak Avenue",
    "city": "Los Angeles",
    "postal_code": "90001",
    "national_id": "XYZ789012",
    "company_name": "Acme Corp",
    "preferred_language": "en",
    "notes": "VIP customer",
    "created_at": "2024-02-01T10:00:00.000000Z"
  },
  "message": "Customer created successfully"
}
```

#### Get Customer

Retrieve a specific customer by ID.

**Endpoint:** `GET /customers/{id}`  
**Auth:** Required  
**Permission:** `view-customers`

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "id": 1,
    "tenant_id": "uuid-tenant-id",
    "name": "John Smith",
    "email": "john@example.com",
    "phone": "+1234567890",
    "vehicles": [
      {
        "id": 1,
        "registration_number": "ABC123",
        "make": "Toyota",
        "model": "Camry",
        "year": 2020
      }
    ],
    "total_invoices": 5,
    "total_spent": "2500.00",
    "created_at": "2024-01-31T10:00:00.000000Z"
  }
}
```

#### Update Customer

Update an existing customer.

**Endpoint:** `PUT /customers/{id}`  
**Auth:** Required  
**Permission:** `edit-customers`

**Request:**
```json
{
  "name": "John Smith Updated",
  "email": "john.smith@example.com",
  "phone": "+1234567890",
  "notes": "Updated notes"
}
```

**Response:** `200 OK`

#### Delete Customer

Soft delete a customer.

**Endpoint:** `DELETE /customers/{id}`  
**Auth:** Required  
**Permission:** `delete-customers`

**Response:** `204 No Content`

---

### Vehicles

#### List Vehicles

Retrieve a paginated list of vehicles.

**Endpoint:** `GET /vehicles`  
**Auth:** Required  
**Permission:** `view-vehicles`

**Query Parameters:**
- `customer_id` - Filter by customer ID
- `search` - Search by registration number, make, model
- `sort_by` - Field to sort by
- `sort_order` - Sort direction

**Response:** `200 OK`
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "tenant_id": "uuid-tenant-id",
      "customer_id": 1,
      "registration_number": "ABC123",
      "make": "Toyota",
      "model": "Camry",
      "year": 2020,
      "color": "Silver",
      "vin": "1HGBH41JXMN109186",
      "engine_number": "ENG123456",
      "current_mileage": 50000,
      "last_service_date": "2024-01-15",
      "next_service_date": "2024-07-15",
      "purchase_date": "2020-03-20",
      "notes": null,
      "customer": {
        "id": 1,
        "name": "John Smith",
        "phone": "+1234567890"
      }
    }
  ]
}
```

#### Create Vehicle

Create a new vehicle.

**Endpoint:** `POST /vehicles`  
**Auth:** Required  
**Permission:** `create-vehicles`

**Request:**
```json
{
  "customer_id": 1,
  "registration_number": "XYZ789",
  "make": "Honda",
  "model": "Accord",
  "year": 2021,
  "color": "Black",
  "vin": "1HGCV1F30LA123456",
  "engine_number": "ENG789012",
  "current_mileage": 25000,
  "last_service_date": "2024-01-01",
  "next_service_date": "2024-07-01",
  "purchase_date": "2021-05-15",
  "notes": "New vehicle"
}
```

**Response:** `201 Created`

#### Get Vehicle

**Endpoint:** `GET /vehicles/{id}`  
**Auth:** Required  
**Permission:** `view-vehicles`

**Response:** `200 OK` - Includes customer, job cards history, and service reminders

#### Update Vehicle

**Endpoint:** `PUT /vehicles/{id}`  
**Auth:** Required  
**Permission:** `edit-vehicles`

#### Delete Vehicle

**Endpoint:** `DELETE /vehicles/{id}`  
**Auth:** Required  
**Permission:** `delete-vehicles`

---

### Job Cards

#### List Job Cards

Retrieve a paginated list of job cards.

**Endpoint:** `GET /job-cards`  
**Auth:** Required  
**Permission:** `view-job-cards`

**Query Parameters:**
- `customer_id` - Filter by customer
- `vehicle_id` - Filter by vehicle
- `status` - Filter by status (pending, in_progress, completed, delivered, cancelled)
- `priority` - Filter by priority (low, medium, high, urgent)
- `assigned_to` - Filter by assigned user ID
- `date_from` - Filter from date (ISO 8601)
- `date_to` - Filter to date (ISO 8601)

**Response:** `200 OK`
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "tenant_id": "uuid-tenant-id",
      "job_number": "JC-2024-0001",
      "customer_id": 1,
      "vehicle_id": 1,
      "assigned_to": 3,
      "status": "in_progress",
      "priority": "high",
      "mileage_in": 50000,
      "mileage_out": null,
      "customer_notes": "Engine making noise",
      "internal_notes": "Check timing belt",
      "diagnosis_notes": "Timing belt needs replacement",
      "estimated_completion": "2024-02-05T17:00:00.000000Z",
      "actual_completion": null,
      "subtotal": "500.00",
      "tax_amount": "50.00",
      "discount_amount": "0.00",
      "total_amount": "550.00",
      "started_at": "2024-02-01T09:00:00.000000Z",
      "completed_at": null,
      "delivered_at": null,
      "customer": {
        "id": 1,
        "name": "John Smith",
        "phone": "+1234567890"
      },
      "vehicle": {
        "id": 1,
        "registration_number": "ABC123",
        "make": "Toyota",
        "model": "Camry"
      },
      "assigned_user": {
        "id": 3,
        "name": "Mechanic Joe"
      }
    }
  ]
}
```

#### Create Job Card

Create a new job card.

**Endpoint:** `POST /job-cards`  
**Auth:** Required  
**Permission:** `create-job-cards`

**Request:**
```json
{
  "customer_id": 1,
  "vehicle_id": 1,
  "assigned_to": 3,
  "priority": "high",
  "mileage_in": 50000,
  "customer_notes": "Engine making noise",
  "internal_notes": "Check timing belt",
  "estimated_completion": "2024-02-05T17:00:00Z"
}
```

**Response:** `201 Created` - Auto-generates job_number (e.g., JC-2024-0001)

#### Get Job Card

**Endpoint:** `GET /job-cards/{id}`  
**Auth:** Required  
**Permission:** `view-job-cards`

**Response:** `200 OK` - Includes items, customer, vehicle, assigned user, attachments

#### Update Job Card

**Endpoint:** `PUT /job-cards/{id}`  
**Auth:** Required  
**Permission:** `edit-job-cards`

#### Delete Job Card

**Endpoint:** `DELETE /job-cards/{id}`  
**Auth:** Required  
**Permission:** `delete-job-cards`

#### Add Job Card Item

Add a service item or part to a job card.

**Endpoint:** `POST /job-cards/{id}/items`  
**Auth:** Required  
**Permission:** `edit-job-cards`

**Request:**
```json
{
  "type": "service",
  "description": "Oil Change",
  "quantity": 1,
  "unit_price": "50.00",
  "tax_rate": "10.00",
  "discount": "0.00",
  "notes": "Full synthetic oil"
}
```

**Response:** `201 Created`

#### Update Job Card Status

Update the status of a job card.

**Endpoint:** `PATCH /job-cards/{id}/status`  
**Auth:** Required  
**Permission:** `edit-job-cards`

**Request:**
```json
{
  "status": "completed",
  "mileage_out": 50100,
  "actual_completion": "2024-02-05T16:30:00Z",
  "notes": "Work completed successfully"
}
```

**Response:** `200 OK`

**Valid Status Transitions:**
- `pending` → `in_progress`
- `in_progress` → `completed`, `cancelled`
- `completed` → `delivered`

---

### Invoices

#### List Invoices

**Endpoint:** `GET /invoices`  
**Auth:** Required  
**Permission:** `view-invoices`

**Query Parameters:**
- `customer_id` - Filter by customer
- `job_card_id` - Filter by job card
- `status` - Filter by status (draft, sent, paid, overdue, cancelled)
- `date_from` - Filter from invoice date
- `date_to` - Filter to invoice date

**Response:** `200 OK`
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "tenant_id": "uuid-tenant-id",
      "invoice_number": "INV-2024-0001",
      "customer_id": 1,
      "job_card_id": 1,
      "invoice_date": "2024-02-01",
      "due_date": "2024-02-15",
      "subtotal": "500.00",
      "tax_amount": "50.00",
      "discount_amount": "0.00",
      "total_amount": "550.00",
      "paid_amount": "0.00",
      "balance": "550.00",
      "status": "sent",
      "notes": null,
      "customer": {
        "id": 1,
        "name": "John Smith",
        "email": "john@example.com"
      },
      "job_card": {
        "id": 1,
        "job_number": "JC-2024-0001"
      }
    }
  ]
}
```

#### Create Invoice

**Endpoint:** `POST /invoices`  
**Auth:** Required  
**Permission:** `create-invoices`

**Request:**
```json
{
  "customer_id": 1,
  "job_card_id": 1,
  "invoice_date": "2024-02-01",
  "due_date": "2024-02-15",
  "subtotal": "500.00",
  "tax_amount": "50.00",
  "discount_amount": "0.00",
  "total_amount": "550.00",
  "notes": "Payment terms: Net 15"
}
```

**Response:** `201 Created` - Auto-generates invoice_number

#### Get Invoice

**Endpoint:** `GET /invoices/{id}`  
**Auth:** Required  
**Permission:** `view-invoices`

#### Update Invoice

**Endpoint:** `PUT /invoices/{id}`  
**Auth:** Required  
**Permission:** `edit-invoices`

#### Delete Invoice

**Endpoint:** `DELETE /invoices/{id}`  
**Auth:** Required  
**Permission:** `delete-invoices`

---

### Payments

#### List Payments

**Endpoint:** `GET /payments`  
**Auth:** Required  
**Permission:** `view-payments`

**Query Parameters:**
- `invoice_id` - Filter by invoice
- `payment_method` - Filter by method (cash, card, bank_transfer, cheque, other)
- `date_from` - Filter from payment date
- `date_to` - Filter to payment date

**Response:** `200 OK`
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "tenant_id": "uuid-tenant-id",
      "invoice_id": 1,
      "payment_date": "2024-02-01",
      "amount": "550.00",
      "payment_method": "card",
      "reference_number": "TXN123456789",
      "notes": "Paid in full",
      "invoice": {
        "id": 1,
        "invoice_number": "INV-2024-0001",
        "customer": {
          "name": "John Smith"
        }
      }
    }
  ]
}
```

#### Create Payment

**Endpoint:** `POST /payments`  
**Auth:** Required  
**Permission:** `create-payments`

**Request:**
```json
{
  "invoice_id": 1,
  "amount": "550.00",
  "payment_date": "2024-02-01",
  "payment_method": "card",
  "reference_number": "TXN123456789",
  "notes": "Visa ending in 1234"
}
```

**Response:** `201 Created` - Automatically updates invoice paid_amount and balance

#### Get Payment

**Endpoint:** `GET /payments/{id}`  
**Auth:** Required  
**Permission:** `view-payments`

#### Update Payment

**Endpoint:** `PUT /payments/{id}`  
**Auth:** Required  
**Permission:** `edit-payments`

#### Delete Payment

**Endpoint:** `DELETE /payments/{id}`  
**Auth:** Required  
**Permission:** `delete-payments`

---

### Appointments

#### List Appointments

**Endpoint:** `GET /appointments`  
**Auth:** Required  
**Permission:** `view-appointments`

**Query Parameters:**
- `customer_id` - Filter by customer
- `vehicle_id` - Filter by vehicle
- `status` - Filter by status (scheduled, confirmed, in_progress, completed, cancelled, no_show)
- `date_from` - Filter from appointment date
- `date_to` - Filter to appointment date

**Response:** `200 OK`
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "tenant_id": "uuid-tenant-id",
      "customer_id": 1,
      "vehicle_id": 1,
      "appointment_date": "2024-02-10T10:00:00.000000Z",
      "duration_minutes": 120,
      "service_type": "maintenance",
      "status": "confirmed",
      "priority": "normal",
      "description": "Regular service",
      "notes": null,
      "reminder_sent": true,
      "customer": {
        "id": 1,
        "name": "John Smith",
        "phone": "+1234567890"
      },
      "vehicle": {
        "id": 1,
        "registration_number": "ABC123",
        "make": "Toyota",
        "model": "Camry"
      }
    }
  ]
}
```

#### Create Appointment

**Endpoint:** `POST /appointments`  
**Auth:** Required  
**Permission:** `create-appointments`

**Request:**
```json
{
  "customer_id": 1,
  "vehicle_id": 1,
  "appointment_date": "2024-02-10T10:00:00Z",
  "duration_minutes": 120,
  "service_type": "maintenance",
  "priority": "normal",
  "description": "Regular service and oil change",
  "notes": "Customer prefers morning appointments"
}
```

**Response:** `201 Created`

#### Get Appointment

**Endpoint:** `GET /appointments/{id}`  
**Auth:** Required  
**Permission:** `view-appointments`

#### Update Appointment

**Endpoint:** `PUT /appointments/{id}`  
**Auth:** Required  
**Permission:** `edit-appointments`

#### Delete Appointment

**Endpoint:** `DELETE /appointments/{id}`  
**Auth:** Required  
**Permission:** `delete-appointments`

#### Confirm Appointment

Confirm a scheduled appointment.

**Endpoint:** `POST /appointments/{id}/confirm`  
**Auth:** Required  
**Permission:** `confirm-appointments`

**Request:**
```json
{
  "confirmed_by": 1,
  "notes": "Confirmed via phone"
}
```

**Response:** `200 OK`

---

### Dashboard

#### Get Dashboard Statistics

Retrieve dashboard statistics and metrics.

**Endpoint:** `GET /dashboard/stats`  
**Auth:** Required

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "total_customers": 250,
    "active_job_cards": 15,
    "pending_invoices": 8,
    "today_appointments": 5,
    "monthly_revenue": "25000.00",
    "pending_payments": "5000.00",
    "vehicles_serviced_this_month": 42
  },
  "message": "Dashboard statistics retrieved successfully"
}
```

---

## Webhooks

AutoPulse supports webhooks for real-time event notifications to external systems.

### Webhook Events

Available webhook events:

| Event | Description |
|-------|-------------|
| `customer.created` | New customer created |
| `customer.updated` | Customer updated |
| `vehicle.created` | New vehicle added |
| `job_card.created` | New job card created |
| `job_card.status_changed` | Job card status updated |
| `invoice.created` | New invoice generated |
| `invoice.paid` | Invoice fully paid |
| `payment.received` | Payment recorded |
| `appointment.created` | New appointment scheduled |
| `appointment.confirmed` | Appointment confirmed |

### Webhook Payload Format

```json
{
  "event": "job_card.status_changed",
  "timestamp": "2024-02-01T10:30:00.000000Z",
  "tenant_id": "uuid-tenant-id",
  "data": {
    "id": 1,
    "job_number": "JC-2024-0001",
    "status": "completed",
    "old_status": "in_progress",
    "customer": {
      "id": 1,
      "name": "John Smith"
    },
    "vehicle": {
      "registration_number": "ABC123"
    }
  }
}
```

### Webhook Security

All webhook requests include the following headers:

- `X-Webhook-Signature` - HMAC SHA256 signature for payload verification
- `X-Webhook-Event` - Event type
- `X-Webhook-ID` - Unique webhook call ID

**Signature Verification (PHP):**
```php
$signature = hash_hmac('sha256', $payload, $webhookSecret);
$isValid = hash_equals($signature, $_SERVER['HTTP_X_WEBHOOK_SIGNATURE']);
```

### Webhook Configuration

Webhooks can be configured via the settings API or admin panel. Each webhook requires:

- **URL** - Endpoint to receive webhook calls
- **Events** - List of events to subscribe to
- **Secret** - Shared secret for signature verification
- **Active** - Enable/disable webhook

### Retry Policy

Failed webhook calls are retried with exponential backoff:

- 1st retry: After 1 minute
- 2nd retry: After 5 minutes
- 3rd retry: After 30 minutes
- 4th retry: After 2 hours
- 5th retry: After 12 hours

After 5 failed attempts, the webhook is marked as failed and requires manual reactivation.

---

## Best Practices

### Performance Optimization

1. **Use pagination** - Always paginate list requests
2. **Filter results** - Use query parameters to reduce payload size
3. **Cache responses** - Cache non-volatile data on the client side
4. **Batch operations** - Group related operations when possible

### Security

1. **Rotate tokens** - Regularly refresh authentication tokens
2. **Use HTTPS** - Always use encrypted connections
3. **Validate input** - Sanitize and validate all user input
4. **Monitor rate limits** - Implement client-side rate limiting

### Error Recovery

1. **Implement retries** - Retry failed requests with exponential backoff
2. **Log errors** - Maintain comprehensive error logs
3. **Handle timeouts** - Set appropriate timeout values
4. **Graceful degradation** - Handle API unavailability

---

## Support

For API support and inquiries:

- **Documentation:** https://docs.autopulse.com
- **Email:** api-support@autopulse.com
- **Status:** https://status.autopulse.com

**API Updates:** Subscribe to our changelog at https://changelog.autopulse.com
