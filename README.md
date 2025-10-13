# 💰 SikaPay: Multi-Tenant Ghanaian Payroll System

SikaPay is a custom-built, multi-tenant web application designed to handle payroll processing in Ghana, strictly adhering to all statutory requirements (PAYE, SSNIT Tiers 1, 2, 3) and accommodating complex pay components and international hires.

---

## 🛠️ Technology Stack

* **Language:** PHP 8.1+ (Vanilla MVC)
* **Database:** MySQL / MariaDB
* **Dependencies:** Composer, FastRoute, FPDF, PhpSpreadsheet, vlucas/phpdotenv.

## 🚀 Setup Guide

1.  **Clone Repository:** (If checking out)
    ```bash
    git clone [https://github.com/jeffreykwakye/sikapay.git](https://github.com/jeffreykwakye/sikapay.git)
    cd sikapay
    ```
2.  **Environment:** Create a `.env` file in the root directory.
    * *Note: See the `PROJECT_CONTEXT.md` for specific required environment variables.*
3.  **Composer:** Install PHP dependencies.
    ```bash
    composer install
    ```
4.  **Virtual Host:** Ensure your web server points the document root to the `/public` directory (e.g., `sikapay.localhost`).
5.  **Database:** Create the database defined in your `.env` file (e.g., `sikapay`).

## 🛠️ Installation and Initial Data

1.  **CLI Setup:** After PHP files are placed, run migrations and seed initial data.
    ```bash
    php cli/cli_runner.php db:migrate
    php cli/cli_runner.php db:seed
    ```
    * **Note:** This process creates the initial **Super Admin** user and **System Tenant** data.

2.  **Access:** Open your configured URL (e.g., `http://sikapay.localhost/`).

---

## ✨ Architectural & Working Features (Current Status)

| Module | Features | Status | Notes |
| :--- | :--- | :--- | :--- |
| **Authentication** | Full Login/Logout Flow | Complete | Secure session management. |
| **Multi-Tenancy** | Data Scoping & Isolation | Core Complete | Base Model enforces `WHERE tenant_id = X` on tenant users; Super Admin bypass implemented. |
| **Tenant Provisioning** | **Full Creation Workflow (CRUD-C)** | **Complete** | **Transactional creation** of Tenant, Admin User, Subscription, and Audit Log records. |
| **Subscriptions** | Initial Trial Provisioning | Complete | Dedicated `subscriptions` and `subscription_history` tables populated. |
| **Audit/Compliance** | Audit Logging | Complete | Logs critical actions using the acting Super Admin's ID and new Tenant ID. |
| **Security** | BFCache Fix & Secure Routing | Complete | Prevents cached session data post-logout. |
| **In-App Notifications** | **Full System Pipeline** | **Complete** | **Real-time alerts, counter badge, mark-as-read functionality, and robust architectural integration.** |

---

## 🔑 Access & Credentials

| Role | Email | Password | Access |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@sikapay.local` | `password` | System-wide (Tenant ID 1) |
| **Tenant Admin (Example)** | `beta.admin@beta.com` | `password` | Tenant-scoped (e.g., Tenant ID 3) |

**Note on Tenant Credentials:** New tenants (e.g., `beta.admin@beta.com`) are now created directly via the Super Admin Dashboard and are fully operational for testing.

---

## 🚀 Next Focus Area

**User Experience Refinements & Core Feature Expansion**
* **Objective:** Enhance usability (e.g., showing user names, auto-marking notifications as read) and begin building out core tenant features (e.g., Payroll setup).