# AutoPulse Missing Modules Implementation Plan

## Goal
Implement remaining domain modules safely in dependency order, with tenant isolation, RBAC, validation, resources, and feature tests.

## Current Baseline
Already implemented API modules:
- Auth
- Customers
- Vehicles
- Job Cards
- Invoices
- Payments
- Appointments
- Attachments
- Dashboard stats

## Dependency Order (Best Way)

### Phase 1 — Core Operations (highest business value)
1. Supplier
2. Product
3. InventoryTransaction
4. Expense
5. TaxRate
6. ServiceTemplate
7. ServiceTemplateItem

Reason: these power day-to-day workshop operations and financial accuracy.

### Phase 2 — Customer Lifecycle + Service Intelligence
1. VehicleServiceReminder
2. CustomerCommunication
3. CustomerFeedback
4. AiDiagnostic
5. NotificationLog

Reason: improves retention, communication, and service quality workflow.

### Phase 3 — Platform/SaaS Administration
1. Setting
2. User (tenant user management endpoints)
3. Package
4. TenantSubscription
5. SubscriptionInvoice
6. Tenant

Reason: admin and subscription lifecycle should come after core operations are stable.

### Phase 4 — Audit, Security, Integrations
1. AuditLog (read API only)
2. LoginAttempt (read API only)
3. ApiRateLimit (read API only / observability)
4. Webhook
5. WebhookCall

Reason: observability and external integrations are safest after internal modules stabilize.

## Per-Module Definition of Done
For each model:
1. API controller (index/store/show/update/destroy as needed)
2. Form requests for store/update validation
3. API resource(s)
4. RBAC route wiring in routes/api.php
5. Tenant-safe query constraints
6. Feature tests:
   - CRUD happy path
   - cross-tenant isolation
   - validation failures
7. OpenAPI update
8. docs/api.md update

## RBAC Naming Convention
- view-{module}
- create-{module}
- edit-{module}
- delete-{module}

Keep middleware consistent with existing style.

## Suggested Delivery Slices
- Slice A: Supplier + Product + TaxRate
- Slice B: InventoryTransaction + Expense
- Slice C: ServiceTemplate + ServiceTemplateItem
- Slice D: VehicleServiceReminder + CustomerCommunication + CustomerFeedback
- Slice E: AiDiagnostic + NotificationLog
- Slice F: SaaS admin models
- Slice G: Webhook + WebhookCall + observability reads

Each slice should ship with routes, tests, and docs in the same PR.
