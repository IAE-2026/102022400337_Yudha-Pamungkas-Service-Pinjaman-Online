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
s