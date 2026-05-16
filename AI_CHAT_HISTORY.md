# AI Chat History — Loan Service

**NIM:** 102022400337  
**Service:** SERVICE PINJAMAN / LOAN  
**Framework:** Laravel 11  
**Date:** 2025

---

## Prompt 1 — Project Planning

**User:**
> Each team member must create their own separate GitHub repository and service. Do not make one monorepo unless the lecturer explicitly requires it. Each service must run with Docker and communicate with other services through HTTP endpoints...
>
> My project idea is: PENGAJUAN PINJAMAN DIGITAL  
> My assigned service is: SERVICE PINJAMAN / LOAN  
> My NIM is: 102022400337  
> My preferred framework is: Laravel.

**AI (Claude Sonnet):**

The AI entered Plan Mode and asked clarifying questions:

**Q: What other services exist in your system besides your Loan Service?**  
**A:** User/Auth Service, Payment Service

**Q: Where will services be deployed/run?**  
**A:** Different laptops (each runs independently)

**Q: What database should your Loan Service use?**  
**A:** MySQL

---

## Prompt 2 — Architecture Plan (AI Response)

The AI produced this architecture plan:

### System Topology

```
User/Auth Service          Loan Service ⭐          Payment Service
(teammate's laptop)    (102022400337 · :8001)    (teammate's laptop)
        │                       │                        │
        │  POST /validate        │                        │
        │ ◄──────────────────── │  (on loan create)      │
        │                       │                        │
        │                       │  POST /api/v1/schedules│
        │                       │ ──────────────────────►│ (on disburse)
```

### Inter-service communication rationale:
- **User/Auth**: When a loan application includes an `applicant_user_id`, the Loan Service calls the User/Auth Service to verify the user exists before accepting the application.
- **Payment**: When a loan's status changes to `disbursed`, the Loan Service notifies the Payment Service so it can create a repayment schedule. Both calls are **fire-and-forget with graceful degradation** — Loan Service operations are never blocked by downstream service failures.

### REST API Contract
| Method | Endpoint | Description | Codes |
|---|---|---|---|
| GET | /api/v1/loans | List loans (paginated) | 200, 401 |
| GET | /api/v1/loans/{id} | Get loan by UUID | 200, 401, 404 |
| POST | /api/v1/loans | Submit loan application | 201, 401, 404, 422 |
| PATCH | /api/v1/loans/{id} | Update status/notes | 200, 401, 404, 422 |
| DELETE | /api/v1/loans/{id} | Soft-delete loan | 200, 401, 404 |
| GET | /api/v1/loans/health | Health check | 200 |

### Database Schema
```sql
loans (
  id               UUID PRIMARY KEY,
  applicant_name   VARCHAR(255),
  applicant_nim    VARCHAR(50),
  applicant_user_id VARCHAR(100) NULL,  -- from User/Auth Service
  amount           DECIMAL(15,2),
  tenor_months     INT UNSIGNED,
  purpose          VARCHAR(255),
  status           ENUM('pending','approved','rejected','disbursed'),
  notes            TEXT NULL,
  deleted_at       TIMESTAMP NULL,       -- soft delete
  created_at       TIMESTAMP,
  updated_at       TIMESTAMP
)
```

### GraphQL Schema
```graphql
type Loan {
  id: ID!
  applicant_name: String!
  applicant_nim: String!
  applicant_user_id: String
  amount: Float!
  tenor_months: Int!
  purpose: String!
  status: String!
  notes: String
  created_at: String!
  updated_at: String!
}

type Query {
  loans(status: String): [Loan!]!
  loan(id: ID!): Loan
}
```

### Standard Integration Contract Response Format
```json
// Success
{
  "status": "success",
  "message": "Operation successful",
  "data": {},
  "meta": { "service_name": "Loan-Service", "api_version": "v1" }
}

// Error
{
  "status": "error",
  "message": "Detail pesan kesalahan...",
  "errors": null
}
```

---

## Prompt 3 — Implementation

**User:** Proceed with implementation.

**AI Response:** Generated 30 files covering the complete service:

```
102022400337_Loan-Service/
├── Dockerfile
├── docker-compose.yml
├── .env.example
├── .gitignore
├── phpunit.xml
├── composer.json
├── bootstrap/app.php
├── app/
│   ├── Models/Loan.php
│   ├── Http/
│   │   ├── Controllers/Api/V1/LoanController.php
│   │   └── Middleware/VerifyIaeKey.php
│   ├── Services/
│   │   ├── UserAuthService.php      ← calls User/Auth Service
│   │   └── PaymentService.php       ← calls Payment Service on disburse
│   └── GraphQL/
│       ├── Types/LoanType.php
│       └── Queries/LoansQuery.php / LoanQuery.php
├── config/
│   ├── app.php, graphql.php, l5-swagger.php, services.php
├── database/
│   ├── migrations/, factories/, seeders/
├── routes/api.php, web.php
├── docker/nginx.conf, supervisord.conf
└── tests/Feature/
    ├── LoanApiTest.php         (22 tests)
    └── SwaggerGraphQLTest.php  (8 tests)
```

---

## Key Design Decisions Made with AI Assistance

| Decision | Rationale |
|---|---|
| `applicant_user_id` stored but not FK | Different service's DB — we store the reference but never join across |
| Graceful degradation for User/Auth + Payment | Loan service must stay up even if teammates' laptops are off |
| Payment trigger only on `disbursed` | Business rule: only fully disbursed loans need a repayment schedule |
| User/Auth only checked when `applicant_user_id` is provided | Field is optional — supports both internal and external applicants |
| `SoftDeletes` on Loan | Audit trail; data can be recovered if deleted by mistake |
| UUID PKs | Safe to share across services without collision |
| SQLite `:memory:` for tests | No Docker dependency for running tests in CI |
| Pest for testing | Expressive, Laravel-native, readable test descriptions |

---

*This file satisfies the assignment requirement for an AI prompting/chat history file in `.md` format.*
