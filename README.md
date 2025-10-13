# 💰 SikaPay: Multi-Tenant Ghanaian Payroll System

SikaPay is a custom-built, multi-tenant web application designed to handle payroll processing in Ghana, strictly adhering to all statutory requirements (PAYE, SSNIT Tiers 1, 2, 3) and accommodating complex pay components and international hires.

## 🛠️ Technology Stack

* **Language:** PHP 8.1+ (Vanilla MVC)
* **Database:** MySQL / MariaDB
* **Dependencies:** Composer, FastRoute, FPDF, PhpSpreadsheet, vlucas/phpdotenv.

## 🚀 Setup Guide

1.  **Clone Repository:** (If checking out)
    ```bash
    git clone [https://github.com/jeffreykwakye/sikapay.git](https://github.com/jeffreykwakye/sikapay.git)
    cd sikapay
    ```
2.  **Environment:** Create a `.env` file in the root directory.
    * *Note: See the `PROJECT_CONTEXT.md` for specific required environment variables.*
3.  **Composer:** Install PHP dependencies.
    ```bash
    composer install
    ```
4.  **Virtual Host:** Ensure your web server points the document root to the `/public` directory (e.g., `sikapay.localhost`).
5.  **Database:** Create the database defined in your `.env` file (e.g., `sikapay`).

## 🛠️ Installation and Initial Data

1.  **CLI Setup:** After PHP files are placed, run migrations and seed initial data.
    ```bash
    php cli/cli_runner.php db:migrate
    php cli/cli_runner.php db:seed
    ```
    * **Note:** This process creates the initial **Super Admin** user and **System Tenant** data.

2.  **Access:** Open your configured URL (e.g., `http://sikapay.localhost/`).

---

## 🔑 Access & Credentials

The core authentication system is operational and multi-tenancy is active.

| Role | Email | Password | Access |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@sikapay.local` | `password` | System-wide (Tenant ID 1) |
| **Tenant Admin** | `tenant.admin@acmecorp.com` | `password` | Tenant-scoped (Tenant ID 2) |

**Note on Tenant Credentials:** The `tenant.admin@acmecorp.com` user and its Tenant (ID 2) must be manually created in the database for testing purposes.

---

## ✨ Architectural & Working Features

### Architectural Highlights
* **Front Controller:** All requests are routed through `public/index.php`.
* **Routing:** Uses the **FastRoute** package via a custom `Router` class (`app/Core/Router.php`).
* **Multi-Tenancy Scoping:** Core data isolation is enforced automatically in `app/Core/Model.php` by checking the user's role and injecting a `WHERE tenant_id = X` clause into all non-admin queries.

### Working Features
* **Full Authentication Flow:** Login and Logout are functional and secure.
* **Secure Routing:** URL Routing (`/login`, `/dashboard`, `/logout`) is protected.
* **Security:** Browser back/forward caching is disabled for authenticated routes to prevent session fixation/hijacking after logout.
* **Data Scoping (Multi-Tenancy Isolation):** Fully validated and implemented.