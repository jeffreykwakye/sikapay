# 💰 SikaPay: Multi-Tenant Ghanaian Payroll System

SikaPay is a custom-built, multi-tenant web application designed to handle payroll processing in Ghana, strictly adhering to all statutory requirements (PAYE, SSNIT Tiers 1, 2, 3) and accommodating complex pay components and international hires.

> [!NOTE]
> For the technology stack, setup guide, and high-level feature overview, please refer to the main [**README.md**](../README.md) file. This document focuses on the detailed architectural context and historical project log.

## Project Status: Core Infrastructure & RBAC Implemented (v1.2)
---

### **Current Features Implemented**

| Module | Features | Status | Notes |
| :--- | :--- | :--- | :--- |
| **Authentication** | Super Admin Login, Tenant User Login, Logout **(Auth Service is now Singleton)** | Complete | Centralized, secure session management. |
| **Security/Authentication** | **Brute-force Protection (Login Attempts)** | **Complete** | **Limits login attempts. Implements temporary, extending lockouts with email alerts to user and admins.** |
| **Security/RBAC** | **Role-Based Access Control (RBAC)** | **Core Complete** | **Implemented central `Auth::can()` gate, Permission Middleware, and protected initial routes.** |
| **Multi-Tenancy** | Request/Tenant Routing, Database Scoping | Core Complete | Base Model enforces `WHERE tenant_id = X` on most tables. |
| **Super Admin Features** | **Dashboard Overhaul, Subscription & Plan Management (CRUD), Automated Subscription Lifecycle, Statutory Rates Management (CRUD)** | **Complete** | Full suite of tools for Super Admins to manage the entire platform, including revenue metrics, plan creation, subscription lifecycle, and global statutory rates. |
| **Audit/Compliance** | Audit Logging & Activity Page | **Complete** | Logs critical actions and provides a dedicated, role-aware page for viewing system and tenant-level activity. |
| **In-App Notifications (NEW)** | **E2E In-App System** | **Complete** | Includes service, model, controller, dynamic navbar dropdown, and a dedicated page for all notifications. |
| **Dashboard (NEW)** | **Tenant Dashboard Overhaul** | **Complete** | Features dynamic KPI cards for key metrics, a payroll summary graph, and lists for recent hires and anniversaries. |
| **Employee Profile** | View, Profile Picture Upload, Staff File Upload, Staff File Deletion | **Complete** | Modern, two-column layout with tabbed navigation. |
| **Payroll Configuration** | Manage custom Allowances & Deductions. | **Complete** | Full CRUD UI for tenant admins to define and manage payroll elements. |
| **Employee Management** | CRUD for Employees, Departments, and Positions; Active/Inactive Staff Views; Immediate Basic Salary Update with History Logging | **Complete** | Full employee lifecycle management, including personal, statutory, and bank information, with categorized staff views. |
| **Subscriptions** | Initial Trial Provisioning; Tenant Subscription Details & History; Automated Subscription Lifecycle Management | **Complete** | Dedicated tables populated transactionally, with a tenant-facing view for current plan, features, and history. |
| **Payroll Core** | Database schema for tax bands, SSNIT rates, payroll settings, periods, payslips, and employee payroll details; Core calculation logic, service, controller, and view implemented. Payslip PDF generation and viewing functionality included. | **Complete** | Foundation laid for the core payroll engine. |
| **Statutory Reports** | Generation of PAYE and SSNIT reports in PDF and Excel formats. | **Complete** | Allows tenants to generate statutory reports for compliance. |
| **Company Profile** | Tenant Profile Management | **Complete** | Allows tenants to manage their own company profile, including logo upload. |
| **Payroll & Reporting Fixes** | Payslip generation, data accuracy (gross pay, total deductions), and addition of SSNIT/TIN numbers to reports. | **Complete** | Resolved payroll run errors and enhanced statutory reports with critical employee identifiers. |
| **Employee Self-Service** | View Personal/Employment Info, View/Download Payslips, Create own profile if non-existent. | **Core Complete** | Dedicated portal for employees to access their data and manage basic information. |
| **Advanced Payroll Logic** | Conditional tax/SSNIT logic for different employment types (Contract, Intern, National-Service, Casual-Worker). | **Complete** | Implemented withholding tax for contractors/casuals and exemptions for interns/NSS. |
| **Configuration System** | Replaced .env with native app/config.php; refactored AppConfig | **Complete** | Supports shared hosting environments and improves configuration management. |
| **Support Messaging** | Tenant-to-Super Admin Support Tickets with Replies; Super Admin Interface | **Complete** | Tenants can submit/reply to tickets; Super Admins can view/respond to all tickets, with notification system and open ticket count badge. |
| **Leave Management** | Comprehensive Leave Application, Approval, and Balance Tracking | **Complete** | Full workflow for leave application, approval, and balance tracking. Includes a complete notification loop (in-app & email) for submissions, approvals, and rejections. |

