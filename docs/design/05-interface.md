# 05 — Perilaku Sequence, Rancangan API, dan Daftar Layar

Ini rancangan, belum implementasi. Diagram sequence ada di [07-diagrams.md](07-diagrams.md) bagian 5–7 (satu-satunya sumber); catatan perilaku ada di bagian 1 di bawah. Gaya autentikasi mengikuti OQ-10 dan pilihan frontend belum diputuskan; rancangan di bawah netral terhadap keduanya. Label **[ABM-n]** = keputusan klien; **[USULAN]** merujuk ke 01-process.md bagian 6. Kode ERR merujuk ke 02-rules.md.

## 1. Catatan Perilaku Sequence

| Diagram | Story | Perilaku kunci |
|---|---|---|
| 07 bagian 5: tambah item | US-02 | Pengecekan awal stok tersedia (stok fisik − reservasi). Tidak mereservasi dan tidak mengubah stok [ABM-4]. Gagal → ERR-05. |
| 07 bagian 6: submit | US-04 | Satu transaksi: kunci pesanan (`UPDLOCK`), reservasi tiap item dengan UPDATE bersyarat `(qty_on_hand − qty_reserved) >= qty`. Satu item gagal → rollback seluruhnya, ERR-05 memuat semua item yang kurang, status tetap `draft`. Berhasil → buat reservasi, status `submitted`, tulis riwayat status. |
| 07 bagian 7: approve/reject | US-05, US-06 | Satu transaksi dengan kunci pesanan. Approve: `qty_on_hand` dan `qty_reserved` berkurang, reservasi `released`. Reject: hanya `qty_reserved` berkurang, `qty_on_hand` tetap. Keduanya menulis riwayat status. |

Atomisitas UPDATE bersyarat memastikan dua submit bersamaan tidak sama-sama lolos (BR-03, SC-04-7). Kunci pesanan dan keputusan satu kali memastikan stok fisik tidak berkurang dua kali pada approve ganda (SC-05-5).

## 2. Rancangan API

Prefiks `/api`, JSON. Semua endpoint membutuhkan login (ERR-01) kecuali login. Role: SA = Sales Admin, SV = Supervisor, WH = Warehouse. Aturan visibilitas BR-13 diterapkan pada setiap pembacaan pesanan.

| ID | Metode & path | Role | Fungsi | Sukses | Error utama | Story |
|---|---|---|---|---|---|---|
| E-01 | POST `/login`, POST `/logout` | semua | Masuk/keluar (mekanisme: OQ-10) | 200 | 401/422 | — |
| E-02 | GET `/customers` | SA | Daftar customer aktif untuk form | 200 | ERR-02 | US-01 |
| E-03 | GET `/products` | SA, SV | Product aktif + stok fisik, reservasi, stok tersedia | 200 | ERR-02 | US-02 |
| E-04 | POST `/sales-orders` `{customer_id, note?}` | SA | Buat draft | 201 | ERR-02, ERR-11 | US-01 |
| E-05 | GET `/sales-orders?status=&q=&page=` | SA, SV, WH | Daftar, cari, filter | 200 | 422 filter tidak valid | US-07 |
| E-06 | GET `/sales-orders/{id}` | SA, SV, WH | Detail + item | 200 | ERR-03 | US-07 |
| E-07 | DELETE `/sales-orders/{id}` | SA | Hapus draft (stok tidak berubah) | 204 | ERR-03, ERR-08 | US-03 |
| E-08 | POST `/sales-orders/{id}/items` `{product_id, qty}` | SA | Tambah item (pengecekan awal, tanpa reservasi) | 201 | ERR-03/04/05/06/08/11 | US-02 |
| E-09 | PATCH `/sales-orders/{id}/items/{item}` `{qty}` | SA | Ubah qty (pengecekan awal) | 200 | ERR-03/04/05/08 | US-03 |
| E-10 | DELETE `/sales-orders/{id}/items/{item}` | SA | Hapus item | 204 | ERR-03, ERR-08 | US-03 |
| E-11 | POST `/sales-orders/{id}/submit` | SA | Submit + reservasi stok atomik | 200 | ERR-03/05/07/09/12 | US-04 |
| E-12 | POST `/sales-orders/{id}/approve` `{note?}` | SV | Approve; stok fisik berkurang, reservasi dilepas | 200 | ERR-02/07/10 | US-05 |
| E-13 | POST `/sales-orders/{id}/reject` `{note}` | SV | Reject; reservasi dilepas, stok fisik tetap | 200 | ERR-02/07/10 | US-06 |
| E-14 | GET `/sales-orders/{id}/history` | SA, SV, WH | Riwayat status (pengguna, waktu, awal, akhir, catatan) | 200 | ERR-03 | US-08 |
| E-15 | GET `/sales-orders/{id}/audit` | SA, SV, WH | Audit trail perubahan item dan draft | 200 | ERR-03 | US-08 |

Tidak ada endpoint PUT/PATCH/DELETE untuk riwayat status maupun audit log; permintaan semacam itu dijawab 405 (ERR-13) (SC-08-3).

Bentuk respons error: lihat 02-rules.md bagian 4. Pagination daftar: ukuran halaman default dan batas ditetapkan saat implementasi.

## 3. Daftar Layar

| ID | Layar | Role | Isi utama | Endpoint | Story |
|---|---|---|---|---|---|
| S-01 | Login | semua | Form login | E-01 | — |
| S-02 | Daftar Pesanan | SA, SV, WH | Tabel, pencarian, filter status, pagination, keadaan kosong | E-05 | US-07 |
| S-03 | Form Pesanan (draft) | SA | Pilih customer, tabel item, tambah/ubah/hapus item, tampil stok tersedia (informasi, belum direservasi), tombol Submit, tombol Hapus draft | E-02, E-03, E-04, E-07..E-11 | US-01..04 |
| S-04 | Detail Pesanan | SA, SV, WH | Header, item, status, catatan keputusan, tab Riwayat | E-06, E-14, E-15 | US-07, US-08 |
| S-05 | Antrian Approval | SV | Daftar `submitted`, tombol Approve/Reject dengan kolom catatan | E-05, E-12, E-13 | US-05, US-06 |
| S-06 | Riwayat Status dan Audit | SA, SV, WH | Linimasa status (awal → akhir, pengguna, waktu, catatan) dan perubahan item | E-14, E-15 | US-08 |

Tombol aksi disembunyikan sesuai role dan status, tetapi kontrol akses tetap ditegakkan di server (BR-05, BR-07).
