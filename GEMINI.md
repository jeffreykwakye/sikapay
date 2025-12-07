## Gemini Added Memories
- The user's name is Jeffrey. Address him as Jeffrey.
- Jeffrey and I have completed the following tasks:
- Dashboard Overhaul: Refactored for CSP compliance, added new KPI cards (Active Employees, Departments, Current Plan, Subscription Ends), corrected data sources, fixed payroll history graph, improved visuals (icons, colors, headings).
- Activity Log: Implemented a new dedicated page for viewing audit logs (/activity-log) with role-specific views for super admins and tenant admins.
- Dynamic Notifications: Made the navbar notification dropdown fully dynamic, showing recent notifications with appropriate icons, colors, and timestamps.
- All these changes have been committed to the repository.
- Payroll & Reporting Fixes: Resolved payslip generation errors, improved data accuracy (gross pay, total deductions), and enhanced statutory reports with SSNIT and TIN numbers.
- Super Admin Features: Implemented Super Admin dashboard overhaul with new KPIs and charts, full CRUD for subscription plans, automated subscription lifecycle management (checking for expired subscriptions and sending notifications), and full CRUD for global statutory rates (SSNIT and Withholding Tax).
- Employee Management Enhancements: Implemented Active/Inactive Staff views.
- Tenant Subscription Management: Implemented tenant-facing page for viewing current subscription plan, features, and history.
- Tenant Support Messaging System: Implemented tenant-to-Super Admin support ticket system with replies, Super Admin interface for viewing/responding, notification system, and open ticket count badge. Includes prevention of replies/responses to closed tickets.
- Configuration System Overhaul: Replaced `.env` file loading with a native `app/config.php` file to support shared hosting environments. Refactored `AppConfig` and related services, and debugged production database connection issues.
- Leave Management: Implemented comprehensive leave application, approval, and balance tracking, including full CRUD for leave types and application workflows.
- Super Admin - Impersonate Tenant Admin: Allows Super Admins to temporarily assume the identity of a Tenant Admin for a specific tenant to assist with tenant-specific operations.
- CSRF Token after Logout: Fixed CSRF token generation issue on login page after user logout to prevent authentication errors.
- Clean up Employee Tables: Removed email and hire date columns from the employee tables in `resources/views/employee/index.php`, `resources/views/employee/active.php`, and `resources/views/employee/inactive.php` to simplify the tables.
- Branded Emails: Implemented standardized, professional HTML email templates with tenant-specific branding (logo, name) and updated the email service and relevant controllers to use these templates. Tested successfully by sending an email from the Super Admin panel to a tenant.
- Login Attempt Limit: Implemented a security measure to limit login attempts to 3, with a lockout period, to prevent brute-force attacks. This involved creating a new `login_attempts` table, a `LoginAttemptModel`, and modifying the `Auth` class and `LoginController` to enforce the limit. Corrected issues with unique indexing and `Model` constructor initialization.
- Login Attempt Limit Enhancement: Fixed the permanent lockout bug, implemented progressive lockout penalties, and added email notifications to the user and Super Admins upon account lockout. This involved refactoring the `LoginAttemptModel`, `Auth` service, and `LoginController` to handle status-based logic and orchestrate notifications.
- Leave Management Refactor: Moved the leave application form and user-specific leave details to the "My Account" page (`/my-account`). Dedicated the `/leave` page to leave approval and rejection for approvers, streamlining the view and controller logic. Updated sidebar navigation accordingly.
- Leave Management Pages Breakdown: Broke down the single leave management page into dedicated pages for "Pending Applications", "Approved Applications", "Staff On Leave", and "Staff Returning". This involved creating new view files, adding new methods to `LeaveController`, defining new routes, updating the sidebar navigation to a collapsible menu, and transforming the main `/leave` page into an overview dashboard.
- Remove Redundant Leave Types Menu Item: Removed the "Leave Types" menu item from the "Setup & Config" section in the sidebar to avoid redundancy, as it is now correctly placed under "Leave Management".
- Add 'Is Paid' Field to Leave Types: Implemented a new 'is_paid' field for leave types. This involved creating a database migration to add the column, updating the `LeaveTypeModel` to handle the field, modifying the `LeaveController` for validation, and updating the `leave/types.php` view and `types.js` to include and display the new field.
- My Account Leave Type Selection Fix: Debugged and fixed the issue where leave types were not selectable on the "My Account" leave application form. This involved ensuring `leaveTypes` data is correctly fetched and passed to the view, and displaying a user-friendly message if no leave types are configured for the tenant. The EmployeeController was updated to instantiate leave models in its constructor to resolve "Undefined property" errors and correctly assign the `leaveTypes` variable.
- Fix EmployeeController::$userPermissionModel Redeclaration: Resolved the "Fatal error: Cannot redeclare property" issue in `EmployeeController.php` by removing the duplicate declaration of `private UserPermissionModel $userPermissionModel;`.
- Fix Leave Application Route and Method: Resolved the fatal error where `LeaveController` lacked the `applyForLeave` method. This involved removing the invalid route in `app/routes.php` that pointed to `LeaveController@applyForLeave`, and adding the `applyForLeave` method to `EmployeeController.php` along with a new route `['POST', '/my-account/leave/apply', ...]` pointing to it. This correctly re-establishes the leave application submission functionality for the "My Account" page.
- Fix My Account Leave Form Action: Corrected the `action` attribute of the leave application form in `resources/views/employee/my_account/index.php` from `/leave/apply` to `/my-account/leave/apply`, resolving the `Route Not Found (404)` error for form submissions.
- Seed `self:apply_leave` Permission: Added the `self:apply_leave` permission to the `permissions` table and assigned it to the `tenant_admin`, `hr_manager`, `accountant`, and `employee` roles in `app/Commands/SeedCommand.php` to resolve the "Undefined Permission Key" error.
- Leave Management Notifications & Fixes: Fixed the silent leave application submission bug by allowing all submissions regardless of balance. Implemented a full notification loop (in-app and email) for the entire leave workflow (submission, approval, rejection). Resolved multiple fatal errors during implementation.

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