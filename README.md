# AutoPulse - Multi-Tenant Garage Management SaaS

**AutoPulse** is a production-ready, enterprise-grade garage/workshop management SaaS system built with Laravel 12, implementing modern best practices and senior engineer-level architecture.

## 🚀 Features

### Core Features
- **Multi-Tenancy**: Complete tenant isolation with domain/subdomain-based identification
- **RESTful API**: Mobile-ready API with versioning support
- **RBAC**: Role-based access control (Owner, Manager, Advisor, Mechanic, Accountant)
- **Job Card Management**: Complete workflow from diagnosis to delivery
- **Customer & Vehicle Management**: Comprehensive tracking with service reminders
- **Inventory Management**: Full ledger system with multi-location support
- **Financial Management**: Invoicing, payments, and expense tracking
- **Service Templates**: Pre-defined service packages
- **AI Diagnostics**: AI-powered diagnostic support (optional)
- **Appointment System**: Booking and scheduling
- **Notification System**: Multi-channel (SMS, WhatsApp, Email, Push)
- **Audit Logging**: Complete change tracking
- **File Management**: Polymorphic attachments with S3 support

### Technical Highlights
- **Clean Architecture**: Domain-Driven Design (DDD) principles
- **Repository Pattern**: Abstracted data access layer
- **Service Layer**: Business logic separation
- **Action Pattern**: Single-purpose action classes
- **API Resources**: Data transformation layer
- **Form Requests**: Centralized validation
- **Strict Types**: PHP 8.2+ with strict type declarations
- **PSR-12**: Code standards compliance

## 📋 Requirements

- PHP 8.2 or higher
- Composer
- MySQL 8.0+ or MariaDB 10.3+
- Node.js 18+ & NPM
- Redis (optional, for caching and queues)

## 🔧 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/solymanHossen/autoplus-car-management.git
cd autoplus-car-management
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Configuration

```bash
cp .env.example .env
```

Edit `.env` and configure your database and other services:

```env
APP_NAME="AutoPulse"
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=autopulse
DB_USERNAME=root
DB_PASSWORD=

TENANT_IDENTIFICATION_METHOD=domain
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Run Migrations

```bash
php artisan migrate
```

### 6. Seed Demo Data (Optional)

```bash
php artisan db:seed --class=PackageSeeder
php artisan db:seed --class=TaxRateSeeder
php artisan db:seed --class=DemoDataSeeder
```

### 7. Build Frontend Assets

```bash
npm run build
```

### 8. Start Development Server

```bash
php artisan serve
```

## 🗂️ Project Structure

```
app/
├── Actions/              # Single-purpose action classes
├── Domain/              # Domain models and business logic
│   ├── Tenant/
│   ├── Customer/
│   ├── Vehicle/
│   ├── JobCard/
│   ├── Invoice/
│   └── Subscription/
├── Http/
│   ├── Controllers/
│   │   ├── Api/V1/      # Versioned API controllers
│   │   └── Web/         # Web controllers
│   ├── Requests/        # Form validation requests
│   ├── Resources/       # API resources for data transformation
│   └── Middleware/      # Custom middleware
├── Models/              # Eloquent models
├── Repositories/        # Repository interfaces & implementations
├── Services/            # Business logic services
├── Observers/           # Model observers for audit logs
├── Traits/              # Reusable traits (TenantScoped, etc.)
└── Support/             # Helper classes

