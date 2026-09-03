---
paths:
  - 'app/{Models,Http/Controllers/Organizations,Http/Requests/Organizations}/**'
---

# Organizations

## Organization is a non-deletable user singleton
Each organization user may own at most one organization, enforced by the unique organizations.organization_user_id constraint. Organization management exposes read, store, and update only; do not add a delete endpoint without a new product decision.
