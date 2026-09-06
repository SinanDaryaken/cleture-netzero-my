---
paths:
  - 'app/{Actions,Models,Http/Controllers,Http/Requests}/Products/**,app/Models/{Product,Currency,OrganizationEntitlement}.php,routes/web.php,resources/js/modules/products/**,tests/Feature/Products/**'
---

# Feature Products

## Ödemesiz ürün satın alma entitlement sözleşmesi
Satın alma yalnız active=true, purchasable=true, included_by_default=false, fiyatı tanımlı ve aktif/silinmemiş para birimine bağlı ürünlerde yapılır. Organizasyon auth kullanıcısından türetilir; işlem organizasyon kilidi altında active/purchase entitlement oluşturur, starts_at=now ve ends_at/source_reference=null kalır, ödeme kaydı üretilmez. Aynı organizasyonda herhangi bir entitlement durumu yeniden satın almayı engeller. Products fiyat migration'ı Admin-owned'dır ve MyCleture kodundan önce uygulanmalıdır.

## Yıllık edinmeye geçişte eski satın alma kapalı
Eski ödemesiz ve süresiz entitlement oluşturan satın alma sözleşmesi geçersizdir. Yeni merkezi alım şeması, ödeme doğrulaması ve açık tarih/geçiş kararları kesinleşip yıllık akış uygulanana kadar PurchaseProduct kullanılabilir üründe lokalize validation hatası verir, hak yazmaz; UI edinmeyi kapalı gösterir. included_by_default filtre/cast tüketilmez. Yeni yıllık akış henüz uygulanmadı; bu geçiş korumasını tamamlanmış satın alma kabul etmeyin.

## Ürün tarihleri, gerçek işlem geçmişi ve ödeme öncesi özet
Ürünlerim başlangıç/bitiş tarihlerini UTC etiketiyle gösterir; eksik eski tarihlere bitiş uydurulmaz. Geçmiş işlemler Admin-owned organization_product_purchases tablosundan yalnız auth kullanıcısının organizasyonu için purchased_at/id azalan, 10'lu historyPage sayfalamasıyla okunur; fiyat/para birimi snapshot'ı kullanılır. Eski entitlement kayıtları ödeme geçmişine dönüştürülmez. Owner isteğiyle önceki kapalı düğme yerine satın alma özeti/ödemeye devam et UI açıldı; sağlayıcı henüz bağlı olmadığından POST hâlâ lokalize hata verir, ödeme veya hak oluşturmaz. Merkezi migration My'de oluşturulmaz.
