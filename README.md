# Product Configurator

A web-based Configure-Price-Quote (CPQ) and product configuration platform built with **Laravel 13**, **Livewire 4**, and **Tailwind CSS 4**. It lets businesses define configurable products with attributes, options, pricing, rule-driven validation, and multi-level Bills of Materials — and lets end users configure products interactively with real-time validation, pricing, and BOM generation.

The configuration and BOM engine is inspired by the behavior of the OCA Odoo `product_configurator`, `product_configurator_mrp`, and `product_configurator_mrp_component` modules, re-implemented natively (clean-room) for Laravel.

---

## Highlights

- **Rule-driven configuration** — grouped AND/OR conditions with a full action vocabulary (show/hide, require/optional, set value, add/remove component, set price, set part number, warn/error).
- **Symmetric compatibility rules** — compatible / incompatible option combinations enforced in both directions.
- **Multi-level BOM generation** — nested configurable sub-assemblies with cycle detection and a configurable max depth.
- **Attribute-value BOM gating** — a BOM line can be restricted to one or more attribute-value sets (OCA "configuration set" semantics).
- **Computed component quantities** — fixed, per-attribute multiplier, or a safe formula (e.g. `{length} / 2 + 1`) evaluated by a sandboxed parser (no `eval`).
- **Configuration snapshots & versioning** — saved configurations keep an immutable BOM snapshot and revision, so changing a product definition never silently rewrites an existing configuration.
- **Backend-authoritative** — all validation and calculation happen server-side; money is handled in integer cents to avoid float drift.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language / Framework | PHP 8.4, Laravel 13 |
| Interactive UI | Livewire 4 |
| Styling | Tailwind CSS 4 (via Vite) |
| Access control | spatie/laravel-permission |
| PDF export | DomPDF |
| Excel export | PhpSpreadsheet |
| Database | MySQL 8+ (recommended) / PostgreSQL / SQLite |
| Testing | Pest / PHPUnit |

---

## Features

### Admin Panel
- **Products** — configurable products with a status workflow (Draft → In Review → Approved → Published → Archived).
- **Categories & Families** — hierarchical product taxonomy.
- **Attributes & Options** — typed attributes (select, multi-select, text, number, boolean) with options, plus per-attribute validation rules (type, min/max, required).
- **Pricing** — price lists with per-product and per-option pricing, validity periods, and currency support.
- **Configuration Rules** — visibility, required, default-value, warning, pricing, and part-number rules with condition/action logic.
- **Compatibility Rules** — compatible / incompatible option combinations (create, edit, delete).
- **BOM Rules** — condition-driven bills of materials with:
  - attribute-value sets per line (include a component only when a value set is fully selected),
  - computed quantities (fixed / attribute multiplier / formula),
  - configurable sub-assemblies that reference another configurable product (nested BOM).
- **Part Number Patterns** — auto-generated part numbers from configuration selections.
- **Components** — manage physical components used in BOM rules.
- **Users & Roles** — user management backed by spatie roles/permissions, with login throttling and active/inactive enforcement.
- **Configurations, Quotation Requests, Audit Logs** — review saved configurations, manage quotes, and track all create/update/delete actions.

### Configurator (customer-facing)
- Step-by-step product configuration with real-time rule evaluation and live pricing.
- Nested BOM preview and configuration summary with generated part number.
- Field-level validation errors and disabled-option reasons.
- Quotation request submission with lifecycle email notifications.
- Quotation PDF export and queued BOM export (PDF / Excel) via signed download links.

---

## Requirements

