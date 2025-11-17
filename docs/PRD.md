# SikaPay - Product Requirements Document (PRD)

**Version:** 1.0  
**Date:** 2025-11-16  
**Status:** Draft

---

## 1. Introduction

### 1.1. Purpose

This document outlines the product requirements for SikaPay, a multi-tenant Software-as-a-Service (SaaS) application. SikaPay provides a comprehensive payroll processing solution for businesses in Ghana, ensuring compliance with local statutory regulations.

### 1.2. Scope

This PRD covers the existing functionality of the SikaPay platform as of v1.2. It details user roles, core features, architectural principles, and non-functional requirements. The primary goal is to serve as a foundational document for current system understanding and future development planning.

### 1.3. Definitions

| Term | Definition |
| :--- | :--- |
| **SaaS** | Software-as-a-Service |
| **Multi-Tenancy** | A software architecture where a single instance of software serves multiple distinct user groups (tenants). |
| **Tenant** | A client company or organization using SikaPay for its payroll needs. |
| **Super Admin** | An administrator with system-wide access, responsible for managing tenants. |
| **Tenant Admin** | An administrator responsible for managing a single tenant's payroll, employees, and settings. |
| **Employee** | An end-user within a tenant organization who can view their personal and payroll information. |
| **RBAC** | Role-Based Access Control. |
| **PAYE** | Pay As You Earn; income tax deducted from salary. |
| **SSNIT** | Social Security and National Insurance Trust; Ghana's national pension scheme. |

---

## 2. User Personas & Roles

The system defines three primary user roles, each with a distinct set of permissions and capabilities.

### 2.1. Super Admin

*   **Objective:** To manage the entire SikaPay platform and its client tenants.
*   **Key Capabilities:**
    *   Create, view, update, and deactivate tenant accounts.
    *   Provision new tenants, which includes creating the tenant entity, the initial Tenant Admin user, and a trial subscription.
    *   View system-wide activity logs.
    *   Manage subscription plans.

### 2.2. Tenant Admin

*   **Objective:** To manage all payroll and HR-related functions for their specific company (tenant).
*   **Key Capabilities:**
    *   View a dashboard with key company metrics (e.g., active employees, payroll summaries).
    *   Full CRUD (Create, Read, Update, Delete) functionality for employees, departments, and positions.
    *   Define and manage custom payroll elements (allowances and deductions).
    *   Create payroll periods and execute payroll runs.
    *   View and download generated payslips for all employees.
    *   Generate and download statutory reports (SSNIT, GRA/PAYE) and bank advice files.
    *   Manage the company's profile, including logo.
    *   View tenant-specific activity logs.

### 2.3. Employee

*   **Objective:** To access personal employment and payroll information.
*   **Key Capabilities:**
    *   Log in to a dedicated self-service portal.
    *   View and update personal profile information.
    *   View and download historical payslips.
    *   Manage personal staff files (e.g., upload documents).

---

## 3. System Architecture & Design Principles

### 3.1. Core Architecture

SikaPay is built using a classic **Model-View-Controller (MVC)** pattern, which promotes a clear separation of concerns:
*   **Models (`app/Models`):** Manage data logic and interact with the database. A base model enforces multi-tenant data scoping.
*   **Views (`resources/views`):** Handle the presentation layer, rendering data into HTML templates for the user.
*   **Controllers (`app/Controllers`):** Process user input, interact with models, and select the appropriate view to render.

### 3.2. Key Design Patterns

*   **Front Controller:** All HTTP requests are routed through a single entry point (`public/index.php`), which initializes the application and handles routing.
*   **Singleton Pattern:** Core services like `Application`, `Database`, and `Auth` are implemented as singletons to ensure a single, globally accessible instance manages the application state.
*   **Middleware:** The router supports middleware to process requests before they reach the controller. This is used for security concerns like authentication, CSRF protection, and authorization.

### 3.3. Security Principles

