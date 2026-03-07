# Roles Documentation for Developers

## 1. Overview
The Roles module implements Role-Based Access Control (RBAC) for a multi-organization system. Authorization decisions are based on:
- `role`
- `organization scope`
- `action/resource`

Current supported roles:
- `SUPER_ADMIN`
- `ORG_ADMIN`

## 2. Design Goals
- Enforce secure access boundaries
- Prevent cross-organization data access
- Keep permission logic centralized and testable
- Support future role expansion without breaking existing behavior

## 3. Role Model

### 3.1 SUPER_ADMIN
Scope: global

Capabilities:
- Full user lifecycle operations
- Role assignment/revocation across organizations
- Organization management
- Platform-level configuration
- Full data visibility

### 3.2 ORG_ADMIN
Scope: single organization (`organization_id`)

Capabilities:
- User management within assigned organization
- Role assignment within assigned organization policy
- Organization-scoped data operations

Restrictions:
- No cross-organization reads/writes
- No global configuration updates

## 4. Authorization Architecture
Recommended layered enforcement:
1. API gateway/controller guard validates identity and role presence.
2. Authorization middleware validates role permission for requested action.
3. Service layer enforces organization scope.
4. Data layer applies organization filters for read/write operations.

Security rule: never rely on frontend visibility for authorization.

## 5. Setup and Configuration

### 5.1 Required User Claims/Fields
- `user_id`
- `role`
- `organization_id` (required for organization-scoped roles)
- `status` (active/inactive)

### 5.2 Policy Mapping
Maintain a centralized permission map, for example:
- `SUPER_ADMIN -> *`
- `ORG_ADMIN -> org:*` with `organization_id` match required

### 5.3 Environment Assumptions
- AuthN is already in place (JWT/session)
- User identity is available in request context
- Data model supports organization ownership

## 6. API Authorization Contract
The exact route names may vary; apply these controls consistently.

### 6.1 User Management APIs
- `POST /users`: SUPER_ADMIN (global), ORG_ADMIN (same org only)
- `PATCH /users/{id}`: allowed if target user is in allowed scope
- `DELETE /users/{id}`: same scope rule

### 6.2 Role Assignment APIs
- `POST /users/{id}/role`
  - SUPER_ADMIN: any organization
  - ORG_ADMIN: only users in own organization

### 6.3 Organization Management APIs
- `POST /organizations`, `PATCH /organizations/{id}`: SUPER_ADMIN only
- Organization-scoped reads: ORG_ADMIN allowed for own org

## 7. Internal Authorization Flow
1. Authenticate request.
2. Resolve caller role and scope.
3. Map endpoint action to required permission.
4. Validate permission against role policy.
5. Validate organization boundary (`caller.organization_id == resource.organization_id`) where applicable.
6. Execute action or return authorization error.

Suggested error responses:
- `401 Unauthorized`: no/invalid authentication
- `403 Forbidden`: authenticated but insufficient permission/scope

## 8. Audit and Logging
Log security-relevant events:
- Role assignment and revocation
- Privileged SUPER_ADMIN operations
- Rejected authorization checks

Each audit log should include:
- actor id
- target id/resource
- action
- organization scope
- timestamp
- result (allowed/denied)

## 9. Testing Strategy

### 9.1 Unit Tests
- Role-to-permission matrix validation
- Deny-by-default behavior
- Scope checks for organization ownership

### 9.2 Integration Tests
- Endpoint authorization by role
- Cross-organization access denial
- Role assignment boundary checks

### 9.3 Regression Tests
- Privilege escalation prevention
- Backward compatibility for existing role behaviors

## 10. Extension Guidelines
When adding a new role:
1. Define explicit scope and allowed actions.
2. Add deny rules for sensitive/global actions.
3. Update policy map and API guard coverage.
4. Add unit/integration tests.
5. Update both user and developer documentation.

## 11. Operational Recommendations
- Periodically review memberships in high-privilege roles.
- Revalidate role/scope during organization transfers.
- Track permission model changes in release notes.