# 💰 SikaPay: Multi-Tenant Ghanaian Payroll System

SikaPay is a custom-built, multi-tenant web application designed to handle payroll processing in Ghana, strictly adhering to all statutory requirements (PAYE, SSNIT Tiers 1, 2, 3) and accommodating complex pay components and international hires.

## 🛠️ Technology Stack

* **Language:** PHP 8.1+ (Vanilla MVC)
* **Database:** MySQL / MariaDB
* **Dependencies:** Composer, FastRoute, FPDF, PhpSpreadsheet, vlucas/phpdotenv.

## 🚀 Setup Guide

1.  **Clone Repository:** (If checking out)
    ```bash
    git clone https://github.com/jeffreykwakye/sikapay.git
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
6.  **CLI Setup:** After PHP files are placed, run migrations and seed initial data.
    ```bash
    php cli/cli_runner.php db:migrate
    php cli/cli_runner.php db:seed
    ```
7.  **Access:** Open your configured URL (e.g., `http://sikapay.localhost/`).


# 🏢 SikaPay - Core Project Context and Architecture

## I. Multi-Tenancy & Security Layer (Phase 1: Complete)

The core foundation is a multi-tenant SaaS application designed for high security and isolation.

### Key Data Structures Implemented:

| Table Group | Purpose | Key Features |
| :--- | :--- | :--- |
| **Tenancy** | Core Isolation | `tenants`, `tenant_profiles` (Branding), **`payroll_approval_flow`** setting. |
| **User Management** | Identity & Access | `users` (with `other_name`), `user_profiles` (Compliance/Ghana Card, SSNIT, TIN). |
| **RBAC** | Granular Permissions | `roles`, **`permissions`**, **`role_permissions`**, **`user_permissions`** (for overrides). |
| **Employment** | HR Records & History | **`departments`**, **`positions`** (separated), `employees`, **`employment_history`** (Tracking promotions/salaries). |
| **Billing** | Feature Gating | `plans`, `features`, `plan_features` (includes **seat limits** for HR/Accountant roles), `subscriptions`, `subscription_history`. |

### Status:

* Database Schema: **Complete and Migrated.**
* Seeding: **Successful** (Initial Roles, Plans, Permissions, and Super Admin are active).

## II. Next Focus: Authentication & Routing

The immediate priority is to build the front-end login mechanism to validate the core `Auth.php` and tenancy model.

* **Target:** Implement `LoginController`, `BaseController`, and the login view.