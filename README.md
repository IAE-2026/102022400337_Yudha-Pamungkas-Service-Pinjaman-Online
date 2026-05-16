# 102022400337 — Loan Service

**Service Pinjaman | PENGAJUAN PINJAMAN DIGITAL**  
NIM: **102022400337** · Framework: **Laravel 11** · Database: **MySQL 8** · Port: **8001**

---

## System Overview

This service is part of a microservice system for digital loan applications. It owns all loan data and communicates with two other services via HTTP:

| Partner Service | Direction | Trigger | Endpoint Called |
|---|---|---|---|
| User/Auth Service | Loan → Auth | POST /loans with `applicant_user_id` | `GET /api/v1/users/{id}` |
| Payment Service | Loan → Payment | Loan status → `disbursed` | `POST /api/v1/schedules` |

No service accesses another service's database directly.

---

## Quick Start with Docker

```bash
# 1. Clone the repository
git clone https://github.com/<org>/102022400337_Loan-Service.git
cd 102022400337_Loan-Service

# 2. Configure environment
cp .env.example .env
# Edit .env — required: APP_KEY, DB_PASSWORD
# Optional: USER_AUTH_SERVICE_URL, PAYMENT_SERVICE_URL (set to teammates' laptop IPs)

# 3. Build and start
docker compose up -d --build

# 4. Run database migrations
docker compose exec loan-service php artisan migrate --seed

# 5. Generate Swagger docs
docker compose exec loan-service php artisan l5-swagger:generate
```

Service is live at **http://localhost:8001**

---

## URLs

| URL | Description |
|---|---|
| `http://localhost:8001/api/v1/loans` | REST API base |
| `http://localhost:8001/api/documentation` | Swagger UI |
| `http://localhost:8001/graphql` | GraphQL endpoint |
| `http://localhost:8001/graphiql` | GraphiQL Playground |
| `http://localhost:8001/api/v1/loans/health` | Health check |

---

## Authentication

Every request must include:

```
X-IAE-KEY: 102022400337
```

Missing or wrong key → `401 Unauthorized`.

---

## Standard Response Format

**Success:**
```json
{
  "status": "success",
  "message": "Operation successful",
  "data": { },
  "meta": {
    "service_name": "Loan-Service",
    "api_version": "v1"
  }
}
```

**Error:**
```json
{
  "status": "error",
  "message": "Detail pesan kesalahan.",
  "errors": null
}
```

---

## REST API

### `GET /api/v1/loans`
List all loan applications (paginated).

Query params: `status`, `page`, `per_page`

```bash
curl -H "X-IAE-KEY: 102022400337" http://localhost:8001/api/v1/loans
curl -H "X-IAE-KEY: 102022400337" http://localhost:8001/api/v1/loans?status=pending
```

---

### `GET /api/v1/loans/{id}`
Get a single loan by UUID.

```bash
curl -H "X-IAE-KEY: 102022400337" http://localhost:8001/api/v1/loans/<uuid>
```

Responses: `200` found · `401` bad key · `404` not found

---

### `POST /api/v1/loans`
Submit a new loan application.

```bash
curl -X POST http://localhost:8001/api/v1/loans \
  -H "X-IAE-KEY: 102022400337" \
  -H "Content-Type: application/json" \
  -d '{
    "applicant_name":    "Budi Santoso",
    "applicant_nim":     "102022400001",
    "applicant_user_id": "optional-user-uuid-from-auth-service",
    "amount":            5000000,
    "tenor_months":      12,
    "purpose":           "Modal usaha"
  }'
```

If `applicant_user_id` is provided, it is validated against the User/Auth Service.  
Returns `404` if the user does not exist there.

Validation:
- `applicant_name` — required, string
- `applicant_nim` — required, string
- `applicant_user_id` — optional, string
- `amount` — required, numeric, min: 100,000 / max: 500,000,000
- `tenor_months` — required, integer 1–360
- `purpose` — required, string
- `notes` — optional, string

Responses: `201` created · `401` · `404` user not found · `422` validation error

---

### `PATCH /api/v1/loans/{id}`
Update loan status or notes.

```bash
curl -X PATCH http://localhost:8001/api/v1/loans/<uuid> \
  -H "X-IAE-KEY: 102022400337" \
  -H "Content-Type: application/json" \
  -d '{"status": "disbursed"}'
```

When status changes to `disbursed`, the Payment Service is automatically called to create a repayment schedule.

Allowed statuses: `pending` · `approved` · `rejected` · `disbursed`

Responses: `200` · `401` · `404` · `422`

---

### `DELETE /api/v1/loans/{id}`
Soft-delete a loan application (data is preserved, not destroyed).

```bash
curl -X DELETE http://localhost:8001/api/v1/loans/<uuid> \
  -H "X-IAE-KEY: 102022400337"
```

Responses: `200` · `401` · `404`

---

