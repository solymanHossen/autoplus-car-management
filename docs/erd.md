# Entity Relationship Diagram (ERD)

## Table of Contents
- [Overview](#overview)
- [Core Entities](#core-entities)
- [Entity Relationships](#entity-relationships)
- [Detailed Schema](#detailed-schema)
- [Indexes and Constraints](#indexes-and-constraints)
- [Database Diagram](#database-diagram)

---

## Overview

The AutoPulse database consists of **32 tables** implementing a multi-tenant car management system. The schema follows a single-database, shared-schema architecture where all tenants share the same database with data isolation through `tenant_id` foreign keys.

### Key Characteristics

- **Multi-tenant architecture**: All business tables include `tenant_id`
- **Soft deletes**: Core entities support soft deletion
- **UUID primary keys**: Tenants use UUIDs for security
- **Timestamps**: All tables include `created_at` and `updated_at`
- **Foreign key constraints**: Ensure referential integrity
- **Composite indexes**: Optimize tenant-scoped queries

---

## Core Entities

### Tenant Management
- **tenants** - Central tenant registry
- **users** - System users with role-based access
- **settings** - Tenant-specific configuration
- **tenant_subscriptions** - Subscription tracking
- **subscription_invoices** - Subscription billing

### Customer Management
- **customers** - Customer information
- **vehicles** - Customer-owned vehicles
- **vehicle_service_reminders** - Automated service reminders
- **customer_communications** - Communication logs
- **customer_feedback** - Customer satisfaction ratings

### Service Operations
- **appointments** - Service appointment scheduling
- **job_cards** - Service job tracking
- **job_card_items** - Individual job card services/parts
- **service_templates** - Predefined service packages
- **service_template_items** - Template line items
- **ai_diagnostics** - AI-powered diagnostic results

### Financial
- **invoices** - Customer invoices
- **payments** - Payment transactions
- **expenses** - Business expenses
- **tax_rates** - Tax configuration
- **promotions** - Discount campaigns

### Inventory
- **products** - Parts and supplies
- **suppliers** - Vendor information
- **inventory_transactions** - Stock movements
- **packages** - Bundled offerings

### System
- **attachments** - Polymorphic file storage
- **webhooks** - Webhook endpoint configuration
- **webhook_calls** - Webhook delivery log
- **audit_logs** - Activity audit trail
- **notification_logs** - Notification history
- **login_attempts** - Security tracking
- **api_rate_limits** - API throttling

---

## Entity Relationships

### Primary Relationships

```
┌──────────────┐
│   TENANTS    │ (UUID, Central entity)
└──────┬───────┘
       │
       ├─────────────────────────────────────────────────────────────┐
       │                                                               │
       ├── 1:N ──> USERS                                             │
       ├── 1:N ──> SETTINGS                                          │
       ├── 1:N ──> CUSTOMERS                                         │
       ├── 1:N ──> VEHICLES                                          │
       ├── 1:N ──> JOB_CARDS                                         │
       ├── 1:N ──> INVOICES                                          │
       ├── 1:N ──> PRODUCTS                                          │
       ├── 1:N ──> SUPPLIERS                                         │
       ├── 1:N ──> APPOINTMENTS                                      │
       └── 1:N ──> TENANT_SUBSCRIPTIONS                             │
                                                                      │
┌──────────────┐                                                     │
│  CUSTOMERS   │ (Core business entity)                             │
└──────┬───────┘                                                     │
       │                                                              │
       ├── 1:N ──> VEHICLES                                         │
       ├── 1:N ──> JOB_CARDS                                        │
       ├── 1:N ──> INVOICES                                         │
       ├── 1:N ──> APPOINTMENTS                                     │
       ├── 1:N ──> CUSTOMER_COMMUNICATIONS                          │
       └── 1:N ──> CUSTOMER_FEEDBACK                                │
                                                                      │
┌──────────────┐                                                     │
│   VEHICLES   │ (Customer assets)                                  │
└──────┬───────┘                                                     │
       │                                                              │
       ├── 1:N ──> JOB_CARDS                                        │
       ├── 1:N ──> APPOINTMENTS                                     │
       ├── 1:N ──> VEHICLE_SERVICE_REMINDERS                        │
       ├── 1:N ──> AI_DIAGNOSTICS                                   │
       └── 1:N ──> ATTACHMENTS (polymorphic)                        │
                                                                      │
┌──────────────┐                                                     │
│  JOB_CARDS   │ (Central work order)                               │
└──────┬───────┘                                                     │
       │                                                              │
       ├── N:1 ──> CUSTOMERS                                        │
       ├── N:1 ──> VEHICLES                                         │
       ├── N:1 ──> USERS (assigned_to)                              │
       ├── 1:N ──> JOB_CARD_ITEMS                                   │
       ├── 1:N ──> AI_DIAGNOSTICS                                   │
       ├── 1:1 ──> INVOICES                                         │
       ├── 1:1 ──> CUSTOMER_FEEDBACK                                │
       └── 1:N ──> ATTACHMENTS (polymorphic)                        │
                                                                      │
┌──────────────┐                                                     │
│   INVOICES   │ (Financial documents)                              │
└──────┬───────┘                                                     │
       │                                                              │
       ├── N:1 ──> CUSTOMERS                                        │
       ├── N:1 ──> JOB_CARDS (optional)                             │
       └── 1:N ──> PAYMENTS                                         │
```

### Supporting Relationships

```
PRODUCTS ──> 1:N ──> INVENTORY_TRANSACTIONS
SUPPLIERS ──> 1:N ──> PRODUCTS
SUPPLIERS ──> 1:N ──> INVENTORY_TRANSACTIONS
SERVICE_TEMPLATES ──> 1:N ──> SERVICE_TEMPLATE_ITEMS
WEBHOOKS ──> 1:N ──> WEBHOOK_CALLS
TENANT_SUBSCRIPTIONS ──> 1:N ──> SUBSCRIPTION_INVOICES

Polymorphic Relationships:
- ATTACHMENTS: attachable_type + attachable_id
  ├── JOB_CARDS
  └── VEHICLES
```

---

## Detailed Schema

### Tenants Table

```sql
CREATE TABLE tenants (
    id                    CHAR(36) PRIMARY KEY,  -- UUID
    name                  VARCHAR(255) NOT NULL,
    domain                VARCHAR(255) NULL UNIQUE,
    subdomain             VARCHAR(255) NULL UNIQUE,
    logo_url              VARCHAR(255) NULL,
    primary_color         VARCHAR(7) NULL,        -- Hex color
    subscription_status   ENUM('active', 'suspended', 'cancelled') DEFAULT 'active',
    trial_ends_at         TIMESTAMP NULL,
    created_at            TIMESTAMP NULL,
    updated_at            TIMESTAMP NULL,
    deleted_at            TIMESTAMP NULL,
    
    INDEX idx_domain (domain),
    INDEX idx_subdomain (subdomain),
    INDEX idx_subscription_status (subscription_status)
);
```

### Users Table

```sql
CREATE TABLE users (
    id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id         CHAR(36) NOT NULL,
    name              VARCHAR(255) NOT NULL,
    email             VARCHAR(255) NOT NULL,
    phone             VARCHAR(20) NULL,
    avatar_url        VARCHAR(255) NULL,
    role              ENUM('owner', 'manager', 'advisor', 'mechanic', 'accountant'),
    password          VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token    VARCHAR(100) NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_email_per_tenant (tenant_id, email),
    INDEX idx_tenant_role (tenant_id, role)
);
```

### Customers Table

```sql
CREATE TABLE customers (
    id                  BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id           CHAR(36) NOT NULL,
    name                VARCHAR(255) NOT NULL,
    name_local          JSON NULL,                -- Localized names
    email               VARCHAR(255) NULL,
    phone               VARCHAR(20) NOT NULL,
    phone_alt           VARCHAR(20) NULL,
    address             TEXT NULL,
    city                VARCHAR(100) NULL,
    postal_code         VARCHAR(20) NULL,
    national_id         VARCHAR(50) NULL,
    company_name        VARCHAR(255) NULL,
    preferred_language  VARCHAR(5) DEFAULT 'en',
    notes               TEXT NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant_created (tenant_id, created_at),
    INDEX idx_phone (phone),
    INDEX idx_email (email)
);
```

### Vehicles Table

```sql
CREATE TABLE vehicles (
    id                   BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id            CHAR(36) NOT NULL,
    customer_id          BIGINT UNSIGNED NOT NULL,
    registration_number  VARCHAR(20) NOT NULL,
    make                 VARCHAR(100) NOT NULL,
    model                VARCHAR(100) NOT NULL,
    year                 INTEGER NOT NULL,
    color                VARCHAR(50) NULL,
    vin                  VARCHAR(17) NULL,        -- Vehicle Identification Number
    engine_number        VARCHAR(50) NULL,
    current_mileage      INTEGER NOT NULL DEFAULT 0,
    last_service_date    DATE NULL,
    next_service_date    DATE NULL,
    purchase_date        DATE NULL,
    notes                TEXT NULL,
    created_at           TIMESTAMP NULL,
    updated_at           TIMESTAMP NULL,
    deleted_at           TIMESTAMP NULL,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration_per_tenant (tenant_id, registration_number),
    INDEX idx_tenant_customer (tenant_id, customer_id),
    INDEX idx_next_service (tenant_id, next_service_date)
);
```

### Job Cards Table

```sql
CREATE TABLE job_cards (
    id                    BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id             CHAR(36) NOT NULL,
    job_number            VARCHAR(50) NOT NULL,   -- Auto-generated: JC-2024-0001
    customer_id           BIGINT UNSIGNED NOT NULL,
    vehicle_id            BIGINT UNSIGNED NOT NULL,
    assigned_to           BIGINT UNSIGNED NULL,   -- User ID
    status                ENUM('pending', 'in_progress', 'completed', 'delivered', 'cancelled') DEFAULT 'pending',
    priority              ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    mileage_in            INTEGER NULL,
    mileage_out           INTEGER NULL,
    customer_notes        TEXT NULL,
    internal_notes        TEXT NULL,
    diagnosis_notes       TEXT NULL,
    estimated_completion  TIMESTAMP NULL,
    actual_completion     TIMESTAMP NULL,
    subtotal              DECIMAL(10,2) DEFAULT 0.00,
    tax_amount            DECIMAL(10,2) DEFAULT 0.00,
    discount_amount       DECIMAL(10,2) DEFAULT 0.00,
    total_amount          DECIMAL(10,2) DEFAULT 0.00,
    started_at            TIMESTAMP NULL,
    completed_at          TIMESTAMP NULL,
    delivered_at          TIMESTAMP NULL,
    created_at            TIMESTAMP NULL,
    updated_at            TIMESTAMP NULL,
    deleted_at            TIMESTAMP NULL,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_job_number_per_tenant (tenant_id, job_number),
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_tenant_customer (tenant_id, customer_id),
    INDEX idx_tenant_vehicle (tenant_id, vehicle_id),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_estimated_completion (tenant_id, estimated_completion)
);
```

### Job Card Items Table

```sql
CREATE TABLE job_card_items (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id       CHAR(36) NOT NULL,
    job_card_id     BIGINT UNSIGNED NOT NULL,
    product_id      BIGINT UNSIGNED NULL,
    type            ENUM('service', 'part', 'labour') NOT NULL,
    description     TEXT NOT NULL,
    quantity        DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    unit_price      DECIMAL(10,2) NOT NULL,
    tax_rate        DECIMAL(5,2) DEFAULT 0.00,
    discount        DECIMAL(10,2) DEFAULT 0.00,
    subtotal        DECIMAL(10,2) NOT NULL,
    total           DECIMAL(10,2) NOT NULL,
    notes           TEXT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (job_card_id) REFERENCES job_cards(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_tenant_job_card (tenant_id, job_card_id)
);
```

### Invoices Table

```sql
CREATE TABLE invoices (
    id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id         CHAR(36) NOT NULL,
    invoice_number    VARCHAR(50) NOT NULL,      -- Auto-generated: INV-2024-0001
    customer_id       BIGINT UNSIGNED NOT NULL,
    job_card_id       BIGINT UNSIGNED NULL,
    invoice_date      DATE NOT NULL,
    due_date          DATE NOT NULL,
    subtotal          DECIMAL(10,2) NOT NULL,
    tax_amount        DECIMAL(10,2) DEFAULT 0.00,
    discount_amount   DECIMAL(10,2) DEFAULT 0.00,
    total_amount      DECIMAL(10,2) NOT NULL,
    paid_amount       DECIMAL(10,2) DEFAULT 0.00,
    balance           DECIMAL(10,2) NOT NULL,
    status            ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    notes             TEXT NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    deleted_at        TIMESTAMP NULL,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (job_card_id) REFERENCES job_cards(id) ON DELETE SET NULL,
    UNIQUE KEY unique_invoice_number_per_tenant (tenant_id, invoice_number),
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_tenant_customer (tenant_id, customer_id),
    INDEX idx_due_date (tenant_id, due_date)
);
```

### Payments Table

```sql
CREATE TABLE payments (
    id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id         CHAR(36) NOT NULL,
    invoice_id        BIGINT UNSIGNED NOT NULL,
    payment_date      DATE NOT NULL,
    amount            DECIMAL(10,2) NOT NULL,
    payment_method    ENUM('cash', 'card', 'bank_transfer', 'cheque', 'other') NOT NULL,
    reference_number  VARCHAR(100) NULL,
    notes             TEXT NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    deleted_at        TIMESTAMP NULL,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    INDEX idx_tenant_invoice (tenant_id, invoice_id),
    INDEX idx_payment_date (tenant_id, payment_date)
);
```

### Appointments Table

```sql
CREATE TABLE appointments (
    id                  BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id           CHAR(36) NOT NULL,
    customer_id         BIGINT UNSIGNED NOT NULL,
    vehicle_id          BIGINT UNSIGNED NOT NULL,
    appointment_date    TIMESTAMP NOT NULL,
    duration_minutes    INTEGER DEFAULT 60,
    service_type        VARCHAR(100) NULL,
    status              ENUM('scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    priority            ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    description         TEXT NULL,
    notes               TEXT NULL,
    reminder_sent       BOOLEAN DEFAULT FALSE,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    INDEX idx_tenant_date (tenant_id, appointment_date),
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_reminder (tenant_id, reminder_sent, appointment_date)
);
```

### Products Table

```sql
CREATE TABLE products (
    id                 BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id          CHAR(36) NOT NULL,
    supplier_id        BIGINT UNSIGNED NULL,
    sku                VARCHAR(50) NOT NULL,
    name               VARCHAR(255) NOT NULL,
    name_local         JSON NULL,
    description        TEXT NULL,
    category           VARCHAR(100) NULL,
    unit_price         DECIMAL(10,2) NOT NULL,
    cost_price         DECIMAL(10,2) NULL,
    quantity_in_stock  INTEGER DEFAULT 0,
    reorder_level      INTEGER DEFAULT 0,
    unit_of_measure    VARCHAR(20) DEFAULT 'unit',
    tax_rate           DECIMAL(5,2) DEFAULT 0.00,
    is_active          BOOLEAN DEFAULT TRUE,
    created_at         TIMESTAMP NULL,
    updated_at         TIMESTAMP NULL,
    deleted_at         TIMESTAMP NULL,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    UNIQUE KEY unique_sku_per_tenant (tenant_id, sku),
    INDEX idx_tenant_category (tenant_id, category),
    INDEX idx_reorder (tenant_id, quantity_in_stock, reorder_level)
);
```

### Attachments Table (Polymorphic)

```sql
CREATE TABLE attachments (
    id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id         CHAR(36) NOT NULL,
    attachable_type   VARCHAR(255) NOT NULL,     -- Model class name
    attachable_id     BIGINT UNSIGNED NOT NULL,  -- Model ID
    file_name         VARCHAR(255) NOT NULL,
    file_path         VARCHAR(500) NOT NULL,
    file_type         VARCHAR(50) NULL,
    file_size         BIGINT NULL,               -- Bytes
    mime_type         VARCHAR(100) NULL,
    uploaded_by       BIGINT UNSIGNED NULL,      -- User ID
    description       TEXT NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_attachable (tenant_id, attachable_type, attachable_id)
);
```

### Additional Support Tables

**Suppliers, Expenses, Tax Rates, Promotions, Service Templates, AI Diagnostics, Customer Communications, Customer Feedback, Vehicle Service Reminders, Webhooks, Audit Logs, Notification Logs, Login Attempts, API Rate Limits, Packages, Tenant Subscriptions, Subscription Invoices**

(See migration files for complete schemas)

---

## Indexes and Constraints

### Foreign Key Constraints

All tenant-scoped tables include:
```sql
FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
```

This ensures:
- **Referential integrity**: Invalid tenant IDs cannot be inserted
- **Cascade deletion**: Deleting a tenant removes all related data
- **Data consistency**: Orphaned records are prevented

### Composite Indexes

Optimized for common tenant-scoped queries:

```sql
-- Tenant + timestamp for chronological queries
INDEX idx_tenant_created (tenant_id, created_at)

-- Tenant + status for filtering
INDEX idx_tenant_status (tenant_id, status)

-- Tenant + foreign key for joins
INDEX idx_tenant_customer (tenant_id, customer_id)
INDEX idx_tenant_vehicle (tenant_id, vehicle_id)
```

### Unique Constraints

Prevent duplicates within tenant scope:

```sql
-- One email per tenant
UNIQUE KEY unique_email_per_tenant (tenant_id, email)

-- Unique registration numbers per tenant
UNIQUE KEY unique_registration_per_tenant (tenant_id, registration_number)

-- Unique job/invoice numbers per tenant
UNIQUE KEY unique_job_number_per_tenant (tenant_id, job_number)
UNIQUE KEY unique_invoice_number_per_tenant (tenant_id, invoice_number)
```

---

## Database Diagram

### ASCII Entity Relationship Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                            AUTOPULSE DATABASE SCHEMA                         │
└─────────────────────────────────────────────────────────────────────────────┘

┌────────────────┐
│    TENANTS     │ (UUID Primary Key)
├────────────────┤
│ • id (PK)      │
│ • name         │
│ • domain       │──────────────────┐
│ • subdomain    │                  │
│ • logo_url     │                  │
│ • primary_color│                  │
│ • subscription │                  │
│   _status      │                  │
│ • trial_ends_at│                  │
└────────┬───────┘                  │
         │                           │
         │                           │
         ├───────────────────────────┼──────────┐
         │                           │          │
         ▼                           ▼          ▼
┌────────────────┐         ┌────────────────┐ ┌────────────────┐
│     USERS      │         │   SETTINGS     │ │TENANT_SUBSCRIP │
├────────────────┤         ├────────────────┤ ├────────────────┤
│ • id (PK)      │         │ • id (PK)      │ │ • id (PK)      │
│ • tenant_id FK │─────┐   │ • tenant_id FK │ │ • tenant_id FK │
│ • name         │     │   │ • key          │ │ • package_id FK│
│ • email        │     │   │ • value        │ │ • status       │
│ • role         │     │   │ • type         │ │ • start_date   │
│ • password     │     │   └────────────────┘ │ • end_date     │
└────────────────┘     │                      └────────────────┘
                       │
         ┌─────────────┴────────────────────────┐
         │                                      │
         │                                      │
         ▼                                      ▼
┌────────────────┐                    ┌────────────────┐
│   CUSTOMERS    │                    │   JOB_CARDS    │
├────────────────┤                    ├────────────────┤
│ • id (PK)      │◄───────────────────┤ • id (PK)      │
│ • tenant_id FK │                    │ • tenant_id FK │
│ • name         │                    │ • job_number   │
│ • email        │                    │ • customer_id  │
│ • phone        │                    │ • vehicle_id   │
│ • address      │                    │ • assigned_to  │
│ • national_id  │                    │ • status       │
│ • company_name │                    │ • priority     │
└────────┬───────┘                    │ • mileage_in   │
         │                             │ • total_amount │
         │                             │ • started_at   │
         │                             │ • completed_at │
         │                             └───────┬────────┘
         │                                     │
         │                                     │
         ├─────────────────┐                   ├──────────────┬──────────────┐
         │                 │                   │              │              │
         ▼                 ▼                   ▼              ▼              ▼
┌────────────────┐ ┌────────────────┐ ┌──────────────┐ ┌──────────┐ ┌──────────────┐
│   VEHICLES     │ │ CUSTOMER_COMM  │ │JOB_CARD_ITEMS│ │ATTACHMENT│ │AI_DIAGNOSTICS│
├────────────────┤ ├────────────────┤ ├──────────────┤ ├──────────┤ ├──────────────┤
│ • id (PK)      │ │ • id (PK)      │ │ • id (PK)    │ │ • id (PK)│ │ • id (PK)    │
│ • tenant_id FK │ │ • tenant_id FK │ │ • tenant_id  │ │ • tenant │ │ • tenant_id  │
│ • customer_id  │ │ • customer_id  │ │ • job_card_id│ │   _id    │ │ • job_card_id│
│ • registration │ │ • type         │ │ • product_id │ │ • attach │ │ • vehicle_id │
│   _number      │ │ • subject      │ │ • type       │ │   able_  │ │ • symptoms   │
│ • make         │ │ • content      │ │ • quantity   │ │   type   │ │ • diagnosis  │
│ • model        │ │ • sent_at      │ │ • unit_price │ │ • attach │ │ • confidence │
│ • year         │ └────────────────┘ │ • total      │ │   able_  │ └──────────────┘
│ • vin          │                    └──────────────┘ │   id     │
│ • engine_no    │                                     │ • file_  │
│ • current_     │                                     │   path   │
│   mileage      │                                     └──────────┘
└────────┬───────┘                                  (Polymorphic)
         │
         │
         ├───────────────────┬──────────────────┐
         │                   │                  │
         ▼                   ▼                  ▼
┌────────────────┐  ┌─────────────────┐ ┌────────────────┐
│ VEHICLE_SERVICE│  │  APPOINTMENTS   │ │   ATTACHMENT   │
│   _REMINDERS   │  ├─────────────────┤ │  (Polymorphic) │
├────────────────┤  │ • id (PK)       │ └────────────────┘
│ • id (PK)      │  │ • tenant_id FK  │
│ • tenant_id FK │  │ • customer_id FK│
│ • vehicle_id FK│  │ • vehicle_id FK │
│ • service_type │  │ • appointment_  │
│ • due_date     │  │   date          │
│ • reminder_sent│  │ • duration_     │
└────────────────┘  │   minutes       │
                    │ • status        │
                    └─────────────────┘

┌────────────────┐
│   INVOICES     │
├────────────────┤
│ • id (PK)      │
│ • tenant_id FK │◄────────┐
│ • invoice_     │         │
│   number       │         │
│ • customer_id  │         │
│ • job_card_id  │         │
│ • invoice_date │         │
│ • due_date     │         │
│ • total_amount │         │
│ • paid_amount  │         │
│ • balance      │         │
│ • status       │         │
└────────┬───────┘         │
         │                 │
         │                 │
         ▼                 │
┌────────────────┐         │
│   PAYMENTS     │         │
├────────────────┤         │
│ • id (PK)      │         │
│ • tenant_id FK │─────────┘
│ • invoice_id FK│
│ • payment_date │
│ • amount       │
│ • payment_     │
│   method       │
│ • reference_no │
└────────────────┘

┌────────────────┐                    ┌────────────────┐
│   PRODUCTS     │                    │   SUPPLIERS    │
├────────────────┤                    ├────────────────┤
│ • id (PK)      │                    │ • id (PK)      │
│ • tenant_id FK │                    │ • tenant_id FK │
│ • supplier_id  │◄───────────────────┤ • name         │
│ • sku          │                    │ • contact_     │
│ • name         │                    │   person       │
│ • unit_price   │                    │ • email        │
│ • cost_price   │                    │ • phone        │
│ • quantity_in_ │                    │ • address      │
│   stock        │                    └────────────────┘
│ • reorder_level│
└────────┬───────┘
         │
         │
         ▼
┌────────────────┐
│  INVENTORY_    │
│  TRANSACTIONS  │
├────────────────┤
│ • id (PK)      │
│ • tenant_id FK │
│ • product_id FK│
│ • supplier_id  │
│ • type         │
│ • quantity     │
│ • unit_cost    │
│ • total_cost   │
│ • reference    │
└────────────────┘

┌────────────────┐         ┌────────────────┐
│SERVICE_TEMPLATE│         │SERVICE_TEMPLATE│
│                │         │    _ITEMS      │
├────────────────┤         ├────────────────┤
│ • id (PK)      │◄────────┤ • id (PK)      │
│ • tenant_id FK │         │ • tenant_id FK │
│ • name         │         │ • template_id  │
│ • description  │         │ • description  │
│ • default_price│         │ • quantity     │
│ • duration_min │         │ • unit_price   │
└────────────────┘         └────────────────┘

┌────────────────┐         ┌────────────────┐
│   WEBHOOKS     │         │ WEBHOOK_CALLS  │
├────────────────┤         ├────────────────┤
│ • id (PK)      │◄────────┤ • id (PK)      │
│ • tenant_id FK │         │ • webhook_id FK│
│ • url          │         │ • event        │
│ • events[]     │         │ • payload      │
│ • secret       │         │ • response     │
│ • is_active    │         │ • status_code  │
└────────────────┘         │ • attempted_at │
                           └────────────────┘

SYSTEM TABLES:
┌────────────────┐  ┌────────────────┐  ┌────────────────┐
│  AUDIT_LOGS    │  │NOTIFICATION_LOG│  │ LOGIN_ATTEMPTS │
├────────────────┤  ├────────────────┤  ├────────────────┤
│ • id (PK)      │  │ • id (PK)      │  │ • id (PK)      │
│ • tenant_id FK │  │ • tenant_id FK │  │ • email        │
│ • user_id      │  │ • user_id      │  │ • ip_address   │
│ • action       │  │ • type         │  │ • user_agent   │
│ • model_type   │  │ • channel      │  │ • successful   │
│ • model_id     │  │ • content      │  │ • attempted_at │
│ • old_values   │  │ • sent_at      │  └────────────────┘
│ • new_values   │  └────────────────┘
│ • ip_address   │
└────────────────┘

LEGEND:
────────  One-to-Many Relationship
◄───────  Foreign Key Reference
(PK)      Primary Key
(FK)      Foreign Key
```

---

## Key Relationships Summary

| Parent | Child | Type | Description |
|--------|-------|------|-------------|
| Tenants | Users | 1:N | Each tenant has multiple users |
| Tenants | Customers | 1:N | Each tenant has multiple customers |
| Tenants | Vehicles | 1:N | All vehicles belong to a tenant |
| Tenants | Job Cards | 1:N | All job cards belong to a tenant |
| Customers | Vehicles | 1:N | Customers own multiple vehicles |
| Customers | Job Cards | 1:N | Customers have multiple job cards |
| Customers | Invoices | 1:N | Customers receive multiple invoices |
| Customers | Appointments | 1:N | Customers book multiple appointments |
| Vehicles | Job Cards | 1:N | Vehicles have service history |
| Vehicles | Appointments | 1:N | Vehicles scheduled for service |
| Vehicles | Attachments | 1:N | Vehicle photos/documents (polymorphic) |
| Job Cards | Job Card Items | 1:N | Job cards contain multiple line items |
| Job Cards | Invoice | 1:1 | Job card generates one invoice |
| Job Cards | Attachments | 1:N | Before/after photos (polymorphic) |
| Invoices | Payments | 1:N | Invoices can have multiple payments |
| Products | Inventory Trans. | 1:N | Track product stock movements |
| Suppliers | Products | 1:N | Suppliers provide products |
| Service Templates | Template Items | 1:N | Templates contain service items |
| Webhooks | Webhook Calls | 1:N | Track webhook delivery attempts |

---

## Migration Order

For database setup, migrations must run in this order to respect foreign key constraints:

1. **Core**: tenants, users, cache, jobs, personal_access_tokens
2. **Configuration**: settings, tax_rates, suppliers, packages
3. **Business Core**: customers, vehicles, products
4. **Support**: vehicle_service_reminders, inventory_transactions, promotions
5. **Operations**: appointments, service_templates, service_template_items
6. **Work Orders**: job_cards, job_card_items, ai_diagnostics
7. **Financial**: invoices, payments, expenses
8. **Communication**: customer_communications, notification_logs, customer_feedback
9. **Subscription**: tenant_subscriptions, subscription_invoices
10. **System**: audit_logs, attachments, api_rate_limits, webhooks, webhook_calls, login_attempts

---

## Performance Considerations

### Indexing Strategy

1. **Tenant Scoping**: All queries filter by `tenant_id` first
2. **Composite Indexes**: `(tenant_id, frequently_queried_column)`
3. **Foreign Keys**: Automatically indexed for join performance
4. **Unique Constraints**: Also serve as indexes

### Query Optimization

```sql
-- Efficient: Uses composite index
SELECT * FROM customers 
WHERE tenant_id = ? AND city = 'New York';

-- Efficient: Composite index on (tenant_id, status)
SELECT * FROM job_cards 
WHERE tenant_id = ? AND status = 'in_progress';

-- Efficient: Foreign key index
SELECT * FROM vehicles 
WHERE tenant_id = ? AND customer_id = ?;
```

### Partitioning Considerations

For large-scale deployments, consider partitioning by `tenant_id`:

```sql
-- Example: Partition job_cards by tenant_id range
ALTER TABLE job_cards PARTITION BY HASH(tenant_id) PARTITIONS 10;
```

---

For detailed API usage and multi-tenancy implementation, see:
- [API Documentation](./api.md)
- [Multi-Tenancy Guide](./multi-tenancy.md)
- [Contributing Guidelines](../CONTRIBUTING.md)
