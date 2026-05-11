# <img alt="WiseClone Logo" src="https://wise.com/public-resources/assets/logos/wise/brand_logo.svg" width="120"> WiseClone

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)

A modern money transfer application inspired by [Wise](https://www.wise.com) (formerly TransferWise). Built with Laravel 13, featuring multi-currency transfers, admin-managed rates and fees, atomic balance updates, and a clean fintech MVP interface.

## ✨ Features

-   🔐 **Secure Authentication** - User registration, login, email verification, and encrypted session support
-   💱 **Multi-Currency Support** - Transfer between USD, EUR, and NGN
-   📊 **Admin Rate Management** - Admin users can update exchange rates, variable fees, and fixed fees from the UI
-   📝 **Transaction History** - Complete audit trail of all transfers
-   💰 **Atomic Transfer Posting** - Debit and credit legs are recorded in one locked database transaction
-   👤 **Role-Based Access** - Admin and customer roles; admins can manage rates and make transfers
-   🐳 **Docker Ready** - Full Docker configuration for easy deployment
-   ✅ **Comprehensive Tests** - Unit and feature tests included

## 📋 Requirements

-   PHP 8.3 or higher
-   Composer 2.x
-   MySQL 8.0+ or MariaDB 10.x
-   Node.js 18+ (for frontend assets)

## 🚀 Quick Start

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

## 🔑 Demo Credentials

| Role  | Email               | Password |
| ----- | ------------------- | -------- |
| Admin | admin@wiseclone.com | password |
| User  | user@wiseclone.com  | password |

> Demo accounts are seeded for local development and interviews only. Replace seeded passwords and credentials before any real deployment.

## 🧑‍💼 Admin Capabilities

-   View and update exchange rates for all supported currency pairs.
-   Update variable percentage fees and fixed fees without changing code.
-   Make transfers from admin accounts when the admin has an available balance.
-   View all transactions from the dashboard.

Rate management is available at:

```text
/admin/rates
```

## 🏗️ Project Structure

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

## 🧪 Running Tests

```bash
# Run all tests through the local Artisan wrapper
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

## 🐳 Docker Services

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

## 📸 Screenshots

![Dashboard](images/screen-1.png)
_Dashboard showing transaction history and balances_

![New Transaction](images/screen-2.png)
_Create new money transfer with live conversion_

![Transaction Details](images/screen-3.png)
_Detailed view of a completed transaction_

## 🔒 Security Features

-   CSRF protection on all forms
-   Email verification required for protected money routes
-   POST-only funding action
-   Admin-only rate management
-   Atomic debit/credit posting with locked balance rows
-   Password hashing with bcrypt
-   Strong password validation for new registrations
-   Input validation and sanitization
-   SQL injection prevention via Eloquent ORM
-   XSS protection with Blade templating
-   Rate limiting on authentication routes
-   Encrypted sessions by default

## 📝 API Endpoints

| Method | Endpoint                        | Description                     |
| ------ | ------------------------------- | ------------------------------- |
| POST   | `/transaction/source-converter` | Convert currency (AJAX)         |
| POST   | `/transaction/currency-balance` | Get balance for currency (AJAX) |
| GET    | `/transaction/create`           | New transaction form            |
| POST   | `/transaction`                  | Create transaction              |
| GET    | `/transaction/{uuid}`           | View transaction details        |
| GET    | `/admin/rates`                  | Admin rate management           |
| PATCH  | `/admin/rates/{charge}`         | Update a rate/fee pair          |


## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Acknowledgements

-   [Laravel](https://laravel.com) - The PHP framework for web artisans
-   [Wise](https://wise.com) - Inspiration for the application concept
-   [Keenthemes Metronic](https://keenthemes.com) - UI components

---

Made with Laravel 13