---

### **Testing Credentials**

| User Role | Email | Password |
| :--- | :--- | :--- |
| **Super Admin** | `admin@sikapay.local` | `password` |
| **Tenant Admin (Example)** | `beta.admin@beta.com` | `password` |


# 🏢 SikaPay - Core Project Context and Architecture

## I. Multi-Tenancy & Security Layer (Phase 2: Complete)

The core foundation is a multi-tenant SaaS application designed for high security and isolation.

### Key Data Structures Implemented:

| Table Group | Purpose | Key Features |
| :--- | :--- | :--- |
| **Tenancy** | Core Isolation | `tenants`, `tenant_profiles`, `payroll_approval_flow` setting. |
| **User Management** | Identity & Access | `users`, `user_profiles`. |
| **RBAC** | Granular Permissions | `roles`, `permissions`, `role_permissions`, `user_permissions`. |
| **Employment** | HR Records & History | `departments`, `positions`, `employees`, `employment_history`. |
| **Billing** | Feature Gating | `plans`, `features`, `plan_features`, `subscriptions`, `subscription_history`. |
| **Payroll** | Core Payroll Data & Logic | `tax_bands`, `ssnit_rates`, `payroll_settings`, `payroll_periods`, `payslips`, `employee_payroll_details`, `PayrollService`, `PayrollController`. Payslip PDF generation and viewing functionality implemented. |
| **Payroll Core** | Foundational Payroll Data | `tax_bands`, `ssnit_rates`, `payroll_settings`, `payroll_periods`, `employee_payroll_details`. |
| **Application** | User Feedback | `notifications` (Table structure is migrated). |

---

## II. Architectural Patterns & Core Implementation (Phase 2: Complete)

### A. Authentication and Authorization (New Core System)

| Component | Implementation Detail | Rationale | Status |
| :--- | :--- | :--- | :--- |
| **Auth Service** | **Singleton Pattern** applied to `app/Core/Auth.php`. | Guarantees a single, stateful instance for DB checks and session access. | **Complete** |
| **Base Controller**| `__construct()` updated to use `Auth::getInstance()`. | Fixed the Fatal Error and adheres to the Singleton pattern. | **Complete** |
| **Authorization Gate**| **Central `Auth::can($key)` method** implements the 3-tiered logic (Super Admin > User Override > Role Default). | Single source of truth for all access control decisions. | **Complete** |
| **Route Protection** | **`app/Middleware/PermissionMiddleware.php`** created. | Centralizes pre-controller security checks and denial handling. | **Complete** |
| **Router Support** | `app/Core/Router.php` updated to support and execute the `['middleware' => [...], 'handler' => [...] ]` route structure. | Enables declarative, per-route access control. | **Complete** |

### B. Transactional Provisioning Workflow (Super Admin)

The tenant creation process adheres to the **Single Responsibility Principle (SRP)**, with the `TenantController::store()` method acting as a dedicated **Orchestrator** for a single, critical database transaction.

| Responsibility | Model/Service | Details |
| :--- | :--- | :--- |
| **Orchestration** | `TenantController` | Manages `PDO::beginTransaction()` and `PDO::commit()`/`rollBack()` to ensure atomicity. |
| **Provisioning** | `TenantModel`, `UserModel` | Creates the new tenant record and the associated primary admin user. |
| **Subscription** | `SubscriptionModel` | Inserts the initial **trial** record into `subscriptions` (current state) and `subscription_history` (audit trail). |
| **Compliance** | `AuditModel` | Logs the creation event, citing the new `tenantId` and the acting Super Admin's `user_id`. |
| **Notification Trigger** | `NotificationService` | Triggered *outside* the main transaction (post-commit) to alert the Super Admin of success. |

---

# SikaPay Project Context Log (Updated)

