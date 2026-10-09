# 07 — Kumpulan Diagram (Mermaid)

Semua diagram desain dalam satu berkas, sebagai teks Mermaid. Dapat ditempel ke mermaid.live atau dirender langsung oleh GitHub, VS Code, dan Obsidian. Berkas ini adalah satu-satunya sumber diagram; dokumen 01, 04, 05 merujuk ke sini. Direvisi mengikuti Aturan Bisnis Minimum (ABM).

Label **[ABM-n]** = keputusan klien; **[USULAN]** = belum diputuskan klien (01-process.md bagian 6).

## 1. Aktor dan Hak Akses (ikhtisar)

Sumber: 02-rules.md bagian 3 (matriks hak akses).

```mermaid
flowchart LR
    SA([Sales Admin])
    SV([Supervisor])
    WH([Warehouse])

    subgraph Aplikasi Sales Order
        UC1[Buat draft pesanan]
        UC2[Tambah/ubah/hapus item<br/>pengecekan stok]
        UC3[Submit pesanan<br/>reservasi stok]
        UC4[Approve / reject dengan catatan]
        UC5[Lihat daftar, cari, filter]
        UC6[Lihat riwayat status dan audit]
    end

    SA --> UC1
    SA --> UC2
    SA --> UC3
    SA -- "milik sendiri [USULAN]" --> UC5
    SA -- "milik sendiri [USULAN]" --> UC6
    SV --> UC4
    SV -- "semua pesanan" --> UC5
    SV -- "semua pesanan" --> UC6
    WH -- "hanya approved [USULAN]" --> UC5
    WH -- "hanya approved [USULAN]" --> UC6
```

## 2. Diagram Status Pesanan dan Efek Stok

Sumber: 01-process.md bagian 3. Efek stok mengikuti ABM-4.

```mermaid
stateDiagram-v2
    [*] --> draft: Sales Admin membuat pesanan
    draft --> submitted: Submit (min. 1 item)<br/>stok direservasi
    submitted --> approved: Supervisor approve<br/>stok fisik berkurang, reservasi dilepas
    submitted --> rejected: Supervisor reject (catatan wajib)<br/>reservasi dilepas, stok fisik tetap
    approved --> [*]
    rejected --> [*]
```

## 3. Alur Proses Usulan

Sumber: 01-process.md bagian 4.

```mermaid
flowchart TD
    A([Mulai]) --> B[Sales Admin pilih customer, buat draft]
    B --> C[Tambah item: product + qty]
    C --> D{Qty <= stok tersedia?<br/>pengecekan awal, tanpa reservasi}
    D -- Tidak --> E[Tolak: stok tidak cukup, tampilkan stok tersedia] --> C
    D -- Ya --> F[Simpan item, stok tidak berubah]
    F --> G{Tambah/ubah item lagi?}
    G -- Ya --> C
    G -- Tidak --> H{Minimal 1 item?}
    H -- Tidak --> I[Tolak submit: pesanan kosong] --> C
    H -- Ya --> R{Semua item cukup stok?<br/>cek dan reservasi atomik}
    R -- Tidak --> S[Tolak submit seluruhnya, status tetap draft] --> C
    R -- Ya --> J[Status submitted, stok direservasi, pesanan terkunci]
    J --> K[Supervisor tinjau]
    K --> L{Keputusan}
    L -- Approve --> M[Status approved, stok fisik berkurang, reservasi dilepas]
    L -- Reject + catatan --> N[Status rejected, reservasi dilepas, stok fisik tetap]
    M --> O[Warehouse melihat pesanan approved]
    M --> P([Selesai])
    N --> P
    O --> P
```

Setiap perubahan status menulis satu baris riwayat status [ABM-6]; perubahan item dan penghapusan draft menulis audit log.

## 4. ERD

Sumber: [../schema.dbml](../schema.dbml) dan 04-data.md.

```mermaid
erDiagram
    roles ||--o{ users : "memiliki"
    users ||--o{ sales_orders : "membuat (created_by)"
    customers ||--o{ sales_orders : "dipesan oleh"
    sales_orders ||--|{ sales_order_items : "berisi"
    products ||--o{ sales_order_items : "dipesan"
    products ||--|| stocks : "punya stok"
    sales_order_items ||--o| stock_reservations : "direservasi saat submit"
    products ||--o{ stock_reservations : "ditahan"
    sales_orders ||--|{ order_status_histories : "riwayat status"
    users ||--o{ order_status_histories : "mengubah status"
    sales_orders ||--o{ audit_logs : "dicatat"
    users ||--o{ audit_logs : "pelaku"
```

