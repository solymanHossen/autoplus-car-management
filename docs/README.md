# AutoPulse Documentation

Welcome to the AutoPulse documentation. This directory contains comprehensive guides for developers, administrators, and API consumers.

## Documentation Files

### 📘 [API Documentation](./api.md)
Complete API reference for the AutoPulse RESTful API.

**Contents:**
- Authentication & authorization
- All API endpoints (customers, vehicles, job cards, invoices, payments, appointments)
- Request/response examples
- Error handling & status codes
- Rate limiting & pagination
- Webhook integration

**Audience:** Developers integrating with the API, frontend developers

---

### 🏢 [Multi-Tenancy Guide](./multi-tenancy.md)
Comprehensive guide to the multi-tenancy architecture.

**Contents:**
- Tenant identification methods (domain, subdomain, header, path)
- TenantScoped trait implementation
- Setting up new tenants
- Data isolation strategies
- Storage & cache separation
- Testing multi-tenancy
- Best practices & troubleshooting

**Audience:** Backend developers, system administrators, DevOps

---

### 🗄️ [Database ERD](./erd.md)
Complete database schema and entity relationship documentation.

**Contents:**
- All 32 tables with detailed schemas
- Entity relationships and diagrams
- Foreign key constraints
- Indexes and performance optimization
- Migration order
- Query optimization tips

**Audience:** Database administrators, backend developers

---

### 🤝 [Contributing Guidelines](../CONTRIBUTING.md)
Guidelines for contributing to the AutoPulse project.

**Contents:**
- Code standards (PSR-12, strict types)
- Git workflow & branching strategy
- Testing requirements (80% coverage)
- Commit message conventions
- Pull request process
- Code review guidelines

**Audience:** Contributors, open-source developers

---

## Quick Links

| Topic | Document | Section |
|-------|----------|---------|
| API Authentication | [api.md](./api.md#authentication) | Authentication |
| Creating Customers | [api.md](./api.md#create-customer) | Customers |
| Tenant Setup | [multi-tenancy.md](./multi-tenancy.md#setting-up-new-tenants) | Multi-Tenancy |
| Database Schema | [erd.md](./erd.md#detailed-schema) | ERD |
| Code Standards | [CONTRIBUTING.md](../CONTRIBUTING.md#code-standards) | Contributing |
| Testing Guide | [CONTRIBUTING.md](../CONTRIBUTING.md#testing-requirements) | Contributing |

---

## Getting Started

### For API Developers
1. Read [API Documentation](./api.md)
2. Understand [Multi-Tenancy](./multi-tenancy.md#tenant-identification)
3. Review [Contributing Guidelines](../CONTRIBUTING.md) if contributing

### For Backend Developers
1. Review [Database ERD](./erd.md)
2. Understand [Multi-Tenancy Architecture](./multi-tenancy.md)
3. Read [Code Standards](../CONTRIBUTING.md#code-standards)
4. Review [Testing Requirements](../CONTRIBUTING.md#testing-requirements)

### For System Administrators
1. Read [Multi-Tenancy Guide](./multi-tenancy.md)
2. Review [Database Schema](./erd.md)
3. Understand [Tenant Setup](./multi-tenancy.md#setting-up-new-tenants)

---

## Documentation Statistics

- **Total Lines:** 3,955
- **Total Words:** 11,215
- **Total Size:** ~112 KB
- **Files:** 4 main documentation files

---

## Need Help?

- **Issues:** Open a GitHub issue
- **Questions:** Start a GitHub discussion
- **Security:** Email security@autopulse.com
- **General:** Email dev@autopulse.com

---

## License

Documentation is licensed under the same license as the AutoPulse project.

---

**Last Updated:** January 31, 2024
