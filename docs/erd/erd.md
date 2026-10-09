# ERD — MVP Sales Order

Turunan dari [PRD_Sales_Order_MVP.md](prd/PRD_Sales_Order_MVP.md); detail kolom, constraint, dan index ada di [schema.dbml](schema.dbml).
Target database: Microsoft SQL Server (Laravel).

```mermaid
erDiagram
    roles ||--o{ users : "memiliki"
    users ||--o{ sales_orders : "membuat (created_by)"
    users ||--o{ order_decisions : "memutuskan (decided_by)"
    users ||--o{ audit_logs : "melakukan"
    customers ||--o{ sales_orders : "memesan"
    products ||--|| stocks : "punya stok"
    products ||--o{ sales_order_items : "dipesan"
    products ||--o{ stock_reservations : "direservasi"
    sales_orders ||--|{ sales_order_items : "berisi"
    sales_order_items ||--o{ stock_reservations : "mereservasi"
    sales_orders ||--o{ order_decisions : "diputuskan"
    sales_orders |o--o{ audit_logs : "dicatat"

    roles {
        int id PK
        varchar code UK "sales | warehouse | supervisor"
        nvarchar name
    }
    users {
        bigint id PK
        int role_id FK
        nvarchar name
        varchar email UK
        varchar password
        bit is_active
    }
    customers {
        bigint id PK
        varchar code UK
        nvarchar name
        bit is_active
    }
    products {
        bigint id PK
        varchar sku UK
        nvarchar name
        varchar uom
        bit is_active
    }
    stocks {
        bigint id PK
        bigint product_id FK,UK
        decimal qty_on_hand "CHECK >= 0"
        decimal qty_reserved "CHECK 0..qty_on_hand"
        rowversion row_version "concurrency [ASUMSI-2]"
    }
    sales_orders {
        bigint id PK
        varchar order_no UK
        bigint customer_id FK
        bigint created_by FK
        varchar status "draft | submitted | approved | rejected"
        nvarchar note
        datetime2 submitted_at
        datetime2 decided_at
    }
    sales_order_items {
        bigint id PK
        bigint sales_order_id FK
        bigint product_id FK
        decimal qty "CHECK > 0"
    }
    stock_reservations {
        bigint id PK
        bigint sales_order_item_id FK
        bigint product_id FK
        decimal qty
        varchar status "active | released"
        datetime2 reserved_at
        datetime2 released_at
    }
    order_decisions {
        bigint id PK
        bigint sales_order_id FK
        bigint decided_by FK
        varchar decision "approve | reject"
        nvarchar note
        datetime2 decided_at
    }
    audit_logs {
        bigint id PK
        bigint sales_order_id FK "nullable"
        bigint user_id FK
        varchar action
        varchar entity_type
        bigint entity_id
        nvarchar old_values "JSON"
        nvarchar new_values "JSON"
        nvarchar note
        datetime2 created_at
    }
```

## Ringkasan Relasi

| Relasi | Kardinalitas | Kebutuhan |
|---|---|---|
| roles → users | 1 : N | FR-9 |
| customers → sales_orders | 1 : N (satu pesanan satu customer) | FR-2, ASUMSI-5 |
| users → sales_orders | 1 : N (Sales pembuat) | FR-2 |
| sales_orders → sales_order_items | 1 : N (min. 1 item saat submit) | FR-2 |
| products → stocks | 1 : 1 (satu gudang) | FR-3, ASUMSI-6 |
| sales_order_items → stock_reservations | 1 : N | FR-4, ASUMSI-1 |
| sales_orders → order_decisions | 1 : N (riwayat keputusan) | FR-6, OQ-3 |
| sales_orders → audit_logs | 1 : N (append-only) | FR-8 |

## Aturan Kunci

- **Stok tersedia** = `qty_on_hand - qty_reserved`, tidak pernah negatif (CHECK + update atomik).
- **Transisi status**: `draft → submitted → approved | rejected`; selain itu ditolak.
- **Reject** melepas reservasi (`status = released`); **approve** mempertahankannya (ASUMSI-4).
- **audit_logs** hanya INSERT.

## Tidak Dimodelkan (menunggu jawaban klien)

Harga/diskon/pajak (OQ-6), permission rinci per role (OQ-1), deteksi pesanan ganda (OQ-9), pengiriman warehouse (OQ-11).

Catatan implementasi Laravel: tabel `users` bawaan Laravel diperluas dengan `role_id` dan `is_active`.
