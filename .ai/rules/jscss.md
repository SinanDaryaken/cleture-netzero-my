---
paths:
  - 'resources/{js,css}/**'
---

# Jscss

## Build frontend changes immediately
After modifying frontend source files, run the production frontend build in the same turn without asking for confirmation. This is separate from tests; continue to follow any instruction not to run tests.

## MyCleture arayüzü açık tema kullanır
PrimeVue bileşenleri işletim sisteminin koyu tema tercihini otomatik izlememelidir. Cleture tasarım sistemi açık temadır; tema yapılandırmasında darkModeSelector kapalı tutulmalı ve diyalog, input, select gibi bileşenler açık yüzey tokenlarını kullanmalıdır.

## Durum mesajlarını sol üst toast olarak göster
AppLayout kullanan sayfalarda başarı durumlarını içerik içinde tam genişlikte StatusMessage bandıyla gösterme. PrimeVue ToastService üzerinden içerik alanının sol üstünde toast kullan; yıkıcı işlemler için kullanıcı onayı al.

## Durum mesajlarını sağ alt toast olarak göster
AppLayout kullanan sayfalarda başarı durumlarını içerik içinde tam genişlikte StatusMessage bandıyla gösterme. PrimeVue ToastService üzerinden ekranın sağ altında toast kullan; yıkıcı işlemler için kullanıcı onayı al. Önceki sol üst yerleşim kararı geçersizdir.