*   **Strict Multi-Tenancy:** Data is isolated at the database level using a `tenant_id` column on nearly all tables. The core `Model` class automatically scopes queries to the currently authenticated user's tenant, preventing data leakage.
*   **Role-Based Access Control (RBAC):** Access to routes is controlled by a `PermissionMiddleware`. It checks the user's assigned role against a list of permitted roles for each route, ensuring users can only access appropriate functionality.
*   **Authentication:** A dedicated `AuthMiddleware` protects routes from unauthenticated access.
*   **CSRF Protection:** A `CsrfMiddleware` generates and validates tokens on POST requests to prevent Cross-Site Request Forgery attacks.

---

## 4. Functional Requirements (Features)

### 4.1. Authentication Module
*   User login for all roles (Super Admin, Tenant Admin, Employee).
*   Secure session management.
*   User logout.

### 4.2. Super Admin Module (Tenant Management)
*   Transactional creation of new tenants, their admin user, and initial subscription.
*   Dashboard for viewing and managing all tenants.
*   System-level user and activity monitoring.

### 4.3. Tenant Admin Module

#### 4.3.1. Dashboard
*   Displays key performance indicators (KPIs) such as active employee count, number of departments, and subscription status.
*   Visualizes payroll history with a graph.
*   Lists recent hires and upcoming work anniversaries.

#### 4.3.2. Employee Management
*   Full CRUD for employee records, including personal details, bank information, and statutory numbers (SSNIT, TIN).
*   Management of company departments and job positions.

#### 4.3.3. Payroll Management
*   Configuration of tenant-specific payroll settings.
*   CRUD for custom allowances and deductions.
*   Creation of monthly payroll periods.
*   Execution of payroll runs, which automatically calculate salary components for all employees.
*   Generation of individual employee payslips in PDF format.

#### 4.3.4. Reporting
*   Generation of statutory reports for SSNIT and GRA (PAYE) in both PDF and Excel formats.
*   Generation of bank advice reports for salary disbursement in both PDF and Excel formats.

#### 4.3.5. Company Profile & Settings
*   Allows Tenant Admins to update their company name, contact information, and logo.

### 4.4. Employee Self-Service Module
*   Secure login for employees.
*   A profile page to view personal, contact, and employment details.
*   Ability to upload and manage personal staff files.
*   A dedicated section to view and download all historical payslips.

### 4.5. Notifications System
*   In-app notification generation for critical system events (e.g., new tenant created, payroll run completed).
*   A dynamic navbar dropdown showing recent, unread notifications.
*   A dedicated page to view all historical notifications.

### 4.6. Audit & Activity Logging
*   Logs critical events such as user logins, tenant creation, and payroll execution.
*   A dedicated "Activity Log" page, with views tailored to the user's role (system-wide for Super Admin, tenant-specific for Tenant Admin).

---

## 5. Non-Functional Requirements

### 5.1. Security
*   All data must be segregated by tenant. There shall be no cross-tenant data visibility, except for Super Admins performing system management.
*   Passwords must be securely hashed.
*   The system must be protected against common web vulnerabilities (XSS, CSRF, SQL Injection).

### 5.2. Scalability
*   The application should be able to handle a growing number of tenants and employees without significant performance degradation.

### 5.3. Usability
*   The user interface should be intuitive and easy to navigate for all user roles.
*   Critical actions should have clear confirmations and feedback.

---

## 6. Future Scope

The following high-level features are identified as the next priorities for development:

*   **Email Service Integration:** Integrate an email service (e.g., using an SMTP provider) to send real-time notifications for critical events, password resets, and reports.
*   **Enhanced Employee Self-Service:** Expand the employee portal with more features, such as leave requests, expense claims, and performance-related information.
*   **Payroll Approval Flow:** Implement a multi-step approval process for payroll runs within tenant organizations.
*   **Subscription & Billing Management:** Build out the functionality for tenants to manage their subscriptions, view invoices, and make payments directly within the application.
