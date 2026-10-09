# 07 — Kumpulan Diagram (Mermaid)

Semua diagram desain dalam satu berkas, sebagai teks Mermaid. Dapat ditempel ke mermaid.live atau dirender langsung oleh GitHub, VS Code, dan Obsidian. Penjelasan lengkap tiap diagram ada di dokumen sumbernya. Bila ada selisih, dokumen sumber (01, 04, 05) yang berlaku; perbarui berkas ini bersamaan.

Label **[USULAN]** = belum diputuskan klien (01-process.md bagian 6).

## 1. Aktor dan Hak Akses (ikhtisar)

Sumber: 02-rules.md bagian 2 (matriks hak akses).

```mermaid
flowchart LR
    SA([Sales Admin])
    SV([Supervisor])
    WH([Warehouse])

    subgraph Aplikasi Sales Order
        UC1[Buat draft pesanan]
        UC2[Tambah/ubah/hapus item<br/>cek dan reservasi stok]
        UC3[Submit pesanan]
        UC4[Approve / reject dengan catatan]
        UC5[Lihat daftar, cari, filter]
        UC6[Lihat audit trail]
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

## 2. Diagram Status Pesanan

Sumber: 01-process.md bagian 3.

```mermaid
stateDiagram-v2
    [*] --> draft: Sales Admin membuat pesanan
    draft --> submitted: Submit (min. 1 item)
    submitted --> approved: Supervisor approve
    submitted --> rejected: Supervisor reject (catatan wajib)
    approved --> [*]
    rejected --> [*]
```

## 3. Alur Proses Usulan

Sumber: 01-process.md bagian 4.

```mermaid
flowchart TD
    A([Mulai]) --> B[Sales Admin pilih customer, buat draft]
    B --> C[Tambah item: product + qty]
    C --> D{Qty <= stok tersedia?}
    D -- Tidak --> E[Tolak: stok tidak cukup, tampilkan stok tersedia] --> C
    D -- Ya --> F[Reservasi stok, simpan item]
    F --> G{Tambah/ubah item lagi?}
    G -- Ya --> C
    G -- Tidak --> H{Minimal 1 item?}
    H -- Tidak --> I[Tolak submit: pesanan kosong] --> C
    H -- Ya --> J[Submit -> status submitted, pesanan terkunci]
    J --> K[Supervisor tinjau]
    K --> L{Keputusan}
    L -- Approve --> M[Status approved, reservasi dipertahankan]
    L -- Reject + catatan --> N[Status rejected, reservasi dilepas]
    M --> O[Warehouse melihat pesanan approved]
    M --> P([Selesai])
    N --> P
    O --> P
```

## 4. ERD

Sumber: 04-data.md bagian 1 dan [../schema.dbml](../schema.dbml).

```mermaid
erDiagram
    roles ||--o{ users : "memiliki"
    users ||--o{ sales_orders : "membuat (created_by)"
    customers ||--o{ sales_orders : "dipesan oleh"
    sales_orders ||--|{ sales_order_items : "berisi"
    products ||--o{ sales_order_items : "dipesan"
    products ||--|| stocks : "punya stok"
    sales_order_items ||--o| stock_reservations : "mereservasi"
    products ||--o{ stock_reservations : "ditahan"
    sales_orders ||--o| order_decisions : "diputuskan"
    users ||--o{ order_decisions : "memutuskan"
    sales_orders ||--o{ audit_logs : "dicatat"
    users ||--o{ audit_logs : "pelaku"
```

## 5. Sequence: Menambah Item dengan Reservasi Stok (US-02)

Sumber: 05-interface.md bagian 1.1.

```mermaid
sequenceDiagram
    actor SA as Sales Admin
    participant API as OrderController
    participant SVC as OrderService
    participant DB as SQL Server
    SA->>API: POST /sales-orders/{id}/items {product_id, qty}
    API->>SVC: addItem(order, product, qty, user)
    SVC->>DB: BEGIN TRANSACTION
    SVC->>DB: cek order draft + milik user
    alt bukan draft / bukan milik
        SVC-->>API: ERR-08 / ERR-03
    end
    SVC->>DB: UPDATE stocks SET qty_reserved += qty WHERE product_id AND (qty_on_hand - qty_reserved) >= qty
    alt 0 baris terpengaruh
        SVC->>DB: ROLLBACK
        SVC-->>API: ERR-05 (sertakan stok tersedia)
        API-->>SA: 422
    else 1 baris terpengaruh
        SVC->>DB: INSERT sales_order_items
        SVC->>DB: INSERT stock_reservations (active)
        SVC->>DB: INSERT audit_logs (item_added)
        SVC->>DB: COMMIT
        API-->>SA: 201 item + stok tersedia terbaru
    end
```

## 6. Sequence: Submit Pesanan (US-04)

Sumber: 05-interface.md bagian 1.2.

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
        SVC->>DB: UPDATE status=submitted, submitted_at
        SVC->>DB: INSERT audit_logs (submitted)
        SVC->>DB: COMMIT
        API-->>SA: 200 status submitted
    end
```

## 7. Sequence: Approve atau Reject (US-05, US-06)

Sumber: 05-interface.md bagian 1.3.

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
        SVC->>DB: INSERT order_decisions (approve)
        SVC->>DB: UPDATE status=approved, decided_at
        SVC->>DB: INSERT audit_logs (approved)
    else reject
        SVC->>DB: INSERT order_decisions (reject, note)
        SVC->>DB: UPDATE stock_reservations SET status=released
        SVC->>DB: UPDATE stocks SET qty_reserved -= qty (per item)
        SVC->>DB: UPDATE status=rejected, decided_at
        SVC->>DB: INSERT audit_logs (rejected)
    end
    SVC->>DB: COMMIT
    API-->>SV: 200
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
    S04 --> S06[S-06 Audit Trail]
    S05 -- approve / reject --> S04
```
