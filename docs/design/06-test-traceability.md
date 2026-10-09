# 06 — Test Scenario dan Matriks Keterlacakan

Dokumen ini disusun sebelum implementasi dan direvisi mengikuti Aturan Bisnis Minimum (ABM). Kolom **Hasil** diisi `BELUM DIJALANKAN` sampai test benar-benar dijalankan; nilai lain (LULUS/GAGAL) hanya boleh diisi dari hasil eksekusi nyata, tidak diisi dugaan.

## 1. Strategi

- Kerangka uji: PHPUnit 12 (sudah ada di proyek). Skenario BDD pada 03-user-stories.md diterjemahkan satu-ke-satu menjadi test Feature (HTTP, database) dan Unit (aturan status, perhitungan stok).
- ID test = ID skenario (`TS-` + ID skenario), misalnya SC-04-7 → TS-04-7.
- Nama file test di bawah adalah **usulan**; belum ada di repo.
- Uji konkurensi (TS-04-7, TS-05-5) harus dijalankan pada SQL Server, bukan SQLite, karena perilaku lock dan `rowversion` berbeda. `.env.example` saat ini default SQLite, sehingga perlu penyesuaian saat implementasi.
- Setiap test yang mengubah status harus memeriksa tiga hal: status pesanan, nilai `qty_on_hand`/`qty_reserved`, dan baris riwayat status (ABM-4, ABM-6).

## 2. Data Uji Dasar

| Data | Nilai |
|---|---|
| Pengguna | `sa1`, `sa2` (Sales Admin), `sv1` (Supervisor), `wh1` (Warehouse) |
| Customer | C-001 aktif, C-002 nonaktif |
| Product | A (`qty_on_hand` 10, `qty_reserved` 0, aktif), B (`qty_on_hand` 5, `qty_reserved` 0, aktif), C (nonaktif), D (`qty_on_hand` 0) |

## 3. Test Scenario per Story

| Story | Skenario → Test | Jenis | Usulan file test | Hasil |
|---|---|---|---|---|
| US-01 | TS-01-1..4 (SC-01-1..4) | Feature | `tests/Feature/SalesOrder/CreateDraftTest.php` | BELUM DIJALANKAN |
| US-02 | TS-02-1..9 (SC-02-1..9) | Feature + Unit (stok tersedia) | `tests/Feature/SalesOrder/AddItemTest.php`, `tests/Unit/StockAvailabilityTest.php` | BELUM DIJALANKAN |
| US-03 | TS-03-1..6 (SC-03-1..6) | Feature | `tests/Feature/SalesOrder/ChangeItemTest.php` | BELUM DIJALANKAN |
| US-04 | TS-04-1..8 (SC-04-1..8) | Feature (konkurensi di SQL Server) | `tests/Feature/SalesOrder/SubmitReservationTest.php` | BELUM DIJALANKAN |
| US-05 | TS-05-1..7 (SC-05-1..7) | Feature (konkurensi di SQL Server) | `tests/Feature/SalesOrder/ApproveTest.php` | BELUM DIJALANKAN |
| US-06 | TS-06-1..5 (SC-06-1..5) | Feature | `tests/Feature/SalesOrder/RejectTest.php` | BELUM DIJALANKAN |
| US-07 | TS-07-1..7 (SC-07-1..7) | Feature | `tests/Feature/SalesOrder/ListSearchVisibilityTest.php` | BELUM DIJALANKAN |
| US-08 | TS-08-1..6 (SC-08-1..6) | Feature | `tests/Feature/SalesOrder/HistoryAuditTest.php` | BELUM DIJALANKAN |
| Transisi | Semua kombinasi status × aksi tidak valid (TR-1..4 dan sisanya) | Unit | `tests/Unit/OrderStatusTransitionTest.php` | BELUM DIJALANKAN |

Jumlah skenario: 4 + 9 + 6 + 8 + 7 + 5 + 7 + 6 = 52 skenario BDD, ditambah satu tabel uji transisi.

Skenario konkurensi: **TS-04-7** dan **TS-05-5** (wajib di SQL Server).

## 4. Matriks Keterlacakan: Requirement → Implementasi → Test → Hasil

Kolom implementasi masih berupa **rencana** (tabel/endpoint/layar dari 04 dan 05). Kolom "Berkas implementasi" diisi saat kode ada.

