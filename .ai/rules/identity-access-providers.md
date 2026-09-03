---
paths:
  - 'app/{Http/Controllers/IdentityAccess,Providers}/**'
---

# Identity Access Providers

## Keep password recovery enumeration-safe
Forgot-password and reset-password endpoints must use named rate limiters keyed by both IP and a SHA-256 hash of the normalized email. Forgot-password responses must have a constant minimum duration and never reveal account existence; reset failures must use one generic localized message. Never place raw emails, reset tokens, or reset URLs in cache keys or logs.
