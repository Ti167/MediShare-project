# Medi-Share Database Context

## Overview
This document describes the MySQL database schema created for the Medi-Share project - a web-based unused medicine donation portal.

## Files Created
- `medishare_schema.sql` - Complete MySQL database schema
- `context/medishare_context.md` - This context file

## Database: medishare_db

### Tables

#### 1. users
Stores all registered users of the system with three roles:
- **donor**: Individuals/pharmacies donating medicines
- **ngo**: Organizations/clinics receiving medicines
- **admin**: System administrators

Key fields:
- `user_id`: Primary key
- `email`: Unique identifier for login
- `password`: Stores hashed passwords (VARCHAR 255 for bcrypt)
- `role`: ENUM for type safety ('donor', 'ngo', 'admin')

#### 2. medicines
Stores medicine listings from donors.

Key fields:
- `medicine_id`: Primary key
- `donor_id`: Foreign key to users (CASCADE delete)
- `expiry_date`: Critical for validation - only unexpired medicines accepted
- `status`: ENUM tracks medicine lifecycle ('available' -> 'requested' -> 'donated')
- `listed_at`: Timestamp for sorting recent listings

#### 3. requests
Tracks medicine requests from NGOs.

Key fields:
- `request_id`: Primary key
- `ngo_id`: Foreign key to users (CASCADE delete)
- `medicine_id`: Foreign key to medicines (CASCADE delete)
- `request_status`: ENUM workflow ('pending' -> 'approved'/'rejected')

## Design Decisions

1. **InnoDB Engine**: Used for all tables to support foreign key constraints and transactions

2. **ON DELETE CASCADE**: Ensures data integrity - when a user is deleted, their medicines and requests are automatically removed

3. **ENUM Types**: Used for role and status fields to prevent invalid data and enable type safety

4. **Timestamps**: created_at/listed_at/request_date fields track temporal data for sorting and auditing

## Usage
Execute the SQL file in MySQL:
```sql
SOURCE medishare_schema.sql;
```
