# <img alt="WiseClone Logo" src="https://wise.com/public-resources/assets/logos/wise/brand_logo.svg" width="120"> WiseClone

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)

WiseClone is a Laravel 13 money transfer application inspired by [Wise](https://www.wise.com). It supports authenticated multi-currency transfers, admin-managed users and rates, persisted transfer quotes, idempotent money movement, normalized ledger accounts, immutable audit exports, reconciliation jobs, and CI-backed quality gates.

## Features

-   **Secure authentication** - Registration, login, email verification, encrypted sessions, strong password rules, and password-change session revocation.
-   **Multi-currency transfers** - Transfers between supported currencies with persisted quotes, fee breakdowns, exchange rates, expiry, and accepted quote metadata.
-   **Normalized ledger** - Currency-agnostic `accounts` and `ledger_entries` tables track balances and support accounting reports beyond fixed USD/EUR/NGN columns.
-   **Explicit ledger events** - Transfer lifecycle events include pending, posted debit, posted credit, settled, failed, reversed, and refunded states.
-   **Idempotent operations** - Transfer submission and account funding use idempotency keys to prevent duplicate money movement on retries.
-   **Admin user management** - Admins can create, list, block, unblock, soft-delete, and review user accounts and related transactions.
-   **Admin rate management** - Admins can update exchange rates, variable fees, and fixed fees from the UI.
-   **Audit and operations** - Reconciliation jobs, immutable ledger audit exports, backup records, structured logs, and CI gates are included.
-   **Role-based access** - Admin and customer roles protect user management, rate management, and money movement.
-   **Test coverage** - Unit and feature tests cover authentication, account management, transfers, quotes, idempotency, ledger events, and operational jobs.

## Requirements

-   PHP 8.3 or higher
-   BCMath PHP extension
-   Composer 2.x
-   MySQL 8.0+ or MariaDB 10.x
-   Node.js 18+ (for frontend assets)

## Quick Start

### Option 1: Traditional Setup

```bash
# Clone the repository
git clone https://github.com/your-username/wiseclone.git
cd wiseclone

# Install PHP dependencies
composer install

# Copy environment file and generate app key
cp .env.example .env
php artisan key:generate

# Configure your database in .env file
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=wiseclone
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations and seed the database
php artisan migrate:fresh --seed

# Start the development server
php artisan serve
```

### Option 2: Docker Setup

```bash
# Clone the repository
git clone https://github.com/your-username/wiseclone.git
cd wiseclone

# Copy environment file
cp .env.example .env

# Update .env for Docker
# DB_HOST=mysql
# DB_DATABASE=wiseclone
# DB_USERNAME=wiseclone
# DB_PASSWORD=secret

# Start Docker containers
docker-compose up -d

# Install dependencies inside container
docker-compose exec app composer install

# Generate app key
docker-compose exec app php artisan key:generate

# Run migrations and seed
docker-compose exec app php artisan migrate:fresh --seed
```

Access the application at: http://localhost:8080 (Docker) or http://localhost:8000 (Traditional)

## Demo Credentials

| Role  | Email               | Password |
| ----- | ------------------- | -------- |
| Admin | admin@wiseclone.com | password |
| User  | user@wiseclone.com  | password |

> Seeded accounts are for local development only. Replace seeded passwords and credentials before any real deployment.

## Admin Capabilities

-   Create customer or administrator accounts from the admin UI.
-   List users, review account status, and view user details.
-   Block and unblock user access without deleting transaction history.
-   Soft-delete users while preserving transaction and balance audit records.
-   View a user's related transactions from the user details page.
-   View and update exchange rates for all supported currency pairs.
-   Update variable percentage fees and fixed fees without changing code.
-   Make transfers from admin accounts when the admin has an available balance.
-   View all transactions from the dashboard.

Admin management pages are available at:

```text
/admin/users
/admin/rates
```

## Ledger Architecture

WiseClone now treats the normalized ledger as the authoritative money movement layer:

-   `accounts` stores one balance per user, currency, and account type.
-   `ledger_entries` records immutable ledger events with direction, status, amount, balance after posting, transfer group UUID, and optional transaction/quote/idempotency references.
-   `transfer_quotes` persists source amount, target amount, rate, fixed fee, variable fee, transfer fee, amount to convert, expiry, and accepted metadata.
-   `idempotency_keys` protects retry-safe operations from duplicate posting.
-   Existing `transactions` and `currency_balances` records are maintained as compatibility projections for current screens and historical views.

Transfer posting creates explicit events for pending, debit, credit, and settlement. Failed attempts are recorded as failed ledger events without mutating balances.

## Operations

-   `ReconcileLedgerJob` compares account balances against the latest posted/settled ledger entries and records each run.
-   `ExportImmutableAuditLogJob` writes a JSONL export of ledger entries and stores a checksum.
-   `BackupDatabaseJob` records backup snapshots and checksums.
-   Scheduled jobs are registered in `routes/console.php`.
-   `.github/workflows/ci.yml` runs Composer install, lint, and tests on pushes and pull requests.
-   Structured logs are emitted for funding, settled transfers, failed transfers, reconciliation, audit exports, and backups.

## Project Structure

```
wiseclone/
├── app/
│   ├── Http/Controllers/     # Application controllers
│   ├── Models/               # Eloquent models
│   ├── Services/             # Business logic services
│   └── Traits/               # Reusable traits
├── database/
│   ├── factories/            # Model factories for testing
│   ├── migrations/           # Database migrations
│   └── seeders/              # Database seeders with demo data
├── docker/                   # Docker configuration files
├── resources/views/          # Blade templates
├── routes/                   # Application routes
└── tests/                    # PHPUnit tests
    ├── Feature/              # Feature/integration tests
    └── Unit/                 # Unit tests
```

## Running Tests

```bash
# Run all tests with Laravel's grouped output
php artisan test

# Or use Composer scripts
composer test
composer lint
composer fix

# Run a specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

## 💱 Currency Exchange API

The application uses the [Free Currency Converter API](https://www.currencyconverterapi.com/) for real-time exchange rates. To enable this feature:

1. Get a free API key at: https://free.currencyconverterapi.com/free-api-key
2. Add the key to your `.env` file:

```env
CURRENCY_CONVERTER_API_KEY=your_api_key_here
```

If no API key is configured, the application will use fallback rates stored in the database.

## Docker Services

The Docker setup includes:

| Service    | Port | Description                    |
| ---------- | ---- | ------------------------------ |
| nginx      | 8080 | Web server                     |
| app        | 9000 | PHP-FPM application            |
| mysql      | 3306 | MySQL database                 |
| redis      | 6379 | Redis cache                    |
| phpmyadmin | 8081 | Database management (dev only) |

### Docker Commands

```bash
# Start all services
docker-compose up -d

# Start with dev tools (phpMyAdmin)
docker-compose --profile dev up -d

# Start with queue workers
docker-compose --profile workers up -d

# View logs
docker-compose logs -f app

# Execute artisan commands
docker-compose exec app php artisan [command]

# Stop all services
docker-compose down
```

## Screenshots

![Dashboard](images/screen-1.png)
_Dashboard showing transaction history and balances_

![New Transaction](images/screen-2.png)
_Create new money transfer with live conversion_

![Transaction Details](images/screen-3.png)
_Detailed view of a completed transaction_

## Security Features

-   CSRF protection on all forms
-   Email verification required for protected money routes
-   POST-only funding action
-   Admin-only rate management
-   Admin-only user management
-   Blocked users are prevented from logging in and are signed out from active sessions
-   Soft deletes for users so transaction history remains auditable
-   Atomic ledger posting with locked account rows
-   Persisted quotes with expiry and accepted metadata
-   Idempotency keys for retry-safe transfer and funding actions
-   Immutable ledger entries with explicit event status
-   Reconciliation, audit export, and backup job records
-   Structured operational logs
-   Password hashing with bcrypt
-   Authenticated password change with current-password verification
-   Strong password validation for new registrations
-   Input validation and sanitization
-   SQL injection prevention via Eloquent ORM
-   XSS protection with Blade templating
-   Rate limiting on authentication routes
-   Encrypted sessions by default

## Routes

| Method | Endpoint                        | Description                     |
| ------ | ------------------------------- | ------------------------------- |
| POST   | `/transaction/source-converter` | Convert currency (AJAX)         |
| POST   | `/transaction/currency-balance` | Get balance for currency (AJAX) |
| GET    | `/transaction/create`           | New transaction form            |
| POST   | `/transaction`                  | Create transaction              |
| GET    | `/transaction/{uuid}`           | View transaction details        |
| GET    | `/account/password`             | Change password form            |
| PATCH  | `/account/password`             | Update own password             |
| GET    | `/admin/users`                  | Admin user list                 |
| GET    | `/admin/users/create`           | Admin create user form          |
| POST   | `/admin/users`                  | Admin create user action        |
| GET    | `/admin/users/{uuid}`           | Admin user details              |
| PATCH  | `/admin/users/{uuid}/block`     | Block user account              |
| PATCH  | `/admin/users/{uuid}/unblock`   | Unblock user account            |
| DELETE | `/admin/users/{uuid}`           | Soft-delete user account        |
| GET    | `/admin/rates`                  | Admin rate management           |
| PATCH  | `/admin/rates/{charge}`         | Update a rate/fee pair          |

## Quality Gates

```bash
composer lint
composer test
```

The CI workflow runs the same lint and test commands.

Current verified suite: `77 passed (187 assertions)`.

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Acknowledgements

-   [Laravel](https://laravel.com) - The PHP framework for web artisans
-   [Wise](https://wise.com) - Inspiration for the application concept
-   [Keenthemes Metronic](https://keenthemes.com) - UI components

---

Made with Laravel 13