| Req | Ringkasan | Story | Tabel | Endpoint | Layar | Test | Berkas implementasi | Hasil |
|---|---|---|---|---|---|---|---|---|
| FR-1 | Master customer & product dari data awal | US-01, US-02 | customers, products | E-02, E-03 | S-03 | TS-01-1, TS-01-2, TS-02-8 | — | BELUM DIJALANKAN |
| FR-2 | Buat sales order + item | US-01, US-02 | sales_orders, sales_order_items | E-04, E-08 | S-03 | TS-01-1, TS-02-1 | — | BELUM DIJALANKAN |
| FR-3 | Cek stok tersedia | US-02, US-03, US-04 | stocks | E-03, E-08, E-09, E-11 | S-03 | TS-02-2..4, TS-03-5, TS-04-6 | — | BELUM DIJALANKAN |
| FR-4 | Reservasi saat submit; approve kurangi stok fisik; reject lepas tanpa kurangi stok fisik | US-04..06 | stocks, stock_reservations | E-11..E-13 | S-03, S-05 | TS-04-1, TS-04-6..8, TS-05-1, TS-05-7, TS-06-1 | — | BELUM DIJALANKAN |
| FR-5 | Status draft/submitted/approved/rejected | US-04..06 | sales_orders | E-11..E-13 | S-03, S-05 | TS-04-1, TS-04-3, TS-05-3, TS-06-3, Unit transisi | — | BELUM DIJALANKAN |
| FR-6 | Approve/reject dengan catatan | US-05, US-06 | order_status_histories | E-12, E-13 | S-05 | TS-05-1..7, TS-06-1..5 | — | BELUM DIJALANKAN |
| FR-7 | Daftar, cari, filter status | US-07 | sales_orders | E-05 | S-02 | TS-07-1..3, TS-07-7 | — | BELUM DIJALANKAN |
| FR-8 | Riwayat dan audit trail | US-08 | order_status_histories, audit_logs | E-14, E-15 | S-04, S-06 | TS-08-1..6 | — | BELUM DIJALANKAN |
| FR-9 | Akses menurut role | US-01, US-04..07 | roles, users | semua | semua | TS-01-3, TS-04-5, TS-05-4, TS-06-5, TS-07-4..6 | — | BELUM DIJALANKAN |
| ABM-1 / BR-14 | Nomor pesanan unik dibuat sistem | US-01 | sales_orders (unik) | E-04 | S-03 | TS-01-1 | — | BELUM DIJALANKAN |
| ABM-2 / BR-01, BR-06 | Min. satu item, qty > 0 | US-02, US-04 | sales_order_items | E-08, E-09, E-11 | S-03 | TS-02-5, TS-04-2 | — | BELUM DIJALANKAN |
| ABM-3 / BR-02 | Stok tersedia = fisik − reservasi | US-02, US-03 | stocks | E-03, E-08, E-09 | S-03 | TS-02-1..4, TS-03-5 | — | BELUM DIJALANKAN |
| ABM-4 / BR-03, BR-09 | Reservasi submit; approve kurangi fisik + lepas; reject lepas | US-04..06 | stocks, stock_reservations | E-11..E-13 | — | TS-04-1, TS-04-6..8, TS-05-1, TS-05-5, TS-05-7, TS-06-1 | — | BELUM DIJALANKAN |
| ABM-5 / BR-07 | Hanya Supervisor approve/reject submitted | US-05, US-06 | order_status_histories | E-12, E-13 | S-05 | TS-05-3, TS-05-4, TS-06-3, TS-06-5 | — | BELUM DIJALANKAN |
| ABM-6 / BR-15 | Riwayat status: pengguna, waktu, awal, akhir, catatan | US-01, US-04..06, US-08 | order_status_histories | E-14 | S-04, S-06 | TS-01-1, TS-04-1, TS-05-1, TS-05-2, TS-06-1, TS-08-1, TS-08-2, TS-08-6 | — | BELUM DIJALANKAN |
| BR-04 | Product sekali per pesanan | US-02 | sales_order_items (unik) | E-08 | S-03 | TS-02-6 | — | BELUM DIJALANKAN |
| BR-05 | Hanya draft yang dapat diubah; draft tidak mengubah stok | US-03, US-04 | sales_orders | E-07..E-11 | S-03 | TS-03-1..4, TS-03-6, TS-04-4, TS-06-4 | — | BELUM DIJALANKAN |
| BR-08 | Catatan: reject wajib, maks. 1000 | US-05, US-06 | order_status_histories | E-12, E-13 | S-05 | TS-05-2, TS-05-6, TS-06-2 | — | BELUM DIJALANKAN |
| BR-10 | approved/rejected final | US-05, US-06 | sales_orders | E-07..E-13 | — | TS-05-3, TS-06-4, Unit transisi | — | BELUM DIJALANKAN |
| BR-11 | Audit append-only | US-08 | audit_logs | E-15 | S-06 | TS-08-1, TS-08-3, TS-08-4 | — | BELUM DIJALANKAN |
| BR-12 | Customer/product harus aktif | US-01, US-02 | customers, products | E-04, E-08 | S-03 | TS-01-2, TS-02-8 | — | BELUM DIJALANKAN |
| BR-13 | Visibilitas per role | US-07, US-08 | sales_orders | E-05, E-06, E-14, E-15 | S-02, S-04 | TS-07-4..6, TS-08-5 | — | BELUM DIJALANKAN |

Catatan keterlacakan: `ERR-xx` → test yang memicunya ada di skenario BDD (kolom Then). Tujuan 4 PRD (keterlacakan sampai test case) dipenuhi oleh matriks ini; kolom implementasi dan hasil menjadi bukti setelah pembangunan.

## 5. Kebutuhan yang Menunggu Keputusan Klien

Skenario berlabel **[USULAN]** (SC-02-6, SC-02-9, SC-05-2, SC-06-2, SC-06-4, SC-07-4..6, SC-08-4) harus ditinjau ulang bila klien menjawab OQ-1, OQ-3, OQ-4, OQ-9 berbeda dari usulan. Skenario pengecekan awal stok pada draft (SC-02-2..4, SC-03-5) bergantung pada usulan U-8 (sisa OQ-2). Daftar usulan: 01-process.md bagian 6.
