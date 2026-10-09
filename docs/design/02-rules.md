# 02 — Business Rule, Hak Akses, dan Pesan Kesalahan

Label **[ABM-n]** = Aturan Bisnis Minimum dari klien/pemberi tugas (keputusan; PRD bagian 5.1). Label **[USULAN]** = belum diputuskan klien (lihat 01-process.md bagian 6).

## 1. Aturan Bisnis Minimum (dari klien)

| ID | Aturan | Diterapkan di |
|---|---|---|
| ABM-1 | Nomor pesanan unik dan dibuat sistem. | BR-14 |
| ABM-2 | Pesanan minimal satu item dengan kuantitas > 0. | BR-01, BR-06 |
| ABM-3 | Stok tersedia = stok fisik − stok yang sedang direservasi. | BR-02, BR-03 |
| ABM-4 | `submitted`: stok direservasi. `approved`: stok fisik berkurang, reservasi dilepas. `rejected`: reservasi dilepas tanpa mengurangi stok fisik. | BR-03, BR-09 |
| ABM-5 | Hanya Supervisor yang dapat approve/reject pesanan `submitted`. | BR-07 |
| ABM-6 | Setiap perubahan status menyimpan pengguna, waktu, status awal, status akhir, catatan. | BR-15 |

## 2. Business Rule

| ID | Aturan | Sumber |
|---|---|---|
| BR-01 | Qty item harus > 0. | ABM-2 |
| BR-02 | Pengecekan awal: saat item ditambah atau diubah, qty ≤ stok tersedia (stok fisik − reservasi). Pengecekan ini tidak mereservasi dan tidak mengikat. | FR-3, ABM-3 |
| BR-03 | Saat submit, stok semua item direservasi secara atomik. Bila ada item yang stok tersedianya kurang, seluruh submit ditolak, status tetap `draft`, tidak ada reservasi parsial. Stok tersedia tidak pernah negatif walau ada submit bersamaan **[ASUMSI-2]**. | ABM-3, ABM-4 |
| BR-04 | Satu product hanya sekali per pesanan; ubah qty pada item yang ada. **[USULAN]** U-7 | OQ-9 |
| BR-05 | Hanya pesanan `draft` yang boleh diubah (item, customer, catatan) atau dihapus, dan hanya oleh Sales Admin pembuatnya. Draft tidak menahan stok sehingga perubahan atau penghapusan draft tidak mengubah stok. | FR-2, ABM-4, ASUMSI-3 |
| BR-06 | Submit membutuhkan minimal 1 item (qty > 0). | ABM-2 |
| BR-07 | Approve/reject hanya oleh Supervisor dan hanya dari status `submitted`. | ABM-5 |
| BR-08 | Reject wajib catatan; approve catatan opsional; maksimal 1000 karakter. **[USULAN]** U-3 | FR-6, OQ-4 |
| BR-09 | Approve: `qty_on_hand` berkurang sebesar qty tiap item dan reservasi dilepas. Reject: reservasi dilepas, `qty_on_hand` tetap. Dalam satu transaksi dengan perubahan status. | ABM-4 |
| BR-10 | `approved` dan `rejected` final; tidak ada transisi keluar. **[USULAN]** U-2 | OQ-3 |
| BR-11 | Perubahan item dan penghapusan draft menulis audit log. Audit append-only: tidak ada update/hapus lewat aplikasi. | FR-8 |
| BR-12 | Customer dan product harus aktif untuk dipakai pada pesanan baru. | FR-1 |
| BR-13 | Visibilitas: Sales Admin hanya pesanan miliknya; Supervisor semua; Warehouse hanya `approved`. **[USULAN]** U-1, U-5 | FR-9, OQ-1 |
| BR-14 | Nomor pesanan dibuat sistem, unik, tidak bisa diedit. Format `SO-yyyymmdd-nnnn` **[USULAN]** U-6 (hanya format). | ABM-1 |
| BR-15 | Setiap perubahan status (termasuk pembuatan: status awal kosong → `draft`) menulis satu baris riwayat status: pengguna, waktu, status awal, status akhir, catatan. Append-only. | ABM-6 |

## 3. Matriks Hak Akses (role × aksi)

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
| Lihat riwayat status & audit trail pesanan | M | Y | A |
| Ubah/hapus riwayat status atau audit log | — | — | — |

Akses ditolak dijawab 403. Pesanan di luar visibilitas dijawab 404 agar keberadaannya tidak bocor.

## 4. Katalog Pesan Kesalahan Utama

| Kode | HTTP | Pemicu | Pesan (Indonesia) |
|---|---|---|---|
| ERR-01 | 401 | Belum login / sesi habis | Silakan login terlebih dahulu. |
| ERR-02 | 403 | Role tidak berhak atas aksi | Anda tidak memiliki hak akses untuk aksi ini. |
| ERR-03 | 404 | Pesanan tidak ada / di luar visibilitas | Pesanan tidak ditemukan. |
| ERR-04 | 422 | Qty ≤ 0 atau bukan angka (BR-01) | Jumlah harus lebih dari 0. |
| ERR-05 | 422 | Qty > stok tersedia saat tambah/ubah item (BR-02), atau stok tidak cukup saat submit (BR-03) | Stok tidak cukup. Tersedia: {tersedia} {satuan}, diminta: {diminta}. Saat submit, respons memuat daftar semua item yang kurang. |
| ERR-06 | 422 | Product sudah ada di pesanan (BR-04) | Product sudah ada di pesanan ini. Ubah jumlah pada item yang ada. |
| ERR-07 | 409 | Transisi status tidak valid (BR-07, BR-10) | Aksi tidak dapat dilakukan pada pesanan berstatus {status}. |
| ERR-08 | 409 | Ubah/hapus pesanan bukan `draft` (BR-05) | Pesanan berstatus {status} dan tidak dapat diubah. |
| ERR-09 | 422 | Submit tanpa item (BR-06) | Pesanan harus memiliki minimal satu item. |
| ERR-10 | 422 | Reject tanpa catatan atau catatan > 1000 (BR-08) | Catatan wajib diisi (maksimal 1000 karakter). |
| ERR-11 | 422 | Customer/product nonaktif atau tidak ada (BR-12) | Customer atau product tidak tersedia. |
| ERR-12 | 409 | Konflik reservasi bersamaan tidak terselesaikan setelah retry (BR-03) | Stok berubah saat diproses. Muat ulang dan coba lagi. |
| ERR-13 | 405/403 | Upaya ubah atau hapus audit log atau riwayat status (BR-11, BR-15) | Riwayat tidak dapat diubah atau dihapus. |

Respons error JSON: `{ "code": "ERR-05", "message": "...", "errors": { "qty": ["..."] } }`.
