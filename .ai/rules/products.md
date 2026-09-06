---
paths:
  - 'app/{Actions,Models,Http/Controllers}/Products/**,app/Models/Product.php,resources/js/modules/products/**'
---

# Products

## Eklenebilir ürün kataloğu filtresi
Organizasyon ürün kataloğu yalnız active=true, purchasable=true ve included_by_default=false ürünleri gösterir. Organizasyon için herhangi bir organization_entitlements kaydı bulunan ürün katalogdan çıkarılır; yeniden etkinleştirme/yenileme ayrı bir yönetim akışıdır.

## Katalog default alanından bağımsızdır
Önceki katalog kuralının included_by_default koşulu geçersizdir; alan okunmaz. Geçiş sırasında aktif, purchasable, fiyatı ve aktif/silinmemiş para birimi tanımlı ürünler listelenir; sahip olunanlar ayrı gösterilir. Her entitlement varlığında yeniden alımı dışlayan mevcut davranış yıllık akış tamamlanırken değiştirilecektir. Katalog görünürlüğü edinmenin açık olduğunu göstermez.
