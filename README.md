# 💊 MediShare — Medicine Donation & Distribution Platform

> **Connecting medicine donors with NGOs to make essential healthcare resources more accessible.**

**MediShare** is a database-driven platform designed to facilitate the **listing, requesting, donating, and distribution of medicines** between donors and NGOs.

The database provides the backend foundation for managing users, medicines, requests, donations, NGO wishlists, notifications, administrative activity, announcements, and system auditing — all within a structured relational database.

---

## 🌟 What is MediShare?

MediShare brings **donors and NGOs together** through a centralized medicine-sharing system.

```text
             💊 MEDISHARE
                  │
        ┌─────────┴─────────┐
        ▼                   ▼
   👤 DONORS             🏥 NGOs
        │                   │
        │ List Medicines    │ Request Medicines
        │                   │
        └─────────┬─────────┘
                  ▼
          🤝 MEDICINE MATCH
                  │
                  ▼
           📦 DONATION
                  │
                  ▼
          ❤️ COMMUNITY IMPACT
```

---

## ✨ Core Features

### 👥 User Management
Supports multiple user roles:

- 👑 **Admin**
- 💊 **Donor**
- 🏥 **NGO**

Users can be verified and managed through the centralized `users` table.

### 💊 Medicine Management

Donors can list medicines with information such as:

- Medicine name
- Expiry date
- Quantity
- Category
- Image
- Availability status
- Listing timestamp

Supported medicine categories include **Antibiotic, Painkiller, Vitamin, Supplement, and Other**.

### 🏥 NGO Requests

NGOs can request available medicines and track request states:

```text
PENDING
   │
   ├── ✅ APPROVED
   │
   └── ❌ REJECTED
```

Requests can also contain notes and rejection reasons.

### 📋 NGO Wishlists

NGOs can create medicine wishlists describing what they need, the required quantity, category, and fulfillment status.

### 🔔 Notifications

The system maintains user-specific notifications and tracks whether each notification has been read.

### 📢 Announcements

Administrators can publish system-wide announcements with active/inactive status and timestamps.

### 📊 Activity Feed

Important platform activities such as medicine listings, requests, donations, wishlists, and fulfillment actions can be recorded in the activity feed.

### 🛡️ Audit Logging

Administrative and system actions are tracked through an audit log, providing a history of important events such as medicine listings, NGO verification, request approvals, and user management.

---

## 🗄️ Database Architecture

```text
                         ┌──────────────────┐
                         │      USERS       │
                         │──────────────────│
                         │ user_id          │
                         │ full_name        │
                         │ email            │
                         │ role             │
                         │ contact_number   │
                         │ address          │
                         │ is_verified      │
                         └───────┬──────────┘
                                 │
              ┌──────────────────┼──────────────────┐
              │                  │                  │
              ▼                  ▼                  ▼
       ┌─────────────┐    ┌─────────────┐   ┌──────────────┐
       │  MEDICINES  │    │  REQUESTS   │   │  WISHLISTS   │
       └──────┬──────┘    └──────┬──────┘   └──────┬───────┘
              │                   │                  │
              │                   │                  │
              └──────────┬────────┴──────────────────┘
                         ▼
                  🤝 DONATION FLOW

        ┌──────────────────────────────────────┐
        │       PLATFORM SUPPORT SYSTEM       │
        ├──────────────────────────────────────┤
        │ 🔔 Notifications                     │
        │ 📢 Announcements                     │
        │ 📊 Activity Feed                     │
        │ 🛡️ Audit Log                         │
        │ 📝 Admin Notes                       │
        └──────────────────────────────────────┘
```

The database uses foreign-key relationships connecting users with medicines, requests, notifications, wishlists, and administrative records.

---

## 🧩 Database Modules

| Module | Purpose |
|---|---|
| 👥 `users` | User accounts and roles |
| 💊 `medicines` | Donated medicine listings |
| 🏥 `requests` | NGO medicine requests |
| 📋 `wishlists` | NGO medicine requirements |
| 🔔 `notifications` | User notifications |
| 📢 `announcements` | Admin announcements |
| 📊 `activity_feed` | Platform activity |
| 🛡️ `audit_log` | System auditing |
| 📝 `admin_notes` | Administrative notes |

---

## 🔗 Relational Design

MediShare uses **primary keys, indexes, foreign keys, unique constraints, and cascading relationships** to maintain database integrity.

For example:

```text
USERS
  │
  ├──► MEDICINES
  │       └── donor_id
  │
  ├──► REQUESTS
  │       └── ngo_id
  │
  ├──► NOTIFICATIONS
  │       └── user_id
  │
  ├──► WISHLISTS
  │       └── ngo_id
  │
  └──► ADMIN_NOTES
          └── user_id
```

Requests additionally connect NGOs with specific medicines through foreign-key relationships.

---

## ⚙️ Technology

**Database:** MariaDB  
**SQL:** MySQL-compatible SQL  
**Storage Engine:** InnoDB  
**Character Set:** `utf8mb4`  
**Database:** `medishare_db`

---

## 🚀 Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/your-username/medishare.git
```

### 2. Open MySQL / MariaDB / phpMyAdmin

### 3. Import the database

```text
medishare_db (1).sql
```

### 4. Start using the database

The SQL dump recreates the database structure, indexes, auto-increment configuration, relationships, and included records.

---

## 📁 Project Structure

```text
📦 MediShare
└── 📄 medishare_db.sql
```

> **One SQL file containing the database foundation of an entire medicine-sharing platform.**

---

## ❤️ The Idea Behind MediShare

Medicine that is no longer needed by one person can potentially become essential to someone else.

**MediShare is designed around a simple idea:**

> ### 💊 Don't let usable medicine go to waste.
> ### 🤝 Connect it with organizations that need it.

---

## 🔐 Database Integrity

MediShare uses relational constraints to keep connected records consistent. Several relationships use `ON DELETE CASCADE`, ensuring dependent records are handled automatically when their associated user or medicine is removed.

---

## 📌 Project Status

**Database:** ✅ Structured  
**User Management:** ✅ Implemented  
**Medicine Management:** ✅ Implemented  
**NGO Requests:** ✅ Implemented  
**Wishlists:** ✅ Implemented  
**Notifications:** ✅ Implemented  
**Audit Logging:** ✅ Implemented  
**Admin Management:** ✅ Implemented  

---

## ⭐ MediShare

**Donate medicines. Fulfill needs. Connect communities.**

`SQL • MariaDB • Database Design • Healthcare • Donation Management • NGO Management • Relational Database`

### 💙 Built to connect resources with the people who need them.