## 5. Sequence: Menambah Item dengan Pengecekan Stok (US-02)

Sumber: 05-interface.md. Pengecekan ini tidak mereservasi [ABM-4].

```mermaid
sequenceDiagram
    actor SA as Sales Admin
    participant API as OrderController
    participant SVC as OrderService
    participant DB as SQL Server
    SA->>API: POST /sales-orders/{id}/items {product_id, qty}
    API->>SVC: addItem(order, product, qty, user)
    SVC->>DB: cek order draft + milik user
    alt bukan draft / bukan milik
        SVC-->>API: ERR-08 / ERR-03
    else
        SVC->>DB: baca stok tersedia = qty_on_hand - qty_reserved
        alt qty > tersedia
            SVC-->>API: ERR-05 (sertakan stok tersedia)
            API-->>SA: 422
        else qty <= tersedia
            SVC->>DB: BEGIN; INSERT sales_order_items
            SVC->>DB: INSERT audit_logs (item_added); COMMIT
            API-->>SA: 201 item (stok tidak berubah)
        end
    end
```

## 6. Sequence: Submit dan Reservasi Stok (US-04)

Sumber: 05-interface.md. Reservasi atomik untuk semua item [ABM-4].

```mermaid
sequenceDiagram
    actor SA as Sales Admin
    participant API as OrderController
    participant SVC as OrderService
    participant DB as SQL Server
    SA->>API: POST /sales-orders/{id}/submit
    API->>SVC: submit(order, user)
    SVC->>DB: BEGIN; SELECT order WITH (UPDLOCK)
    alt status bukan draft
        SVC-->>API: ERR-07
    else tanpa item
        SVC-->>API: ERR-09
    else valid
        loop setiap item
            SVC->>DB: UPDATE stocks SET qty_reserved += qty WHERE product_id AND (qty_on_hand - qty_reserved) >= qty
        end
        alt ada item dengan 0 baris terpengaruh
            SVC->>DB: ROLLBACK (tanpa reservasi parsial)
            SVC-->>API: ERR-05 (daftar semua item yang kurang)
            API-->>SA: 422, status tetap draft
        else semua item berhasil
            SVC->>DB: INSERT stock_reservations (active) per item
            SVC->>DB: UPDATE status=submitted, submitted_at
            SVC->>DB: INSERT order_status_histories (draft -> submitted, user, waktu)
            SVC->>DB: COMMIT
            API-->>SA: 200 status submitted
        end
    end
```

## 7. Sequence: Approve atau Reject (US-05, US-06)

Sumber: 05-interface.md. Approve mengurangi stok fisik; reject tidak [ABM-4].

```mermaid
sequenceDiagram
    actor SV as Supervisor
    participant API as OrderController
    participant SVC as OrderService
    participant DB as SQL Server
    SV->>API: POST /sales-orders/{id}/approve atau /reject {note}
    API->>SVC: decide(order, decision, note, user)
    SVC->>DB: BEGIN; SELECT order WITH (UPDLOCK)
    alt status bukan submitted
        SVC-->>API: ERR-07
    else reject tanpa catatan
        SVC-->>API: ERR-10
    else approve
        SVC->>DB: UPDATE stocks SET qty_on_hand -= qty, qty_reserved -= qty (per item)
        SVC->>DB: UPDATE stock_reservations SET status=released
        SVC->>DB: UPDATE status=approved, decided_at
        SVC->>DB: INSERT order_status_histories (submitted -> approved, user, waktu, note)
        SVC->>DB: COMMIT
        API-->>SV: 200 approved
    else reject
        SVC->>DB: UPDATE stocks SET qty_reserved -= qty (per item; qty_on_hand tetap)
        SVC->>DB: UPDATE stock_reservations SET status=released
        SVC->>DB: UPDATE status=rejected, decided_at
        SVC->>DB: INSERT order_status_histories (submitted -> rejected, user, waktu, note)
        SVC->>DB: COMMIT
        API-->>SV: 200 rejected
    end
```

## 8. Peta Layar dan Navigasi

Sumber: 05-interface.md bagian 3.

```mermaid
flowchart LR
    S01[S-01 Login] --> S02[S-02 Daftar Pesanan]
    S02 --> S03["S-03 Form Pesanan (draft)<br/>Sales Admin"]
    S02 --> S04[S-04 Detail Pesanan]
    S02 --> S05["S-05 Antrian Approval<br/>Supervisor"]
    S03 -- submit --> S04
    S04 --> S06[S-06 Riwayat Status dan Audit]
    S05 -- approve / reject --> S04
```
