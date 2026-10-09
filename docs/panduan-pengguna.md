# Panduan Pengguna — Aplikasi Sales Order Gula

## Apa gunanya aplikasi ini?

Menggantikan pencatatan pesanan lewat chat dan spreadsheet:

- Sales langsung melihat **stok tersedia** saat membuat pesanan, jadi tidak menjanjikan barang yang tidak ada.
- Stok **langsung dipesan (direservasi)** begitu item disimpan, sehingga dua sales tidak bisa menjual stok yang sama.
- Persetujuan Supervisor punya **status yang jelas**.
- Semua perubahan **tercatat otomatis** (siapa, kapan, apa).

## Alur Pesanan

```
Draft  ──Submit──▶  Submitted  ──Approve──▶  Approved
(Sales Admin)       (menunggu)  ──Reject───▶  Rejected
```

| Status | Artinya |
|---|---|
| **Draft** | Pesanan sedang disusun. Masih bisa diubah atau dihapus. |
| **Submitted** | Sudah diajukan, menunggu Supervisor. Tidak bisa diubah lagi. |
| **Approved** | Disetujui. Stok tetap dipesan untuk pesanan ini. |
| **Rejected** | Ditolak. Stok dikembalikan. Pesanan tidak bisa diajukan ulang; buat pesanan baru. |

## Siapa Bisa Apa

| | Sales Admin | Supervisor | Warehouse |
|---|---|---|---|
| Buat & ubah pesanan | ✔ (miliknya) | – | – |
| Submit pesanan | ✔ (miliknya) | – | – |
| Approve / Reject | – | ✔ | – |
| Lihat pesanan | Miliknya saja | Semua | Yang **Approved** saja |
| Lihat riwayat (audit) | ✔ | ✔ | ✔ |

## Langkah demi Langkah

### Langkah 0 — Login

1. Buka alamat aplikasi, mis. `http://localhost:8000/admin`.
2. Masukkan email dan password.

Akun demo (password: `password`): `sales@gula.test`, `supervisor@gula.test`, `warehouse@gula.test`.

### Sales Admin

**Langkah 1 — Buat pesanan baru**
1. Menu **Sales Order** → tombol **New Sales Order**.
2. Pilih **Customer**, isi **Catatan** bila perlu.
3. Klik **Create**. Pesanan tersimpan sebagai **Draft** dengan nomor otomatis, mis. `SO-20261009-0001`.

**Langkah 2 — Tambah item**
1. Di halaman pesanan, bagian **Item Pesanan** → **Tambah Item**.
2. Pilih **Product**. Di samping nama product terlihat **stok tersedia**.
3. Isi **Jumlah**, klik **Create**.
4. Jika jumlah melebihi stok, muncul pesan *"Stok tidak cukup. Tersedia: … diminta: …"* dan item tidak disimpan.

> Satu product hanya boleh satu kali per pesanan. Untuk menambah jumlah, ubah item yang sudah ada.

**Langkah 3 — Ubah atau hapus item (opsional)**
- **Edit** pada baris item untuk mengubah jumlah; stok otomatis disesuaikan.
- **Delete** untuk menghapus item; stoknya dikembalikan.
- **Hapus Draft** (kanan atas) untuk membatalkan seluruh pesanan.

**Langkah 4 — Submit**
1. Klik **Submit** (kanan atas) → konfirmasi.
2. Status menjadi **Submitted**. Pesanan terkunci dan menunggu Supervisor.

> Pesanan harus punya minimal 1 item untuk bisa di-submit.

### Supervisor

**Langkah 5 — Cari pesanan yang menunggu**
1. Menu **Sales Order** → **Filter** → Status **Submitted**.
2. Klik **View** pada pesanan.

**Langkah 6 — Putuskan**
- **Approve**: catatan boleh dikosongkan. Status menjadi **Approved**.
- **Reject**: **catatan wajib diisi** (alasan penolakan). Status menjadi **Rejected** dan stok dikembalikan.

### Warehouse

**Langkah 7 — Lihat pesanan yang disetujui**
1. Menu **Sales Order**. Hanya pesanan **Approved** yang tampil.
2. Klik **View** untuk melihat customer dan daftar item yang perlu disiapkan.

### Semua Role

**Langkah 8 — Cari, filter, dan telusuri riwayat**
- Kotak **Search**: cari nomor pesanan atau nama customer.
- **Filter** status untuk menyaring daftar.
- Di halaman detail, bagian **Audit Trail** menunjukkan urutan kejadian: waktu, pengguna, aksi, nilai sebelum/sesudah, dan catatan. Riwayat ini tidak bisa diubah atau dihapus siapa pun.

## Skenario Demo (±5 menit)

1. Login **Sales Admin** → buat pesanan untuk *PT Distributor Gula Lampung*.
2. Tambah *Gula Kristal Rafinasi 50 kg* sebanyak **11** → ditolak (stok 10).
3. Ubah jadi **4** → berhasil; stok tersedia tinggal 6.
4. **Submit**.
5. Login **Supervisor** → filter *Submitted* → **Reject** dengan catatan → stok kembali 10.
6. Ulangi langkah 1–4, lalu **Approve**.
7. Login **Warehouse** → hanya pesanan *Approved* yang terlihat.
8. Buka **Audit Trail** → tunjukkan siapa melakukan apa dan kapan.

## Pesan yang Mungkin Muncul

| Pesan | Artinya / yang harus dilakukan |
|---|---|
| Stok tidak cukup. Tersedia: … | Kurangi jumlah sesuai stok tersedia. |
| Product sudah ada di pesanan ini | Edit jumlah pada item yang sudah ada. |
| Pesanan harus memiliki minimal satu item | Tambah item sebelum submit. |
| Pesanan berstatus … dan tidak dapat diubah | Pesanan sudah di-submit/diputuskan; buat pesanan baru bila perlu. |
| Catatan wajib diisi | Isi alasan saat Reject. |
| Anda tidak memiliki hak akses | Aksi ini bukan untuk role Anda. |

## Belum Termasuk di Versi Ini

Harga, diskon, pajak, pembayaran, pengiriman, notifikasi WhatsApp, dan aplikasi mobile.
