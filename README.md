# 💰 SikaPay: Multi-Tenant Ghanaian Payroll System

SikaPay is a custom-built, multi-tenant web application designed to handle payroll processing in Ghana, strictly adhering to all statutory requirements (PAYE, SSNIT Tiers 1, 2, 3) and accommodating complex pay components and international hires.

---

## 🛠️ Technology Stack

* **Language:** PHP 8.1+ (Vanilla MVC)
* **Database:** MySQL / MariaDB
* **Dependencies:** Composer, FastRoute, FPDF, PhpSpreadsheet, vlucas/phpdotenv, Monolog.

## 🚀 Setup Guide

1.  **Clone Repository:** (If checking out)
    ```bash
    git clone https://github.com/jeffreykwakye/sikapay.git
    cd sikapay
    ```
2.  **Configuration:** Create a configuration file.
    ```bash
    cp app/config.example.php app/config.php
    ```
    Now, edit `app/config.php` and fill in your database and mail server details.
3.  **Composer:** Install PHP dependencies.
    ```bash
    composer install
    ```
4.  **Virtual Host:** Ensure your web server points the document root to the `/public` directory (e.g., `sikapay.localhost`).
5.  **Database:** Create the database defined in your `app/config.php` file (e.g., `sikapay_db`).

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
| **Authentication** | Login/Logout Flow **(Auth Service is now Singleton)** | Complete | Centralized session management and guaranteed single instance. |
| **Security/RBAC** | **Role-Based Access Control (RBAC)** | **Core Complete** | **Central `Auth::can()` gate, Permission Middleware, and protected initial routes.** |
| **Multi-Tenancy** | Data Scoping & Isolation | Core Complete | Base Model enforces `WHERE tenant_id = X`. |
| **Tenant Provisioning** | **Full Creation Workflow (CRUD-C)** | **Complete** | **Transactional creation** of Tenant, Admin User, Subscription, and Audit Log records. |
| **Employee Management** | CRUD for Employees, Departments, and Positions; Active/Inactive Staff Views | **Complete** | Full employee lifecycle management, including personal, statutory, and bank information, with categorized staff views. |
| **Payroll Core** | Database schema for tax bands, SSNIT rates, payroll settings, periods, payslips, and employee payroll details; Core calculation logic, service, controller, and view implemented. Payslip generation and viewing functionality included. | **Complete** | Foundation laid for the core payroll engine. |
| **Payroll Configuration** | Manage custom Allowances & Deductions (tenant-level). | **Complete** | Full CRUD for defining payroll elements (name, type, taxable, etc.) and assigning them to employees. |
| **Statutory Reports** | Generation of PAYE and SSNIT reports in PDF and Excel formats. | **Complete** | Allows tenants to generate statutory reports for compliance. |
| **Company Profile** | Tenant Profile Management | **Complete** | Allows tenants to manage their own company profile, including logo upload. |
| **Dashboard** | **Tenant Dashboard Overhaul** | **Complete** | Features dynamic KPI cards for key metrics (employees, departments, subscription), a payroll summary graph, and lists for recent hires and anniversaries. |
| **Subscriptions** | Initial Trial Provisioning; Tenant Subscription Details & History; Automated Subscription Lifecycle Management | **Complete** | Dedicated tables populated transactionally, with a tenant-facing view for current plan, features, and history. |
| **Audit/Compliance** | Audit Logging & Activity Page | **Complete** | Logs critical actions and provides a dedicated, role-aware page for viewing system and tenant-level activity. |
| **In-App Notifications**| Full System Pipeline | **Complete** | Real-time alerts, a dynamic navbar dropdown with recent notifications, and a dedicated page for all notifications. |
| **Employee Profile** | View, Profile Picture Upload, Staff File Upload, Staff File Deletion | **Complete** | Modern, two-column layout with tabbed navigation. |
| **Payroll & Reporting Fixes** | Payslip generation, data accuracy (gross pay, total deductions), and addition of SSNIT/TIN numbers to reports. | **Complete** | Resolved payroll run errors and enhanced statutory reports with critical employee identifiers. |
| **Employee Self-Service** | View Personal/Employment Info, View/Download Payslips, Create own profile if non-existent. | **Core Complete** | Dedicated portal for employees to access their data and manage basic information. |
| **Advanced Payroll Logic** | Conditional tax/SSNIT logic for different employment types (Contract, Intern, National-Service, Casual-Worker). | **Complete** | Implemented withholding tax for contractors/casuals and exemptions for interns/NSS. |
| **Super Admin Features** | **Dashboard Overhaul, Subscription & Plan Management (CRUD), Automated Subscription Lifecycle, Statutory Rates Management (CRUD)** | **Complete** | Full suite of tools for Super Admins to manage the entire platform, including revenue metrics, plan creation, subscription lifecycle, and global statutory rates. |
| **Support Messaging** | Tenant-to-Super Admin Support Tickets with Replies; Super Admin Interface | **Complete** | Tenants can submit/reply to tickets; Super Admins can view/respond to all tickets, with notification system and open ticket count badge. |
| **Leave Management** | Comprehensive Leave Application, Approval, and Balance Tracking | **Complete** | Full CRUD for leave types, application workflow, and employee leave balance management. |

| **Configuration System** | Replaced .env with native app/config.php; refactored AppConfig | **Complete** | Supports shared hosting environments and improves configuration management. |

---

## 🔑 Access & Credentials

| Role | Email | Password | Access |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@sikapay.local` | `password` | System-wide (Tenant ID 1) |
| **Tenant Admin (Example)** | `beta.admin@beta.com` | `password` | Tenant-scoped (e.g., Tenant ID 3) |

**Note on Tenant Credentials:** New tenants (e.g., `beta.admin@beta.com`) are now created directly via the Super Admin Dashboard and are fully operational for testing.

---



## 🚀 Next Focus Area







**Super Admin - Impersonate Tenant Admin**







*   **Objective:** Allow Super Admins to temporarily assume the identity of a Tenant Admin for a specific tenant to assist with tenant-specific operations (e.g., user management, payroll runs).



*   **Future Tasks:**



    *   Implement secure session switching and restoration mechanisms in the `Auth` module.



    *   Develop UI integration for initiating and exiting impersonation on the tenant details page.



    *   Ensure robust audit logging of all impersonation activities for security and compliance.


