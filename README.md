# Product Configurator

A web-based product configuration system built with Laravel 13, Livewire 4, and Tailwind CSS 4. Allows businesses to define configurable products with attributes, options, pricing rules, BOM rules, and compatibility constraints — and lets end users configure products interactively.

## Tech Stack

- **PHP** 8.4 / **Laravel** 13
- **Livewire** 4 (reactive UI without writing JavaScript)
- **Tailwind CSS** 4 (utility-first styling via Vite)
- **MySQL** (primary database)
- **Spatie Laravel Permission** (role-based access control)
- **DomPDF** (PDF export)
- **PhpSpreadsheet** (Excel export)

## Features

### Admin Panel
- **Products** — create and manage configurable products with status workflow (Draft → In Review → Approved → Published → Archived)
- **Categories & Families** — hierarchical product taxonomy
- **Attributes & Options** — define configurable attributes (select, multi-select, text, number, boolean) with options per product
- **Pricing** — price lists with per-product and per-option pricing, validity periods, and currency support
- **Configuration Rules** — visibility, required, warning, and BOM rules with condition/action logic
- **Compatibility Rules** — define compatible/incompatible option combinations
- **BOM Rules** — automatically add components to a bill of materials based on selected options
- **Part Number Patterns** — auto-generate part numbers from configuration selections
- **Components** — manage physical components used in BOM rules
- **Users & Roles** — user management with Spatie permission roles
- **Configurations** — view all saved customer configurations
- **Quotation Requests** — manage quote requests submitted by customers
- **Audit Logs** — track all create/update/delete actions

### Configurator (Customer-facing)
- Step-by-step product configuration
- Real-time rule evaluation (visibility, required fields, warnings)
- Live price calculation
- Configuration summary and part number generation
- Quotation request submission

## Requirements

- PHP >= 8.3
- Composer
- Node.js >= 18
- MySQL 8+

## Installation

```bash
git clone https://github.com/afafirmansyah/product-configurator.git
cd product-configurator

# Install dependencies and set up environment
composer install
cp .env.example .env
php artisan key:generate

# Configure your database in .env, then run migrations and seeders
php artisan migrate --seed

# Install frontend dependencies and build assets
npm install
npm run build
```

Or use the composer setup script:

```bash
composer run setup
```

## Development

```bash
composer run dev
```

This starts the Laravel dev server, queue worker, and Vite HMR concurrently.

## Environment Variables

Key variables to configure in `.env`:

```env
APP_URL=http://product-configurator.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=product_configurator
DB_USERNAME=root
DB_PASSWORD=
```

## Testing

```bash
composer run test
# or
php artisan test
```

## Project Structure

```
app/
├── Http/Controllers/Admin/   # Admin CRUD controllers
├── Livewire/Admin/           # Livewire components (product list, etc.)
├── Models/                   # Eloquent models
├── Services/                 # Business logic (rule engine, pricing, BOM)
├── DTOs/                     # Data transfer objects
└── Enums/                    # PHP 8.1+ enums

resources/views/
├── admin/                    # Admin panel Blade views
├── components/ui/            # Reusable UI components (button, badge, modal, etc.)
├── layouts/                  # Admin and configurator layouts
└── livewire/                 # Livewire component views
```

## License

MIT
