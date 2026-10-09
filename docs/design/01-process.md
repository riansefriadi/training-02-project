# 01 — Aktor, Alur Proses, dan Status

Turunan dari [PRD](../prd/PRD_Sales_Order_MVP.md). Label **[ABM-n]** = Aturan Bisnis Minimum dari klien/pemberi tugas (lihat PRD bagian 5.1), bersifat keputusan. Label **[USULAN]** = usulan analis yang belum diputuskan klien dan harus dikonfirmasi (lihat daftar di bagian 6). Diagram ada di [07-diagrams.md](07-diagrams.md) (satu-satunya sumber diagram).

## 1. Aktor

| Aktor | Peran di aplikasi | Sumber |
|---|---|---|
| Sales Admin | Membuat dan mengubah draft pesanan, mengecek stok, men-submit. | Transkrip ("Sales"), disebut Sales Admin pada permintaan analisis |
| Supervisor | Menyetujui atau menolak pesanan `submitted` dengan catatan; melihat semua pesanan dan audit. | Transkrip |
| Warehouse | Melihat pesanan yang sudah `approved` agar tahu pesanan sebelum dokumen dikirim. **[USULAN]** hanya baca (OQ-1). | Transkrip (masalah: "baru mengetahui pesanan setelah dokumen dikirim") |

Di luar aktor aplikasi: System Analyst dan Application Developer (peran proyek).

## 2. Status Pesanan

| Status | Arti | Dapat diubah? |
|---|---|---|
| `draft` | Pesanan sedang disusun oleh Sales Admin; **belum menahan stok** [ABM-4]. | Ya, oleh Sales Admin pembuat |
| `submitted` | Diajukan, stok semua item **direservasi**, menunggu keputusan Supervisor [ABM-4]. | Tidak (terkunci) **[ASUMSI-3]** |
| `approved` | Disetujui Supervisor; **stok fisik berkurang dan reservasi dilepas** [ABM-4]. | Tidak (final) |
| `rejected` | Ditolak Supervisor dengan catatan; **reservasi dilepas tanpa mengurangi stok fisik** [ABM-4]. | Tidak (final) **[USULAN]**, OQ-3 |

Stok tersedia = stok fisik − stok yang sedang direservasi [ABM-3].

## 3. Transisi Status

Diagram: [07-diagrams.md](07-diagrams.md) bagian 2.

| ID | Dari | Ke | Aktor | Syarat | Efek stok | Dicatat di audit |
|---|---|---|---|---|---|---|
| TR-1 | (baru) | draft | Sales Admin | Customer aktif | — | `created` |
| TR-2 | draft | submitted | Sales Admin pemilik | ≥ 1 item (qty > 0); stok tersedia cukup untuk semua item | Reservasi semua item (atomik; gagal sebagian → batal seluruhnya, tetap `draft`) | `submitted` |
| TR-3 | submitted | approved | Supervisor | Catatan opsional **[USULAN]** OQ-4 | Stok fisik berkurang, reservasi dilepas | `approved` |
| TR-4 | submitted | rejected | Supervisor | Catatan wajib **[USULAN]** OQ-4 | Reservasi dilepas, stok fisik tetap | `rejected` |

Setiap transisi (termasuk TR-1, dengan status awal kosong) menyimpan satu baris riwayat status: pengguna, waktu, status awal, status akhir, catatan [ABM-6]. Kolom "Dicatat di audit" di atas menunjukkan nama aksi.

Semua kombinasi lain ditolak (ERR-07). Tidak ada tarik-kembali `submitted` dan tidak ada edit ulang `rejected` **[USULAN]** (OQ-3): pesanan baru harus dibuat.

## 4. Alur Proses Usulan

Diagram: [07-diagrams.md](07-diagrams.md) bagian 3.

Ringkas: Sales Admin membuat draft dan menambah item (pengecekan stok awal, tanpa reservasi) → submit mereservasi stok semua item secara atomik → Supervisor approve (stok fisik berkurang, reservasi dilepas) atau reject (reservasi dilepas, stok fisik tetap) → Warehouse melihat pesanan `approved`.

Setiap perubahan status menulis satu baris riwayat status; perubahan item dan penghapusan draft menulis audit log (siapa, kapan, aksi, nilai lama/baru).

## 5. Di Luar Alur (tidak dirancang)

Harga/diskon/pajak (OQ-6), tindak lanjut pengiriman (OQ-11), kedaluwarsa draft (tidak terkait stok karena draft tidak mereservasi), deteksi pesanan ganda selain nomor unik (OQ-9), pembayaran, integrasi eksternal, notifikasi WhatsApp.

## 6. Usulan yang Perlu Dikonfirmasi Klien

| ID | Usulan | Terkait |
|---|---|---|
| U-1 | Warehouse hanya baca, dan hanya melihat pesanan `approved`. | OQ-1 |
| U-2 | `rejected` final; harus buat pesanan baru. | OQ-3 |
| U-3 | Catatan wajib saat reject, opsional saat approve; maksimal 1000 karakter. | OQ-4 |
| ~~U-4~~ | ~~Reservasi saat item disimpan pada `draft`.~~ **Gugur**: digantikan ABM-4 (reservasi saat `submitted`). | OQ-2 terjawab |
| U-5 | Sales Admin hanya melihat dan mengubah pesanan miliknya. | OQ-1 |
| U-6 | Format nomor pesanan `SO-yyyymmdd-nnnn` (sistem membuat nomor unik sudah diputuskan, ABM-1). | OQ-6 |
| U-8 | Pengecekan stok awal (tidak mengikat) dilakukan saat item draft ditambah/diubah, selain pengecekan mengikat saat submit. | OQ-2 (sisa) |
| U-7 | Satu product muncul sekali per pesanan. | OQ-9 |
