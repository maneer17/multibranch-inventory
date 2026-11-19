# Stoq

Multi-branch inventory management with automatic replenishment. Stoq tracks stock at every branch, records each change in an immutable ledger, and suggests transfers from branches with surplus to branches running low.

**Live demo:** [stoq.manar.page](https://stoq.manar.page) · 

## Demo accounts

The live demo is seeded with sample data and one account per role. Log in at [/admin](https://stoq.manar.page/admin).

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@example.com` | `password` |
| Branch manager | `layla@example.com` | `password` |
| Staff | `omar@example.com` | `password` |

## Features

- **Stock ledger.** Every change (sale, purchase, adjustment, transfer, opening stock) is an immutable, signed entry. Stock can never go below zero, and each write locks the stock row inside a transaction.
- **Transfers between branches.** Requested → Approved → In Transit → Received, with Rejected and Cancelled as end states. Stock is reserved at the source from the moment of the request, and shipped and received quantities can differ.
- **Reorder suggestions.** Reorder points come from the last 30 days of sales. Stock already on its way in is counted, so a shortage disappears once a transfer covers it. Select several suggestions to create one transfer order per source and destination pair.
- **Recording movements.** Sales, purchase receipts, and adjustments are entered through one form. Adjustments require a reason.
- **Exact arithmetic.** All quantities and prices use PHP 8.4's `BcMath\Number` with three decimals, and raw floats are rejected before they can be rounded silently.
- **Branch-scoped access.** What a user sees and does depends on their role and their branch.
- **Auto-generated SKUs.** Each category has its own sequence, safe under concurrent writes.

## How the reorder engine works

For each branch and product:

1. **Usage.** Average daily sales over the last 30 days. With fewer than 5 sales, the manually configured reorder point is used instead.
2. **Reorder point.** `usage × lead time + usage × safety buffer`.
3. **Inventory position.** `on hand − reserved to go out + incoming`. A branch is short when its position is below the reorder point.
4. **Source.** Other branches with surplus are ranked by transfer lead time (from past transfers), then by surplus. The suggested quantity never exceeds what the source can spare.

The dashboard loads everything it needs in a fixed number of queries, so it doesn't slow down as branches and products grow.

## Roles

| Role | Scope | Can do |
|------|-------|--------|
| `admin` | All branches | Manage users, branches, and the catalog; approve or reject transfers; see every movement |
| `branch_manager` | Own branch | Reorder suggestions, request, ship, and receive transfers, record movements |
| `staff` | Own branch | View stock and movements, record movements |

## Tech stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 13, PHP 8.4 |
| Admin panel | Filament 5 |
| Database | PostgreSQL |
| Permissions | spatie/laravel-permission |
| Testing | Pest |
| Hosting | Heroku (web dyno, Heroku Postgres) |

## Running locally

Requires PHP 8.4+, PostgreSQL, Composer, and Node.js.

```bash
git clone https://github.com/your-username/stoq.git
cd stoq
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Set your PostgreSQL connection in `.env` (`DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), then:

```bash
php artisan migrate --seed
php artisan serve
```

The seeder creates branches, users for each role, products, opening stock, and transfers at every stage. Log in at `http://localhost:8000/admin`.

## Deployment

The demo runs on Heroku. A `Procfile` defines the web process and runs migrations on every release. The app reads Heroku's `DATABASE_URL`, and sessions are stored in the database, since Heroku's disk is not persistent.

## Roadmap

- Daily low-stock email digest for each branch
- Excel export for stock and movements
- Docker setup for local development
