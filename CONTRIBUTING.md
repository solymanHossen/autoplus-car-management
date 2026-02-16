# Contributing to AutoPulse

Thank you for your interest in contributing to AutoPulse! This document provides guidelines and instructions for contributing to the project.

## Table of Contents
- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Code Standards](#code-standards)
- [Testing Requirements](#testing-requirements)
- [Commit Message Guidelines](#commit-message-guidelines)
- [Pull Request Process](#pull-request-process)
- [Code Review Process](#code-review-process)
- [Documentation](#documentation)
- [Reporting Bugs](#reporting-bugs)
- [Suggesting Features](#suggesting-features)

---

## Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inclusive environment for all contributors. We expect all participants to:

- Use welcoming and inclusive language
- Be respectful of differing viewpoints and experiences
- Gracefully accept constructive criticism
- Focus on what is best for the community
- Show empathy towards other community members

### Unacceptable Behavior

- Harassment, trolling, or derogatory comments
- Personal or political attacks
- Public or private harassment
- Publishing others' private information without permission
- Any conduct that could be considered inappropriate in a professional setting

---

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer 2.x
- Node.js 18+ and npm
- MySQL 8.0+ or MariaDB 10.5+
- Git

### Fork and Clone

1. Fork the repository on GitHub
2. Clone your fork locally:

```bash
git clone https://github.com/YOUR_USERNAME/autopulse.git
cd autopulse
```

3. Add the upstream repository:

```bash
git remote add upstream https://github.com/ORIGINAL_OWNER/autopulse.git
```

### Local Setup

1. **Install PHP dependencies:**
```bash
composer install
```

2. **Install Node dependencies:**
```bash
npm install
```

3. **Environment configuration:**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure database:**
Edit `.env` and set your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=autopulse
DB_USERNAME=root
DB_PASSWORD=your_password
```

5. **Run migrations:**
```bash
php artisan migrate
```

6. **Seed database (optional):**
```bash
php artisan db:seed
```

7. **Build assets:**
```bash
npm run dev
```

8. **Start development server:**
```bash
php artisan serve
```

---

## Development Workflow

### Branching Strategy

We follow a **Git Flow** branching model:

- `main` - Production-ready code
- `develop` - Integration branch for features
- `feature/*` - New features and enhancements
- `bugfix/*` - Bug fixes
- `hotfix/*` - Urgent production fixes
- `release/*` - Release preparation

### Creating a Feature Branch

1. **Ensure your develop branch is up to date:**
```bash
git checkout develop
git pull upstream develop
```

2. **Create a new feature branch:**
```bash
git checkout -b feature/your-feature-name
```

**Branch Naming Convention:**
- `feature/add-customer-export` - New features
- `bugfix/fix-invoice-calculation` - Bug fixes
- `hotfix/security-patch-auth` - Urgent fixes
- `refactor/optimize-job-card-query` - Code improvements
- `docs/update-api-documentation` - Documentation updates

### Making Changes

1. Make your changes in your feature branch
2. Test your changes thoroughly
3. Commit your changes (see [Commit Guidelines](#commit-message-guidelines))
4. Push to your fork:

```bash
git push origin feature/your-feature-name
```

---

## Code Standards

### PHP Standards

AutoPulse follows **PSR-12** coding standards with strict type declarations.

#### Key Requirements

✅ **Strict Types**
All PHP files must declare strict types:
```php
<?php

declare(strict_types=1);

namespace App\Models;
```

✅ **Type Hints**
Always use type hints for parameters and return types:
```php
// ✅ Good
public function calculateTotal(float $subtotal, float $taxRate): float
{
    return $subtotal * (1 + $taxRate);
}

// ❌ Bad
public function calculateTotal($subtotal, $taxRate)
{
    return $subtotal * (1 + $taxRate);
}
```

✅ **Return Types**
Declare return types for all methods:
```php
public function getCustomers(): Collection
{
    return Customer::all();
}

public function processPayment(): void
{
    // Process payment
}

public function findCustomer(int $id): ?Customer
{
    return Customer::find($id);
}
```

✅ **Property Types (PHP 7.4+)**
Use typed properties:
```php
class Customer extends Model
{
    protected string $name;
    protected ?string $email;
    protected array $metadata;
}
```

#### Laravel Best Practices

✅ **Eloquent over Query Builder**
```php
// ✅ Preferred
$customers = Customer::where('city', 'New York')->get();

// ❌ Avoid when Eloquent is suitable
$customers = DB::table('customers')->where('city', 'New York')->get();
```

✅ **Route Model Binding**
```php
// ✅ Good
public function show(Customer $customer): JsonResponse
{
    return response()->json($customer);
}

// ❌ Bad
public function show(int $id): JsonResponse
{
    $customer = Customer::findOrFail($id);
    return response()->json($customer);
}
```

✅ **Form Requests for Validation**
```php
// ✅ Good
public function store(StoreCustomerRequest $request): JsonResponse
{
    $customer = Customer::create($request->validated());
    return response()->json($customer, 201);
}

// ❌ Bad
public function store(Request $request): JsonResponse
{
    $request->validate([
        'name' => 'required',
        // ... inline validation
    ]);
}
```

✅ **Resource Transformers**
Use API Resources for consistent response formatting:
```php
// ✅ Good
return CustomerResource::collection($customers);

// ❌ Bad
return response()->json($customers);
```

### Code Formatting

Use **Laravel Pint** for automatic code formatting:

```bash
# Format all files
./vendor/bin/pint

# Format specific file
./vendor/bin/pint app/Models/Customer.php

# Check without fixing
./vendor/bin/pint --test
```

Pint configuration is in `pint.json` (if present) and follows PSR-12 standards.

### JavaScript Standards

- Use ES6+ features
- Prefer `const` and `let` over `var`
- Use arrow functions where appropriate
- Follow consistent naming conventions
- Use semicolons

### CSS/SCSS Standards

- Use meaningful class names
- Follow BEM methodology where applicable
- Avoid deep nesting (max 3 levels)
- Use variables for colors and common values

---

## Testing Requirements

All contributions must include tests. We maintain high code coverage standards.

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/CustomerTest.php

# Run with coverage
php artisan test --coverage

# Run with detailed output
php artisan test --parallel
```

### Writing Tests

#### Feature Tests

Test complete workflows and API endpoints:

```php
<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    public function test_can_create_customer(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/customers', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'email', 'phone'],
                'message',
            ]);

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'email' => 'john@example.com',
        ]);
    }

    public function test_customer_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/customers', [
            'name' => 'John Doe',
        ]);

        $response->assertStatus(401);
    }

    public function test_customers_are_tenant_scoped(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
        $user2 = User::factory()->create(['tenant_id' => $tenant2->id]);

        $customer1 = Customer::factory()->create(['tenant_id' => $tenant1->id]);
        $customer2 = Customer::factory()->create(['tenant_id' => $tenant2->id]);

        // User 1 should only see their customer
        $response = $this->actingAs($user1)->getJson('/api/v1/customers');
        $response->assertJsonCount(1, 'data');

        // User 2 should only see their customer
        $response = $this->actingAs($user2)->getJson('/api/v1/customers');
        $response->assertJsonCount(1, 'data');
    }
}
```

#### Unit Tests

Test individual methods and business logic:

```php
<?php

namespace Tests\Unit;

use App\Models\JobCard;
use Tests\TestCase;

class JobCardTest extends TestCase
{
    public function test_calculates_total_correctly(): void
    {
        $jobCard = new JobCard([
            'subtotal' => 1000.00,
            'tax_amount' => 100.00,
            'discount_amount' => 50.00,
        ]);

        $this->assertEquals(1050.00, $jobCard->calculateTotal());
    }

    public function test_can_transition_status(): void
    {
        $jobCard = JobCard::factory()->create(['status' => 'pending']);

        $jobCard->transitionTo('in_progress');

        $this->assertEquals('in_progress', $jobCard->status);
        $this->assertNotNull($jobCard->started_at);
    }
}
```

### Test Coverage Requirements

- **Minimum coverage:** 80% overall
- **Feature tests:** All API endpoints must be tested
- **Unit tests:** Complex business logic must have unit tests
- **Edge cases:** Test boundary conditions and error scenarios

### Testing Best Practices

✅ **Use factories for test data:**
```php
$customer = Customer::factory()->create(['name' => 'Test Customer']);
```

✅ **Test one thing per test method:**
```php
// ✅ Good
public function test_customer_requires_name(): void { }
public function test_customer_email_must_be_valid(): void { }

// ❌ Bad
public function test_customer_validation(): void { }
```

✅ **Use descriptive test names:**
```php
// ✅ Good
public function test_cannot_delete_customer_with_active_job_cards(): void

// ❌ Bad
public function test_delete(): void
```

✅ **Clean up after tests:**
```php
protected function setUp(): void
{
    parent::setUp();
    // Setup test data
}

protected function tearDown(): void
{
    // Clean up
    parent::tearDown();
}
```

---

## Commit Message Guidelines

We follow the **Conventional Commits** specification for clear and searchable commit history.

### Commit Message Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, semicolons, etc.)
- `refactor`: Code refactoring (no functionality change)
- `perf`: Performance improvements
- `test`: Adding or updating tests
- `chore`: Build process, dependencies, tooling
- `ci`: CI/CD configuration changes

### Scope

The scope specifies what part of the codebase is affected:

- `api`: API changes
- `auth`: Authentication
- `customers`: Customer management
- `vehicles`: Vehicle management
- `job-cards`: Job card functionality
- `invoices`: Invoice management
- `payments`: Payment processing
- `multi-tenancy`: Multi-tenancy features
- `database`: Database migrations/schema

### Examples

```
feat(customers): add customer export to CSV functionality

Implement CSV export for customer list with filtering options.
Includes pagination support for large datasets.

Closes #123
```

```
fix(invoices): correct tax calculation for multi-line items

Tax was being calculated on subtotal only. Now correctly applies
tax to each line item individually.

Fixes #456
```

```
docs(api): update authentication section with token refresh examples

Added examples for token refresh endpoint and clarified
token expiration behavior.
```

```
refactor(job-cards): optimize query performance for job card listing

- Added composite index on (tenant_id, status)
- Eager load relationships to reduce N+1 queries
- Reduced query time from 450ms to 85ms

Performance: #789
```

```
test(appointments): add tests for appointment confirmation flow

Added feature tests covering:
- Successful confirmation
- Invalid appointment ID
- Permission checks
- Notification sending
```

### Commit Message Rules

✅ Use present tense ("add feature" not "added feature")  
✅ Use imperative mood ("move cursor to..." not "moves cursor to...")  
✅ First line max 72 characters  
✅ Reference issues and pull requests in footer  
✅ Explain *what* and *why*, not *how*

---

## Pull Request Process

### Before Submitting

- [ ] Code follows PSR-12 standards
- [ ] All tests pass (`php artisan test`)
- [ ] Code is formatted with Pint (`./vendor/bin/pint`)
- [ ] New features include tests
- [ ] Documentation is updated (if applicable)
- [ ] Commit messages follow guidelines
- [ ] Branch is up to date with develop

### Submitting a Pull Request

1. **Push your changes to your fork:**
```bash
git push origin feature/your-feature-name
```

2. **Create a pull request on GitHub:**
   - Base branch: `develop` (not `main`)
   - Compare branch: `feature/your-feature-name`

3. **Fill out the PR template:**

```markdown
## Description
Brief description of the changes

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Related Issues
Closes #123
Related to #456

## Testing
Describe the tests you ran and their results

## Screenshots (if applicable)
Add screenshots to help explain your changes

## Checklist
- [ ] My code follows the project's code standards
- [ ] I have performed a self-review of my code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally with my changes
```

### PR Title Format

Follow the same format as commit messages:

```
feat(customers): add bulk delete functionality
fix(invoices): resolve tax calculation issue
docs(api): update webhook documentation
```

---

## Code Review Process

### What We Look For

Reviewers will check for:

1. **Functionality**
   - Does the code do what it's supposed to?
   - Are edge cases handled?
   - Are there any bugs?

2. **Code Quality**
   - Follows PSR-12 standards
   - Proper type hints and return types
   - Clean, readable code
   - No code smells or anti-patterns

3. **Testing**
   - Adequate test coverage
   - Tests are meaningful and test the right things
   - Tests pass consistently

4. **Security**
   - No SQL injection vulnerabilities
   - Input validation is proper
   - Authentication/authorization checks are present
   - Sensitive data is not exposed

5. **Performance**
   - No N+1 query problems
   - Efficient algorithms
   - Proper indexing for database queries
   - Caching where appropriate

6. **Documentation**
   - Code is well-commented
   - API documentation is updated
   - README is updated if needed

### Review Timeline

- Initial review: Within 2-3 business days
- Follow-up reviews: Within 1-2 business days
- Complex PRs may take longer

### Addressing Feedback

1. Make requested changes in your feature branch
2. Commit the changes with descriptive messages
3. Push to your fork
4. Request re-review

### Approval and Merge

- Requires at least **1 approval** from a maintainer
- All tests must pass
- No merge conflicts
- All review comments resolved

Once approved, a maintainer will merge your PR using **squash and merge** to keep the history clean.

---

## Documentation

### When to Update Documentation

Update documentation when:

- Adding new features or API endpoints
- Changing existing functionality
- Adding configuration options
- Modifying database schema
- Updating dependencies

### Documentation Files

- **API Documentation:** `docs/api.md`
- **Multi-Tenancy Guide:** `docs/multi-tenancy.md`
- **ERD Documentation:** `docs/erd.md`
- **README:** `README.md`

### Documentation Standards

- Use clear, concise language
- Include code examples
- Provide request/response samples for API changes
- Update table of contents
- Use proper markdown formatting

---

## Reporting Bugs

### Before Reporting

1. Check existing issues to avoid duplicates
2. Verify the bug exists in the latest version
3. Gather detailed information about the bug

### Bug Report Template

```markdown
## Bug Description
Clear and concise description of the bug

## To Reproduce
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '...'
3. Scroll down to '...'
4. See error

## Expected Behavior
What you expected to happen

## Actual Behavior
What actually happened

## Environment
- OS: [e.g., Ubuntu 22.04]
- PHP Version: [e.g., 8.2.10]
- Laravel Version: [e.g., 12.0]
- Database: [e.g., MySQL 8.0.35]

## Screenshots
If applicable, add screenshots

## Additional Context
Any other relevant information

## Possible Solution
If you have an idea of how to fix the bug
```

### Security Vulnerabilities

⚠️ **Do not open public issues for security vulnerabilities.**

Report security issues to: security@autopulse.com

Include:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

---

## Suggesting Features

### Feature Request Template

```markdown
## Feature Description
Clear description of the proposed feature

## Problem Statement
What problem does this feature solve?

## Proposed Solution
How should this feature work?

## Alternatives Considered
What other solutions did you consider?

## Use Cases
Describe realistic scenarios where this feature would be used

## Additional Context
Any other relevant information, mockups, or examples
```

---

## Development Tips

### Debugging

**Enable debug mode:**
```env
APP_DEBUG=true
```

**Use Laravel Telescope (if installed):**
```bash
php artisan telescope:install
```

**Query logging:**
```php
DB::enableQueryLog();
// ... your code
dd(DB::getQueryLog());
```

**Dump and die helpers:**
```php
dd($variable);           // Dump and die
dump($variable);         // Dump and continue
logger()->info($data);   // Log to file
```

### Performance Profiling

Use Laravel Debugbar for local development:
```bash
composer require barryvdh/laravel-debugbar --dev
```

### Database Management

**Reset database:**
```bash
php artisan migrate:fresh --seed
```

**Create migration:**
```bash
php artisan make:migration create_something_table
```

**Create model with migration:**
```bash
php artisan make:model Something -m
```

---

## Getting Help

### Resources

- **Documentation:** [docs/](./docs/)
- **API Reference:** [docs/api.md](./docs/api.md)
- **Laravel Docs:** https://laravel.com/docs
- **GitHub Issues:** https://github.com/REPO/issues

### Contact

- **General Questions:** Open a GitHub discussion
- **Bug Reports:** Open a GitHub issue
- **Security Issues:** security@autopulse.com
- **Email:** dev@autopulse.com

---

## License

By contributing to AutoPulse, you agree that your contributions will be licensed under the same license as the project.

---

Thank you for contributing to AutoPulse! 🚗✨