| Date | Feature/Decision | Details | Status |
| :--- | :--- | :--- | :--- |
| **2025-10-13** | **Authentication (Super Admin)** | Implemented `Auth` service, `LoginController`, and session management. | Complete |
| **2025-10-13** | **Multi-Tenancy Scoping** | Implemented `getTenantScope()` logic in `app/Core/Model.php`. Super Admin bypass confirmed. | Complete |
| **2025-10-13** | **Tenant Provisioning** | **Full SRP implementation.** Orchestration of Tenant, User, Subscription, and Audit Log creation in a single transaction. | **Complete** |
| **2025-10-13** | **In-App Notifications** | **Full E2E implementation** (Models, Service, Controller, Views, and Header UI integration). | **Complete** |
| **2025-10-15** | **RBAC & Architectural Fix** | **Implemented Singleton pattern for Auth**, fixed Controller instantiation, implemented **Permission Middleware** and updated **Router** to enforce security. | **Complete** |
| **2025-10-15** | **Logging & UX Complete** | **Integrate Monolog** (daily rotation/expiration). Confirmed and implemented the **Tenant Admin Welcom Notification** on provisioning success.| **Complete** |
| **2025-10-30** | **Employee Profile** | Rebuilt the employee profile page with a modern, two-column layout, profile picture upload, staff file management, and staff file deletion. | **Complete** |
| **2025-10-30** | **Payroll Core** | Database schema for tax bands, SSNIT rates, payroll settings, periods, payslips, and employee payroll details; Core calculation logic, service, controller, and view implemented. Payslip PDF generation and viewing functionality included. | **Complete** |
| **2025-10-30** | **Statutory Reports** | Generation of PAYE and SSNIT reports in PDF and Excel formats. | **Complete** | Allows tenants to generate statutory reports for compliance. |
| **2025-11-03** | **Payroll Element Management** | Implemented and fixed the full feature for creating, updating, and deleting tenant-level allowances and deductions. This includes the backend models, controllers, routes, and the frontend UI with its client-side logic. | **Complete** |
| **2025-11-08** | **Dashboard & Notifications UX** | Overhauled the tenant dashboard with dynamic KPI cards and graphs. Implemented a fully dynamic notification dropdown. Created a dedicated, role-aware Activity Log page. | **Complete** |
| **2025-11-15** | **Payroll Notifications & Auth View Fix** | Implemented `createNotificationForRole` in `NotificationService`, added `getUsersByRole` to `UserModel`, integrated notifications into `PayrollController::createPeriod` and `runPayroll`, and passed `Auth` instance to `payroll/index.php` view. | **Complete** |
| **2025-11-17** | **Employee Self-Service Portal (Initial Setup)** | Created dedicated `/my-account` route, `myAccountIndex` controller method, and `my_account/index.php` view with basic tabbed layout. Updated sidebar link and permissions (`self:view_profile`). Fixed `Auth::user()` call and `Model` constructor tenant context check. | **Complete** |
| **2025-11-17** | **Payroll & Reporting Fixes** | Payslip generation, data accuracy (gross pay, total deductions), and addition of SSNIT/TIN numbers to reports. | **Complete** |
| **2025-11-17** | **Advanced Payroll Logic** | Conditional tax/SSNIT logic for different employment types (Contract, Intern, Casual Worker) with correct withholding tax and SSNIT logic. | **Complete** |
| **2025-11-18** | **Super Admin Features** | **Dashboard Overhaul, Subscription & Plan Management (CRUD), Automated Subscription Lifecycle, Statutory Rates Management (CRUD)** | **Complete** | Full suite of tools for Super Admins to manage the entire platform, including revenue metrics, plan creation, subscription lifecycle, and global statutory rates. |
| **2025-11-20** | **Employee Management: Active/Inactive Staff Views** | Implemented dedicated views and filtering for active and inactive employees. | **Complete** |
| **2025-11-20** | **Tenant Subscription Management** | Implemented tenant-facing page for viewing current subscription plan, features, and history. | **Complete** |
| **2025-11-20** | **Tenant Support Messaging System** | Implemented tenant-to-Super Admin support ticket system with replies; Super Admin interface for viewing/responding, notification system, and open ticket count badge. | **Complete** |
| **2025-11-23** | **Configuration System Refactor** | Replaced `.env` file loading with a native `app/config.php` system to support shared hosting environments. Refactored `AppConfig` and related services. | **Complete** |
| **2025-11-24** | **Leave Management** | Implemented comprehensive leave application, approval, and balance tracking, including full CRUD for leave types and application workflows. | **Complete** |
| **2025-12-07** | **Enhanced Login Security** | Fixed permanent lockout bug, added progressive lockout penalties, and implemented email notifications (to user and admins) for brute-force attempts. Refactored LoginAttemptModel, Auth, and LoginController to support the new logic. | **Complete** |
| **2025-12-07** | **Leave Management Workflow & Notifications** | Fixed leave application submission bug by allowing all submissions regardless of balance. Implemented a full notification loop (in-app & email) for the entire leave workflow (submission, approval, rejection). Resolved multiple fatal errors during implementation. | **Complete** |
| **2025-12-07** | **Employee Salary Management & Department View Refinement** | Implemented UI for immediate basic salary updates with history logging. Removed irrelevant payroll columns from department list view for clarity. | **Complete** |