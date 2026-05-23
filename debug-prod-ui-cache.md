# Debug Session: prod-ui-cache [OPEN]

## Symptom
- Production deploy selesai, tetapi UI `Goods Receipts` masih tampil versi lama.
- Halaman target: `purchasing/receipts/create`.

## Hypotheses
- H1: Browser masih memakai service worker / PWA cache lama.
- H2: HTML production masih merujuk asset bundle lama dari `manifest.json`.
- H3: Bundle JS terbaru sudah ter-build, tetapi request asset di browser gagal/tertahan cache.
- H4: Production route merender halaman lama karena source file di server belum benar-benar sinkron.

## Evidence Plan
- Periksa HTML hasil render production dan asset bundle yang dirujuk.
- Periksa service worker, cache storage, dan request JS di browser.
- Cocokkan apakah bundle yang dimuat browser memuat `SearchableSelect` untuk form `Goods Receipts`.

## Status
- Waiting for runtime evidence collection.

## Evidence Collected
- Production page loads `Create Goods Receipt - ERP JICOS` successfully.
- Production network loads `build/assets/Create-DM_5tdPu.js` and `build/assets/SearchableSelect-DItr7zx4.js`.
- Browser runtime shows active service worker scope `https://jicos.jidoka.co.id/build/` and cache key `workbox-precache-v2-https://jicos.jidoka.co.id/build/`.
- DOM inspection on production page still finds native `<select>` for `Purchase Order` and `Supplier`.
- Fetched production `Create-DM_5tdPu.js` still contains native `<select>` rendering for `Purchase Order` and `Supplier`, and only uses `SearchableSelect` for product rows.
- Local git status still shows modified but uncommitted files:
  - `resources/js/Pages/Purchasing/Receipts/Create.vue`
  - `deploy.sh`

## Hypothesis Status
- H1: Possible contributing factor, but not root cause based on active bundle contents.
- H2: Confirmed. Production is serving a `Create` bundle built from old source.
- H3: Rejected. Assets load with `200` and bundle executes.
- H4: Confirmed in practice via git evidence: latest local source has not been committed/pushed, so production cannot fetch it from `origin/main`.

## Conclusion
- Root cause: production deploy succeeds, but it deploys the current `origin/main`, while the latest local changes for `Goods Receipts` and `deploy.sh` are still uncommitted locally.
