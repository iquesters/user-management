# Developer Permissions Documentation

## 1. Objective
Define the permission contract and enforcement rules for fine-grained, auditable authorization across modules.

## 2. Authorization Model
- Authorization is permission-based and deny-by-default
- Every protected operation must validate:
  - Permission key (`action-resource`)
  - Tenant/resource scope
- UI visibility is not a security boundary; server-side checks are mandatory

## 3. Permission Key Contract
Pattern:
- `<action>-<resource>`

Action set:
- `view`, `create`, `edit`, `delete`

Current resource set:
- `master_data`
- `organisations`
- `users`
- `roles`
- `permissions`
- `organisation-channels`

## 4. Permission Matrix

### 4.1 Master Data
- `view-master_data`
- `create-master_data`
- `edit-master_data`
- `delete-master_data`

### 4.2 Organisations
- `view-organisations`
- `create-orghanisations`
- `edit-organisations`
- `delete-organisations`

### 4.3 Users
- `view-users`
- `create-users`
- `edit-users`
- `delete-users`

### 4.4 Roles
- `view-roles`
- `create-roles`
- `edit-roles`
- `delete-roles`

### 4.5 Permissions
- `view-permissions`
- `create-permissions`
- `edit-permissions`
- `delete-permissions`

### 4.6 Organisation Channels
- `view-organisation-channels`
- `create-organisation-channels`
- `edit-organisation-channels`
- `delete-organisation-channels`

## 5. Enforcement Requirements
- Apply authorization middleware/guards to all protected endpoints
- Resolve actor scope before data access
- Enforce tenant filters in query/repository layer
- Reject unknown or unmapped permission keys
- Keep permission checks centralized (policy map/service), not scattered in handlers

## 6. Audit and Security Controls
Log and retain:
- Permission grant/revoke events
- Authorization denials
- Privileged changes affecting roles/permissions/users

Minimum audit fields:
- Actor ID
- Target entity and ID
- Permission key evaluated
- Decision (allow/deny)
- Timestamp
- Scope/tenant context

## 7. Test Strategy
- Unit tests for permission-key mapping and policy resolution
- Integration tests per endpoint for allow/deny behavior
- Negative tests for cross-tenant access
- Regression tests for privilege-escalation paths

## 8. Change Management
Any permission model update should include:
- Security review
- Updated policy map
- Backward-compatibility assessment (existing roles/migrations)
- Test updates and release notes
