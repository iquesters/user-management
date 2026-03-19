# Roles Documentation for Users

## Purpose
The Roles feature controls what each person can see and do in the platform. It helps keep data secure and ensures users only access what they need.

## Who This Is For
- Business users
- Team administrators
- Operations/support users

## Role Types

### Super Admin
The highest access level in the platform.

Super Admin can:
- Access all modules and settings
- Create, update, and remove users
- Assign and change roles
- Manage all organizations
- View and manage all system data

Use this role for platform-level ownership and governance.

### Organisation Admin
An administrator for one specific organization.

Organisation Admin can:
- Manage users inside their organization
- Assign roles within their organization
- View and manage organization-specific data

Organisation Admin cannot:
- Access other organizations
- Change global platform settings reserved for Super Admin

Use this role for day-to-day management of a single organization.

## How Roles Are Used
1. A user is assigned to an organization.
2. An admin assigns the correct role.
3. The system grants access based on role and organization scope.

## Common Scenarios

### I cannot see a page or action
Your current role may not include that permission. Contact your admin to review access.

### I changed teams or responsibilities
Your role should be updated to match your new responsibility.

### I need access to another organization
Organisation Admin cannot access other organizations. Request escalation to a Super Admin.

## Security Principles (User View)
- Least privilege: only required access is granted
- Separation of duties: global and organization administration are separated
- Organization isolation: organization data is not shared across tenants

## Quick Summary
- Super Admin manages the full platform.
- Organisation Admin manages one organization only.
- Access is based on both role and organization.