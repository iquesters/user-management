# User Permissions Documentation

## 1. Purpose
The Permissions module controls what actions a user can perform in each module. It helps keep access secure, predictable, and aligned with each users responsibility.

## 2. Audience
This document is for:
- End users
- Organisation administrators
- Support and operations teams

## 3. How Permissions Are Named
Permission keys follow this pattern:
- `<action>-<resource>`

Where:
- `action` can be `view`, `create`, `edit`, or `delete`
- `resource` is the target module/entity

## 4. Permission Areas

### 4.1 Master Data
- `view-master_data`: View master data
- `create-master_data`: Add master data
- `edit-master_data`: Update master data
- `delete-master_data`: Remove master data

### 4.2 Organisations
- `view-organisations`: View organisations
- `create-orghanisations`: Create organisations
- `edit-organisations`: Update organisations
- `delete-organisations`: Remove organisations

### 4.3 Users
- `view-users`: View user accounts
- `create-users`: Create user accounts
- `edit-users`: Update user accounts
- `delete-users`: Remove user accounts

### 4.4 Roles
- `view-roles`: View role definitions
- `create-roles`: Create roles
- `edit-roles`: Update roles
- `delete-roles`: Remove roles

### 4.5 Permissions
- `view-permissions`: View permission definitions
- `create-permissions`: Create permission definitions
- `edit-permissions`: Update permission definitions
- `delete-permissions`: Remove permission definitions

### 4.6 Organisation Channels
- `view-organisation-channels`: View organisation channels
- `create-organisation-channels`: Create organisation channels
- `edit-organisation-channels`: Update organisation channels
- `delete-organisation-channels`: Remove organisation channels

## 5. Access Principles
- Least privilege: users should receive only the permissions they need
- Deny by default: if a permission is not assigned, the action is blocked
- Scope control: permissions are enforced with organization/resource scope

## 6. Common Scenarios
- **Cannot open a module:** Check the required `view-*` permission
- **Cannot save changes:** Check `create-*` or `edit-*` permission
- **Cannot remove records:** Check `delete-*` permission
- **Cannot access another organization's data:** Scope restrictions are working as designed

## 7. Outcome
The Permissions module gives clear, action-level access control so users can do their work while sensitive operations stay protected.
