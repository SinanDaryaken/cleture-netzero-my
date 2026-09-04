---
paths:
  - 'app/{Models,Http/Controllers,Http/Requests}/Tenant/**'
---

# Tenant

## Birim türünü yapısal rolden ayrı tut
organization_unit_types tenant tarafından yönetilen etiketlerdir; organizational_units.organization_unit_type_id nullable kalır. Şirket/Tesis yapısal rolleri mark_as_company ve mark_as_facility alanlarıyla ayrı tutulur; ikisi de false ise rol Standarttır. Yeni atamalarda yalnız aktif tür seçilebilir, mevcut pasif atama düzenleme sırasında korunabilir.