database/
├── factories/           # Model factories for testing
├── migrations/          # All 30 migration files
└── seeders/            # Database seeders
```

## 📚 Database Schema

The application includes 30 production-ready migrations covering:

### 1. Core Multi-Tenancy (3 tables)
- `tenants` - Tenant configuration with UUID, domain, branding
- `users` - RBAC users with role assignments
- `settings` - Tenant-specific settings (JSON)

### 2. Customer & Vehicle Management (4 tables)
- `customers` - Customer profiles with i18n support
- `vehicles` - Vehicle tracking with mileage, service history
- `vehicle_service_reminders` - Automated reminder system

### 3. Inventory & Products (5 tables)
- `suppliers` - Supplier management
- `products` - Parts & services catalog (i18n names)
- `inventory_transactions` - Full inventory ledger
- `tax_rates` - Multi-tax support

### 4. Service Operations (6 tables)
- `appointments` - Booking system
- `service_templates` & `service_template_items` - Pre-defined packages
- `job_cards` - Main work orders
- `job_card_items` - Parts & labor line items
- `ai_diagnostics` - AI-powered diagnostic data

### 5. Financial Management (4 tables)
- `invoices` - Multi-status invoicing
- `payments` - Payment ledger
- `expenses` - Shop cost tracking
- `promotions` - Discount campaigns

### 6. Communication (3 tables)
- `customer_communications` - Conversation history
- `notification_logs` - Multi-channel delivery tracking
- `customer_feedback` - Ratings & reviews

### 7. SaaS Management (3 tables)
- `packages` - Subscription plans
- `tenant_subscriptions` - Active subscriptions
- `subscription_invoices` - SaaS billing

### 8. System & Security (6 tables)
- `audit_logs` - Complete change tracking
- `attachments` - Polymorphic file storage
- `api_rate_limits` - API throttling
- `webhooks` & `webhook_calls` - Integration support
- `login_attempts` - Security tracking

## 🔐 Authentication & Authorization

### API Authentication (Laravel Sanctum)

```bash
# Login
POST /api/v1/auth/login
{
  "email": "user@example.com",
  "password": "password"
}

# Response
{
  "success": true,
  "data": {
    "token": "1|...",
    "user": {...}
  }
}
```

### Authorization Middleware

```php
Route::middleware(['auth:sanctum', 'can:view-job-cards'])->group(function () {
    Route::get('/job-cards', [JobCardController::class, 'index']);
});
```

## 🌐 API Endpoints

### Job Cards
```
GET    /api/v1/job-cards              # List job cards
POST   /api/v1/job-cards              # Create job card
GET    /api/v1/job-cards/{id}         # Show job card
PUT    /api/v1/job-cards/{id}         # Update job card
DELETE /api/v1/job-cards/{id}         # Delete job card
POST   /api/v1/job-cards/{id}/items   # Add items
PATCH  /api/v1/job-cards/{id}/status  # Update status
```

### Customers
```
GET    /api/v1/customers              # List customers
POST   /api/v1/customers              # Create customer
GET    /api/v1/customers/{id}         # Show customer
PUT    /api/v1/customers/{id}          # Update customer
DELETE /api/v1/customers/{id}         # Delete customer
```

### Vehicles
```
GET    /api/v1/vehicles               # List vehicles
POST   /api/v1/vehicles               # Create vehicle
GET    /api/v1/vehicles/{id}          # Show vehicle
PUT    /api/v1/vehicles/{id}          # Update vehicle
```

*See full API documentation in `/docs/api.md`*

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

## 🚀 Deployment

### Production Checklist

1. Set environment to production:
```env
APP_ENV=production
APP_DEBUG=false
```

2. Optimize application:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

3. Run migrations:
```bash
php artisan migrate --force
```

4. Set up queue worker:
```bash
php artisan queue:work --daemon
```

5. Set up task scheduler (cron):
```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## 📖 Documentation

- [API Documentation](/docs/api.md)
- [Database ERD](/docs/erd.md)
- [Multi-Tenancy Guide](/docs/multi-tenancy.md)
- [Contributing Guidelines](/CONTRIBUTING.md)

## 🔒 Security

- All inputs validated via Form Requests
- SQL injection prevention (Eloquent ORM)
- CSRF protection enabled
- XSS prevention
- Password hashing (bcrypt)
- API rate limiting
- Login attempt tracking

If you discover a security vulnerability, please email security@autopulse.com.

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 👥 Team

Developed by [Solyman Hossen](https://github.com/solymanHossen)

## 🤝 Contributing

Contributions are welcome! Please read our [Contributing Guidelines](CONTRIBUTING.md) first.

## 📞 Support

For support, email support@autopulse.com or open an issue on GitHub.
