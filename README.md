<div align="center">

# 🔧 Tool Library System

**A peer-to-peer tool sharing platform where members can lend and borrow tools — with reservations, payments, damage reports, and maintenance tracking.**

[![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com)
[![PDO](https://img.shields.io/badge/PDO-Secure%20Queries-green?style=flat-square)](/)
[![License](https://img.shields.io/badge/License-MIT-blue?style=flat-square)](LICENSE)

</div>

---

## 📋 Table of Contents

- [About](#-about)
- [Features](#-features)
- [User Roles](#-user-roles)
- [Design Patterns](#-design-patterns)
- [Project Structure](#-project-structure)
- [Database Schema](#-database-schema)
- [Getting Started](#-getting-started)
- [Environment Setup](#-environment-setup)
- [Default Accounts](#-default-accounts)
- [API Endpoints](#-api-endpoints)
- [License](#-license)

---

## 🧾 About

Tool Library is a full-stack PHP web application that enables community members to share tools with each other. Members can list their tools for rent, make reservations, pay via an in-app wallet, report damage, and track maintenance — all managed by a librarian and maintenance staff.

---

## ✨ Features

| Feature | Description |
|---|---|
| 🔐 Role-based Auth | Member, Librarian, and Maintenance Staff with separate dashboards |
| 🔧 Tool Listings | Members list tools with photos, daily rate, condition, and safety expiry |
| 📅 Reservations | Borrow tools by date range — approved by librarian, tracked via QR code |
| 💳 Wallet & Payments | In-app balance, deposits, refunds, and penalty system |
| 📸 QR Code Check-in | Scan QR on pickup and return to confirm borrowing lifecycle |
| 🔨 Damage Reports | Borrowers and librarians can file damage reports with photo evidence |
| 🛠️ Maintenance Tracking | Maintenance staff log repair work and update tool condition |
| 💬 In-app Messaging | Members can chat with tool owners before reserving |
| 📊 Reports & Analytics | Reservation, damage, maintenance, and member activity reports |
| 🏆 Membership Tiers | Basic, Silver, Gold tiers with pricing discounts |
| ⭐ Trust Score | Dynamic trust scoring affects pricing and borrowing privileges |
| 🪪 KYC Verification | Identity verification upload for member trust validation |
| 📧 Email Notifications | Automated emails on reservation, approval, return, and damage events |

---

## 👥 User Roles

| Role | Access |
|---|---|
| **Member** | List tools, make reservations, wallet, damage reports, chat, profile |
| **Librarian** | Approve reservations, manage members, approve tool listings, reports |
| **Maintenance Staff** | View damage reports, log maintenance work, update tool condition |

---

## 🏗️ Design Patterns

The service layer is built with classic OOP design patterns:

| Pattern | Where Used | Purpose |
|---|---|---|
| **Observer** | `ReservationService`, `PaymentService`, `MaintenanceService` | Notify borrower, lender, and librarian on state changes |
| **Strategy** | `services/pricing/` | Swap pricing algorithms (Daily, Weekly, Hourly, Trust-based, Membership) |
| **Proxy** | `services/proxies/` | Guard access to Reservation, Payment, Messaging, and KYC operations |

---

## 📁 Project Structure

```
tool-library-fixed/
│
├── api/                          # REST API endpoints (JSON responses)
│   ├── auth/                     # Login, register, profile, KYC, password
│   ├── damage/                   # Damage report creation
│   ├── maintenance/              # Maintenance tasks and work logs
│   ├── members/                  # Member listing and status management
│   ├── messages/                 # Messaging between members
│   ├── payments/                 # Wallet top-up, penalties, refunds
│   ├── reports/                  # Analytics and report data
│   ├── reservations/             # Full reservation lifecycle
│   └── tools/                    # Tool CRUD and search
│
├── pages/                        # Server-rendered PHP pages
│   ├── login.php                 # Login & registration
│   ├── dashboard.php             # Member dashboard
│   ├── tools.php                 # Browse available tools
│   ├── tool-detail.php           # Single tool view + reservation form
│   ├── add-tool.php              # List a new tool
│   ├── my-reservations.php       # Member's reservation history
│   ├── manage-reservations.php   # Librarian reservation management
│   ├── manage-members.php        # Librarian member management
│   ├── manage-tools.php          # Librarian tool approval
│   ├── maintenance.php           # Maintenance staff dashboard
│   ├── maintenance_staff.php     # Detailed maintenance task view
│   ├── damage-report.php         # File a damage report
│   ├── qr-scanner.php            # QR scan for pickup (librarian)
│   ├── scan-qr.php               # QR scan for return (librarian)
│   ├── show-qr.php               # Display QR code for borrower
│   ├── chat.php                  # Messaging interface
│   ├── wallet.php                # Wallet & payment history
│   ├── profile.php               # Member profile & KYC
│   └── reports.php               # Reports dashboard
│
├── services/
│   ├── observers/                # Observer pattern — event-driven notifications
│   │   ├── ISubject.php
│   │   ├── IObserver.php
│   │   ├── ReservationService.php
│   │   ├── PaymentService.php
│   │   ├── MaintenanceService.php
│   │   ├── DisputeService.php
│   │   └── TierUpgradeObserver.php
│   ├── pricing/                  # Strategy pattern — pluggable pricing algorithms
│   │   ├── IPricingStrategy.php
│   │   ├── PricingContext.php
│   │   ├── DailyStrategy.php
│   │   ├── WeeklyStrategy.php
│   │   ├── HourlyStrategy.php
│   │   ├── TrustBasedStrategy.php
│   │   └── MembershipStrategy.php
│   ├── proxies/                  # Proxy pattern — access control layer
│   │   ├── ReservationProxy.php
│   │   ├── PaymentProxy.php
│   │   ├── MessagingProxy.php
│   │   └── KYCProxy.php
│   └── notifiers/                # Email notification handlers
│       ├── BorrowerNotifier.php
│       ├── LenderNotifier.php
│       ├── LibrarianNotifier.php
│       └── TechnicianNotifier.php
│
├── models/                       # Thin model classes
│   ├── Tool.php
│   ├── Reservation.php
│   ├── Member.php
│   ├── Payment.php
│   ├── DamageReport.php
│   └── MaintenanceRecord.php
│
├── includes/                     # Shared PHP helpers
│   ├── auth_check.php            # requireLogin(), requireRole()
│   ├── db.php                    # PDO connection
│   ├── helpers.php               # Utility functions
│   ├── header.php / footer.php   # HTML layout
│   ├── Mailer.php                # PHPMailer wrapper
│   └── bootstrap_notifications.php
│
├── assets/
│   ├── css/                      # Stylesheets
│   └── js/                       # Frontend JS (tools, reservations)
│
├── database/
│   ├── schema.sql                # Full DB schema — run this first
│   ├── seed.sql                  # Sample data
│   ├── migration_qr_checkin.sql  # QR check-in migration
│   └── migration_rejected_status.sql
│
├── img/                          # Uploaded tool images
├── logs/                         # Mail and error logs
├── config.php                    # DB connection + .env loader
├── .env                          # Environment variables (not committed)
├── create_librarian.php          # CLI script to seed a librarian account
└── create_maintenance_staff.php  # CLI script to seed maintenance account
```

---

## 🗄️ Database Schema

**14 tables:**

| Table | Description |
|---|---|
| `Member` | Registered users with tier, trust score, wallet balance |
| `Librarian` | Staff who approve reservations and manage the platform |
| `MaintenanceStaff` | Technicians who handle tool repairs |
| `Tool` | Listed tools with condition, pricing, and availability |
| `Tool_URL` | Photos for each tool (one-to-many) |
| `Reservation` | Borrowing records with status lifecycle |
| `Payment` | Wallet transactions (deposits, charges, refunds, penalties) |
| `DamageReport` | Damage filings with photos and descriptions |
| `Damage_URL` | Photos for damage reports |
| `MaintenanceRecord` | Repair work logs linked to damage reports |
| `Maintenance_URL` | Photos of maintenance work |
| `Damage_Maintenance` | Junction table linking damage to maintenance |
| `Message` | In-app messages between members |
| `KYC` | Identity verification documents |

---

## 🚀 Getting Started

### Prerequisites

- PHP 8.0+
- MySQL 5.7+ or MariaDB
- Apache / Nginx with a local server (XAMPP, Laragon, WAMP, or similar)
- Composer (optional — for PHPMailer if installing fresh)

### 1. Clone the repository

```bash
git clone https://github.com/YOUR_USERNAME/tool-library.git
cd tool-library
```

### 2. Set up the database

Open **phpMyAdmin** → SQL tab → paste and run:

```
database/schema.sql    ← run first (creates all tables)
database/seed.sql      ← run second (sample data)
```

### 3. Configure environment

Copy the example env file and fill in your values:

```bash
cp .env.example .env
```

Edit `.env`:

```env
DB_HOST=localhost
DB_NAME=tool_library
DB_USER=root
DB_PASSWORD=your_password

MAIL_FROM=no-reply@yourdomain.com
LIBRARIAN_EMAIL=librarian@yourdomain.com
TECHNICIAN_EMAIL=maintenance@yourdomain.com
```

### 4. Place project in server root

For XAMPP, put the folder in:

```
C:/xampp/htdocs/tool-library-fixed/
```

Then open: `http://localhost/tool-library-fixed/pages/login.php`

### 5. Create staff accounts

Run these scripts once from browser or CLI:

```
http://localhost/tool-library-fixed/create_librarian.php
http://localhost/tool-library-fixed/create_maintenance_staff.php
```

---

## ⚙️ Environment Setup

| Variable | Description |
|---|---|
| `DB_HOST` | Database host (usually `localhost`) |
| `DB_NAME` | Database name (`tool_library`) |
| `DB_USER` | MySQL username |
| `DB_PASSWORD` | MySQL password |
| `MAIL_FROM` | Sender email for system notifications |
| `LIBRARIAN_EMAIL` | Librarian's notification email |
| `TECHNICIAN_EMAIL` | Maintenance staff notification email |

> ⚠️ Never commit your `.env` file — it's already in `.gitignore`.

---

## 🔑 Default Accounts

After running the seed scripts:

| Role | Email | Password |
|---|---|---|
| Librarian | `librarian@toollibrary.com` | set in `create_librarian.php` |
| Maintenance | `maintenance@toollibrary.com` | set in `create_maintenance_staff.php` |
| Member | Register via `/pages/login.php` | — |

---

## 📡 API Endpoints

All endpoints return JSON. Auth is session-based.

### Auth
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/auth/login.php` | Login |
| POST | `/api/auth/register.php` | Register new member |
| POST | `/api/auth/logout.php` | Logout |
| GET | `/api/auth/get_profile.php` | Get current user profile |
| POST | `/api/auth/update_profile.php` | Update profile |
| POST | `/api/auth/change_password.php` | Change password |
| POST | `/api/auth/kyc_upload.php` | Upload KYC document |

### Tools
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/tools/get_tools.php` | List all available tools |
| GET | `/api/tools/get_tool.php?id=` | Single tool detail |
| GET | `/api/tools/search_tools.php?q=` | Search tools |
| POST | `/api/tools/add_tool.php` | List a new tool |
| POST | `/api/tools/update_tool.php` | Update tool |
| POST | `/api/tools/delete_tool.php` | Delete tool |
| POST | `/api/tools/approve_listing.php` | Librarian approves tool listing |

### Reservations
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/reservations/get_reservations.php` | List reservations |
| POST | `/api/reservations/create_reservation.php` | Create reservation |
| POST | `/api/reservations/approve_reservation.php` | Librarian approves |
| POST | `/api/reservations/cancel_reservation.php` | Cancel reservation |
| POST | `/api/reservations/scan_qr.php` | QR scan on pickup |
| POST | `/api/reservations/borrower_scan.php` | Borrower QR confirmation |
| POST | `/api/reservations/mark_returned.php` | Mark tool as returned |

### Payments
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/payments/get_payments.php` | Payment history |
| POST | `/api/payments/add_balance.php` | Top up wallet |
| POST | `/api/payments/process_deposit.php` | Process security deposit |
| POST | `/api/payments/apply_penalty.php` | Apply penalty charge |
| POST | `/api/payments/refund.php` | Refund payment |

---

## 🛠️ Tech Stack

- **Backend:** PHP 8+ (no framework — pure OOP)
- **Database:** MySQL with PDO (prepared statements)
- **Frontend:** HTML, CSS, Vanilla JS
- **Email:** PHPMailer
- **Auth:** PHP Sessions with role-based access
- **Design Patterns:** Observer, Strategy, Proxy

---

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

---

<div align="center">
  Built with PHP 🐘 &nbsp;·&nbsp; No frameworks. Pure OOP.
</div>
