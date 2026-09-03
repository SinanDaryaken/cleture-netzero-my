---
paths:
  - 'app/{Actions,Http/Controllers}/IdentityAccess/**'
---

# Identity Access

## Keep identity mail tasks secret-free and immutable
Create identity mail processing tasks as payload version 2 with exactly organizationUserId and an allow-listed tr/en locale snapshot. Never include email, passwords, tokens, links, sessions, or other user data. If an active dedupe_key already exists, return it unchanged; never rewrite payload, version, status, timing, attempts, dispatch, claim, or lease fields.