## GraphQL

### Queries

**List all loans:**
```graphql
{
  loans {
    id
    applicant_name
    applicant_nim
    amount
    tenor_months
    status
    created_at
  }
}
```

**Filter by status:**
```graphql
{
  loans(status: "approved") {
    id
    applicant_name
    amount
  }
}
```

**Get single loan:**
```graphql
{
  loan(id: "uuid-here") {
    id
    applicant_name
    applicant_nim
    applicant_user_id
    amount
    tenor_months
    purpose
    status
    notes
    created_at
    updated_at
  }
}
```

---

## Database Schema

```sql
CREATE TABLE loans (
  id                CHAR(36)        PRIMARY KEY,          -- UUID
  applicant_name    VARCHAR(255)    NOT NULL,
  applicant_nim     VARCHAR(50)     NOT NULL,
  applicant_user_id VARCHAR(100)    NULL,                 -- from User/Auth Service
  amount            DECIMAL(15,2)   NOT NULL,
  tenor_months      INT UNSIGNED    NOT NULL,
  purpose           VARCHAR(255)    NOT NULL,
  status            ENUM('pending','approved','rejected','disbursed') DEFAULT 'pending',
  notes             TEXT            NULL,
  deleted_at        TIMESTAMP       NULL,                 -- soft delete
  created_at        TIMESTAMP,
  updated_at        TIMESTAMP,
  INDEX idx_status         (status),
  INDEX idx_nim            (applicant_nim),
  INDEX idx_user_id        (applicant_user_id)
);
```

---

## Inter-Service Communication

### Connecting to other services

Set these in `.env` to point at your teammates' laptops:

```env
USER_AUTH_SERVICE_URL=http://<auth-laptop-ip>:8002
PAYMENT_SERVICE_URL=http://<payment-laptop-ip>:8003
```

### Graceful degradation

Both calls are wrapped in try/catch. If a partner service is:
- Not configured (empty URL) → skipped with a log warning
- Unreachable (timeout/connection error) → skipped with a log error
- Returns unexpected status → skipped with a log warning

The Loan Service **never fails** because of a partner service being down.

---

## Running Tests

```bash
# Via Docker
docker compose exec loan-service php artisan test

# Locally (requires PHP 8.2+, Composer)
composer install
php artisan test

# With verbose output
php artisan test --verbose
```

**Test coverage:**

| File | Tests | What it covers |
|---|---|---|
| `LoanApiTest.php` | 22 | REST CRUD, API key auth, User/Auth integration, Payment integration |
| `SwaggerGraphQLTest.php` | 8 | Swagger UI, OpenAPI JSON, GraphQL queries, GraphiQL |

Tests use SQLite `:memory:` — no Docker or MySQL needed to run them.

---

## Environment Variables

| Variable | Required | Default | Description |
|---|---|---|---|
| `APP_KEY` | ✅ | — | Run `php artisan key:generate` |
| `DB_PASSWORD` | ✅ | — | MySQL password |
| `IAE_KEY` | ✅ | `102022400337` | API key (your NIM) |
| `USER_AUTH_SERVICE_URL` | ⬜ | empty | URL of User/Auth Service |
| `PAYMENT_SERVICE_URL` | ⬜ | empty | URL of Payment Service |
| `APP_PORT` | ⬜ | `8001` | Host port |
| `APP_DEBUG` | ⬜ | `false` | Enable debug mode |

---

## Stopping the Service

```bash
docker compose down          # stop and remove containers
docker compose down -v       # also remove the MySQL volume (deletes all data)
```

---

## Project Structure

```
.
├── app/
│   ├── GraphQL/
│   │   ├── Queries/LoanQuery.php
│   │   ├── Queries/LoansQuery.php
│   │   └── Types/LoanType.php
│   ├── Http/
│   │   ├── Controllers/Api/V1/LoanController.php
│   │   └── Middleware/VerifyIaeKey.php
│   ├── Models/Loan.php
│   └── Services/
│       ├── UserAuthService.php     ← HTTP client for User/Auth Service
│       └── PaymentService.php      ← HTTP client for Payment Service
├── config/
│   ├── app.php
│   ├── graphql.php
│   ├── l5-swagger.php
│   └── services.php
├── database/
│   ├── factories/LoanFactory.php
│   ├── migrations/
│   └── seeders/DatabaseSeeder.php
├── docker/
│   ├── nginx.conf
│   └── supervisord.conf
├── routes/
│   ├── api.php
│   └── web.php
├── tests/Feature/
│   ├── LoanApiTest.php
│   └── SwaggerGraphQLTest.php
├── .env.example
├── .gitignore
├── AI_CHAT_HISTORY.md
├── composer.json
├── docker-compose.yml
├── Dockerfile
├── phpunit.xml
└── README.md
```

---

*Repository: `102022400337_Loan-Service` in the organization provided by the lecturer.*
