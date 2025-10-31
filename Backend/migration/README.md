# CandyHire Portal - Database Migrations

This folder contains all database schema and data files for the CandyHire Portal system.

## Architecture: Single-Database Multi-Tenancy

All tenant data resides in **ONE database** (`CandyHirePortal`), with isolation provided by `tenant_id` column.

## Migration Files (Execution Order)

### Portal Management Schema

1. **01_schema.sql** - Portal core tables
   - `companies_registered` - Company registration & payment tracking
   - `tenant_pool` - Tenant ID pool (100 pre-allocated IDs)
   - `admin_users` - Portal administrators
   - `payment_transactions` - PayPal payment records
   - `activity_logs` - Audit trail

2. **02_initial_data.sql** - Portal initial data
   - 100 tenant IDs (1-100) in `tenant_pool`
   - Default admin user: `admin` / `Admin123!`

3. **03_refactor_single_db.sql** - Migration script (optional)
   - Use this if upgrading from multi-database to single-database architecture

### CandyHire Operational Schema

4. **04_candyhire_schema.sql** - CandyHire operational tables (18 tables)
   - `system_users` - Application users (with `tenant_id`)
   - `candidates` - Candidate database (with `tenant_id`)
   - `jobs` - Job postings (with `tenant_id`)
   - `companies` - Client companies (with `tenant_id`)
   - `job_applications` - Applications (with `tenant_id`)
   - `interviews` - Interview tracking (with `tenant_id`)
   - ... 12 more tables (all with `tenant_id`)

5. **05_candyhire_initial_data.sql** - CandyHire demo data
   - Demo tenant: `demo-tenant`
   - Demo user: `demo@candyhire.com` / `Demo123!`
   - Sample candidates, jobs, applications

## Setup

Run the consolidated setup script:

```bash
cd /Users/guidosalzano/Documents/MyProject/CandyHireSuite/CandyHirePortal/Backend
./setup.sh
```

This will:
1. Create MySQL container (port 3308)
2. Create database `CandyHirePortal`
3. Execute all migrations in order
4. Result: **One database with Portal + CandyHire tables**

## Database Structure

```
CandyHirePortal (Database - 10GB on Aruba)
├─ Portal Tables (no tenant_id)
│  ├─ companies_registered
│  ├─ tenant_pool
│  ├─ admin_users
│  └─ payment_transactions
│
└─ CandyHire Tables (ALL with tenant_id)
   ├─ system_users
   ├─ candidates
   ├─ jobs
   ├─ companies
   └─ ... (18 tables total)
```

## Tenant Isolation

- Portal tables: NO `tenant_id` (global data)
- CandyHire tables: ALL have `tenant_id` (isolated per company)
- New company registration → assigns next available `tenant_id`
- User login → queries filtered by `WHERE tenant_id = ?`

## Scalability

- **100 tenants pre-created** for quick assignment
- **Unlimited tenants** possible (can create on-the-fly)
- **10GB database** can hold ~100,000 companies (text-only data)
- No separate databases needed on Aruba

## Production Deployment

1. Export database: `mysqldump CandyHirePortal > candyhire_production.sql`
2. Upload to Aruba MySQL
3. Update `.env` with Aruba credentials
4. Portal handles tenant assignment automatically via PayPal webhooks

## Notes

- CandyHire standalone setup (`../CandyHire/Backend/setup.sh`) is for **testing only**
- Production uses Portal as master (this setup)
- All schema files are version-controlled
- Migration 03 is for legacy database refactoring (optional)
