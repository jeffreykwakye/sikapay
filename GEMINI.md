## Gemini Added Memories
- The user's name is Jeffrey. Address him as Jeffrey.
- Jeffrey and I have completed the following tasks:
- Dashboard Overhaul: Refactored for CSP compliance, added new KPI cards (Active Employees, Departments, Current Plan, Subscription Ends), corrected data sources, fixed payroll history graph, improved visuals (icons, colors, headings).
- Activity Log: Implemented a new dedicated page for viewing audit logs (/activity-log) with role-specific views for super admins and tenant admins.
- Dynamic Notifications: Made the navbar notification dropdown fully dynamic, showing recent notifications with appropriate icons, colors, and timestamps.
- All these changes have been committed to the repository.
- Payroll & Reporting Fixes: Resolved payslip generation errors, improved data accuracy (gross pay, total deductions), and enhanced statutory reports with SSNIT and TIN numbers. This task is complete.
- Super Admin Features: Implemented Super Admin dashboard overhaul with new KPIs and charts, full CRUD for subscription plans, automated subscription lifecycle management (checking for expired subscriptions and sending notifications), and full CRUD for global statutory rates (SSNIT and Withholding Tax).
- Employee Management Enhancements: Implemented Active/Inactive Staff views.
- Tenant Subscription Management: Implemented tenant-facing page for viewing current subscription plan, features, and history.
- Tenant Support Messaging System: Implemented tenant-to-Super Admin support ticket system with replies, Super Admin interface for viewing/responding, notification system, and open ticket count badge. Includes prevention of replies/responses to closed tickets.
- Configuration System Overhaul: Replaced `.env` file loading with a native `app/config.php` file to support shared hosting environments. Refactored `AppConfig` and related services, and debugged production database connection issues.

## SikaPay Project Status (2025-11-17)

### Project Overview
SikaPay is a robust, multi-tenant SaaS payroll application designed for the Ghanaian market, adhering to local statutory requirements. It is built using a vanilla PHP MVC architecture, emphasizing data isolation, comprehensive Role-Based Access Control (RBAC), and modular features.

### Current State
The foundational pillars of SikaPay are successfully implemented:
-   **Tenant Management:** Provisioning, administration, and data isolation are fully functional.
-   **User Management:** Comprehensive RBAC with Super Admin, Tenant Admin, and Employee roles is in place.
-   **Employee Management:** Full CRUD operations for employee records are supported, including active/inactive staff views.
-   **Core Payroll:** The core payroll engine, including calculations and payslip generation, is operational.
-   **Reporting:** Statutory reporting (PAYE, SSNIT) is implemented.
-   **Company Profile:** Tenant company profile management is available.
-   **Dashboard:** A dynamic dashboard with KPIs and payroll summaries is integrated.
-   **Notifications:** An in-app notification system is active.
-   **Audit & Compliance:** Activity logging and a dedicated audit page are functional.
-   **Employee Self-Service Portal (`/my-account`):** Initial setup is complete, and employees can successfully update their profile information. The previous issue causing a crash after profile updates has been resolved.
-   **Super Admin Features:** The Super Admin dashboard has been overhauled with new KPIs and charts. Full CRUD functionality for subscription plans and global statutory rates (SSNIT and Withholding Tax) has been implemented. Automated subscription lifecycle management, including checking for expired subscriptions and sending notifications, is in place.
-   **Tenant Subscription Management:** Implemented tenant-facing page for viewing current subscription plan, features, and history.
-   **Tenant Support Messaging System:** Implemented tenant-to-Super Admin support ticket system with replies, Super Admin interface for viewing/responding, notification system, and open ticket count badge. Includes prevention of replies/responses to closed tickets.

### Immediate Priorities (Next Focus Area)
Based on the Product Requirements Document (PRD) and recent discussions, the immediate priorities for development are:

1.  **Super Admin - Impersonate Tenant Admin:**
    *   **Objective:** Allow Super Admins to temporarily assume the identity of a Tenant Admin for a specific tenant to assist with tenant-specific operations (e.g., user management, payroll runs).
    *   **Future Tasks:**
        *   Implement secure session switching and restoration mechanisms in the `Auth` module.
        *   Develop UI integration for initiating and exiting impersonation on the tenant details page.
        *   Ensure robust audit logging of all impersonation activities for security and compliance.
