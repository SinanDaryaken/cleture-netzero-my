---
paths:
  - 'app/Actions/Organizations/CreateOrganization.php,app/Models/OrganizationEntitlement.php,tests/Feature/Organizations/**'
---

# Feature Organizations

## Varsayılan ürün entitlement ataması
Entitlement organization_id gerektirdiği için varsayılan ürün ataması hesap kaydında değil organizasyon ilk oluşturulurken yapılır. Organizasyon ve active=true, included_by_default=true ürünlerin active/default entitlement kayıtları aynı merkezi veritabanı transaction'ında oluşturulur; satın alınabilirlik varsayılan atamayı sınırlamaz.

## Varsayılan ürün ataması kaldırıldı
Owner kararıyla included_by_default özelliği kaldırılır. Organizasyon oluşturma ücretsiz ürünler dahil hiçbir ürünü otomatik atamaz; eski Varsayılan ürün entitlement ataması kuralı geçersizdir. Mevcut default/süresiz/suspended kayıtlar geçiş kararı olmadan değiştirilmez. Merkezi migration sahibi NetZeroAdmin'dir.
