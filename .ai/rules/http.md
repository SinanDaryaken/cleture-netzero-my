---
paths:
  - 'app/Http/**'
---

# Http

## MyCleture tenant yönetiminde rol kontrolü yoktur
Tenant içi yönetim uçları rol/izin kontrolü yapmaz. Giriş yapmış ve doğrulanmış OrganizationUser, bağlı tenant provisioning_status=ready ve active=true ise erişebilir; tenant kimliği her zaman kullanıcı organizasyonundan sunucu tarafında türetilir.
