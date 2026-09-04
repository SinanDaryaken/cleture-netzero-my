---
paths:
  - 'app/{Models,Actions,Http/Controllers,Http/Requests}/**,routes/web.php,resources/js/pages/organizations/**'
---

# Pages Organizations

## Provision NetZero from the organization
Organization is the commercial owner of at most one technical tenant; the obsolete Customer registry is not used. MyCleture is the sole tenant.provision producer: atomically set netzero_requested, create a pending inactive tenant, and insert one secret-free task keyed tenant:{tenantId}:provision. Worker owns provisioning_status/first active transition; availability always requires ready plus active. Do not use Redis or a dirty flag.
