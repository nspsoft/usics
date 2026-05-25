# debug-production-dropdown-overlay.md
Status: [OPEN]

## Symptom
- Di production, dropdown/picklist (SearchableSelect) pada form Purchasing masih bisa ketutup/kehalang card Notes/Justification.
- Di local sudah normal (dropdown tampil di atas Notes).

## Expected
- Dropdown/picklist selalu tampil di atas card lain (termasuk Notes), tidak tertutup dan tetap bisa diklik.

## Scope
- Purchasing → Purchase Requests (Create)
- Purchasing → Purchase Orders (Create/Edit)
- Purchasing → Goods Receipts (Create/Edit)

## Hypotheses (falsifiable)
1) Production masih melayani asset lama (deploy belum menarik commit terbaru atau build belum menghasilkan bundle terbaru).
2) Service worker / PWA cache di production menahan bundle/CSS lama sehingga perubahan z-index belum terpakai.
3) Masalah bukan di card z-index, tetapi di dalam komponen dropdown (options list) yang punya z-index rendah atau ter-clipping oleh parent `overflow: hidden/auto`.
4) Ada stacking context tambahan di production (mis. karena `backdrop-filter`, `transform`, atau `position`) yang membuat `z-index` pada card tidak mempengaruhi layer dropdown.
5) CSS class Tailwind untuk `z-20/z-0` tidak ikut ter-generate di build production (content scan / build config), sehingga class ada di HTML tapi tidak ada efek.

## Evidence to collect
- Versi commit yang sedang aktif di server (`git rev-parse HEAD`) vs `origin/main`.
- Apakah DOM production sudah memuat class `relative z-20` pada card Items dan `z-0` pada Notes.
- Computed style: `z-index`, `position`, `overflow` pada card Items, wrapper dropdown, dan card Notes.
- Apakah service worker aktif dan menyajikan asset dari cache (workbox).

## Notes
- Jangan lakukan perubahan business logic. Fokus: verifikasi asset/DOM/CSS layering dan perbaiki styling minimal yang konsisten di semua menu.