- PHP >= 8.4 (see the `php` constraint in `composer.json`)
- Composer
- Node.js >= 18
- A database: **MySQL 8+** (used in this project's development environment), PostgreSQL, or SQLite for a zero-config/test setup

All `composer`, `artisan`, and `test` commands must run under PHP 8.4.

---

## Installation

```bash
git clone https://github.com/afafirmansyah/product-configurator.git
cd product-configurator

# Install dependencies and set up environment
composer install
cp .env.example .env
php artisan key:generate

# Configure your database in .env (see below), then migrate and seed
php artisan migrate --seed

# Install frontend dependencies and build assets
npm install
npm run build
```

Or use the composer convenience script:

```bash
composer run setup
```

---

## Database Configuration

### MySQL (recommended)

Create an empty schema, then point `.env` at it:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=product_configurator
DB_USERNAME=root
DB_PASSWORD=your_password
```

```sql
CREATE DATABASE product_configurator CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate --seed
```

> **Note:** All migrations are additive and backward compatible (new tables / nullable columns), so running `php artisan migrate` on an existing `product_configurator` schema applies new changes without destroying data. Use `migrate:fresh` only on a throwaway database.

### SQLite (zero-config, used by the test suite)

```env
DB_CONNECTION=sqlite
```

```bash
touch database/database.sqlite   # or create an empty file at database/database.sqlite
php artisan migrate --seed
```

### PostgreSQL

```env
DB_CONNECTION=pgsql
DB_PORT=5432
```

---

## Development

```bash
composer run dev
```

This starts the Laravel dev server, queue worker, and Vite HMR concurrently.

Asynchronous features — queued BOM exports and quotation lifecycle notifications — require a running queue worker (`php artisan queue:work`, started automatically by `composer run dev`). The development default is `MAIL_MAILER=log`, so notification emails are written to the log rather than sent. Configure a production SMTP mailer as needed.

---

## Configuration

BOM-engine behavior is tunable in `config/configurator.php`, including the maximum nesting depth for multi-level BOMs (default: 5). Nested generation is guarded by both a cycle detector (visited product set) and this depth limit.

---

## Testing

```bash
composer run test
# or
php artisan test --compact
```

The suite runs on SQLite by default and covers the rule engine, compatibility, pricing, part numbers, BOM generation (including value sets, computed quantities, and nested BOMs), authorization, and configuration versioning.

---

## Architecture

Business logic lives in a dedicated service layer; controllers and Blade views stay thin. Validation uses Form Requests; monetary math uses an integer-cents money helper.

```
app/
├── Enums/                       # PHP enums (e.g. QuantityMode, RuleOperator)
├── Exceptions/                  # Domain exceptions (BOM structure, formula)
├── Http/
│   ├── Controllers/Admin/       # Admin CRUD controllers
│   └── Requests/Admin/          # Form Request validation
├── Livewire/                    # Interactive configurator & admin components
├── Models/                      # Eloquent models
├── Services/
│   ├── Bom/                     # BOM generation, nesting, quantity resolver, safe formula evaluator
│   ├── Configurator/            # Configuration evaluation, compatibility, attribute validation
│   ├── Pricing/                 # Price-list resolution (integer cents)
│   └── Rules/                   # Rule engine + shared condition evaluator
└── Support/                     # Money helper, etc.

resources/views/
├── admin/                       # Admin panel Blade views
├── components/                  # Reusable UI (incl. nested BOM tree)
├── livewire/                    # Livewire component views
└── sales/                       # Configurator, detail, and export templates
```

### Key concepts

| Concept | Where |
|---|---|
| Configurable product + attributes/options | `products`, `attributes`, `attribute_options`, `product_attributes` |
| Saved configuration (variant surrogate) | `configuration_sessions` + `configuration_items` |
| Configuration rule engine | `configuration_rules` → `rule_conditions` / `rule_actions` |
| Compatibility | `compatibility_rules` |
| BOM template | `bom_rules` → `bom_rule_conditions` / `bom_rule_items` |
| Attribute-value gating per BOM line | `bom_rule_item_value_sets` |
| Computed quantity | `bom_rule_items.quantity_mode` / `quantity_attribute_id` / `quantity_formula` |
| Nested sub-assembly | `bom_rule_items.child_product_id` → `products` |
| Persisted BOM snapshot + revision | `configuration_sessions.bom_snapshot` / `bom_revision` |

---

## Attribution & License

This project is released under the **MIT License**.

The configuration and BOM behavior is inspired by the OCA Odoo modules `product_configurator`, `product_configurator_mrp`, and `product_configurator_mrp_component` (AGPL-3.0). No OCA source code is included or copied; the behavior was re-implemented independently in PHP/Laravel.
