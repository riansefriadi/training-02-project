# 02 — Business Rule, Hak Akses, dan Pesan Kesalahan

Label **[USULAN]** = belum diputuskan klien (lihat 01-process.md bagian 6).

## 1. Business Rule

| ID | Aturan | Sumber |
|---|---|---|
| BR-01 | Qty item harus > 0. | FR-3 |
| BR-02 | Qty item ≤ stok tersedia saat disimpan. Stok tersedia = `qty_on_hand` − `qty_reserved` **[ASUMSI-6]**. | FR-3, Tujuan 2 |
| BR-03 | Reservasi dan pelepasan stok atomik; stok tersedia tidak pernah negatif walau ada permintaan bersamaan **[ASUMSI-2]**. | FR-4 |
| BR-04 | Satu product hanya sekali per pesanan; ubah qty pada item yang ada. **[USULAN]** U-7 | OQ-9 |
| BR-05 | Hanya pesanan `draft` yang boleh diubah (item, customer, catatan) atau dihapus, dan hanya oleh Sales Admin pembuatnya. | FR-2, ASUMSI-3 |
| BR-06 | Submit membutuhkan minimal 1 item. | FR-2 |
| BR-07 | Approve/reject hanya oleh Supervisor dan hanya dari status `submitted`. | FR-6, FR-9 |
| BR-08 | Reject wajib catatan; approve catatan opsional; maksimal 1000 karakter. **[USULAN]** U-3 | FR-6, OQ-4 |
| BR-09 | Approve mempertahankan reservasi; reject dan hapus draft melepaskan reservasi. | ASUMSI-4 |
| BR-10 | `approved` dan `rejected` final; tidak ada transisi keluar. **[USULAN]** U-2 | OQ-3 |
| BR-11 | Setiap pembuatan, perubahan item, transisi status, dan keputusan menulis audit log. Audit append-only: tidak ada update/hapus lewat aplikasi. | FR-8 |
| BR-12 | Customer dan product harus aktif untuk dipakai pada pesanan baru. | FR-1 |
| BR-13 | Visibilitas: Sales Admin hanya pesanan miliknya; Supervisor semua; Warehouse hanya `approved`. **[USULAN]** U-1, U-5 | FR-9, OQ-1 |
| BR-14 | Nomor pesanan dibuat sistem, unik, tidak bisa diedit. **[USULAN]** U-6 | OQ-6 |

## 2. Matriks Hak Akses (role × aksi)

Y = boleh, — = tidak boleh, M = hanya miliknya, A = hanya status `approved`.

| Aksi | Sales Admin | Supervisor | Warehouse |
|---|---|---|---|
| Login | Y | Y | Y |
| Buat pesanan draft | Y | — | — |
| Ubah/hapus draft, tambah/ubah/hapus item | M | — | — |
| Lihat stok tersedia saat menyusun pesanan | Y | Y | — |
| Submit | M | — | — |
| Approve / reject | — | Y | — |
| Lihat daftar & detail pesanan | M | Y | A |
| Cari & filter status | M | Y | A |
| Lihat audit trail pesanan | M | Y | A |
| Ubah/hapus audit log | — | — | — |

Akses ditolak dijawab 403. Pesanan di luar visibilitas dijawab 404 agar keberadaannya tidak bocor.

## 3. Katalog Pesan Kesalahan Utama

| Kode | HTTP | Pemicu | Pesan (Indonesia) |
|---|---|---|---|
| ERR-01 | 401 | Belum login / sesi habis | Silakan login terlebih dahulu. |
| ERR-02 | 403 | Role tidak berhak atas aksi | Anda tidak memiliki hak akses untuk aksi ini. |
| ERR-03 | 404 | Pesanan tidak ada / di luar visibilitas | Pesanan tidak ditemukan. |
| ERR-04 | 422 | Qty ≤ 0 atau bukan angka (BR-01) | Jumlah harus lebih dari 0. |
| ERR-05 | 422 | Qty > stok tersedia (BR-02) | Stok tidak cukup. Tersedia: {tersedia} {satuan}, diminta: {diminta}. |
| ERR-06 | 422 | Product sudah ada di pesanan (BR-04) | Product sudah ada di pesanan ini. Ubah jumlah pada item yang ada. |
| ERR-07 | 409 | Transisi status tidak valid (BR-07, BR-10) | Aksi tidak dapat dilakukan pada pesanan berstatus {status}. |
| ERR-08 | 409 | Ubah/hapus pesanan bukan `draft` (BR-05) | Pesanan berstatus {status} dan tidak dapat diubah. |
| ERR-09 | 422 | Submit tanpa item (BR-06) | Pesanan harus memiliki minimal satu item. |
| ERR-10 | 422 | Reject tanpa catatan atau catatan > 1000 (BR-08) | Catatan wajib diisi (maksimal 1000 karakter). |
| ERR-11 | 422 | Customer/product nonaktif atau tidak ada (BR-12) | Customer atau product tidak tersedia. |
| ERR-12 | 409 | Konflik reservasi bersamaan gagal setelah retry (BR-03) | Stok berubah saat diproses. Muat ulang dan coba lagi. |
| ERR-13 | 405/403 | Upaya ubah atau hapus audit log (BR-11) | Audit log tidak dapat diubah atau dihapus. |

Respons error JSON: `{ "code": "ERR-05", "message": "...", "errors": { "qty": ["..."] } }`.
