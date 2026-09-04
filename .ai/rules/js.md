---
paths:
  - 'resources/js/**/*.vue'
---

# Js

## Poll canonical tenant availability during provisioning
Use Inertia usePoll only while tenant provisioning is pending or provisioning, request only the smallest relevant prop, use non-overlapping rest mode, and stop at ready or failed. Tenant availability is ready plus active. Future menu visibility may consume this value for UX, but server-side route authorization remains mandatory.
