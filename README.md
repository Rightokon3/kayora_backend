# Kayora Backend

A modern, high-performance delivery and order management system built with Laravel 12 and Laravel Octane.

## 📋 Table of Contents

- [About](#about)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Running the Application](#running-the-application)
- [Database](#database)
- [Testing](#testing)
- [Project Structure](#project-structure)
- [API Documentation](#api-documentation)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [Troubleshooting](#troubleshooting)
- [Support](#support)
- [License](#license)

## 📌 About

Kayora Backend is a robust Laravel application designed to handle delivery operations, order management, driver logistics, and user administration. It leverages Laravel Octane for high-performance request handling and includes comprehensive API endpoints for seamless integration.

## ✨ Features

- **User Management**: Handle customers, administrators, and drivers with role-based access control
- **Order Management**: Create, track, and manage delivery orders with real-time status updates
- **Driver Management**: Manage driver profiles, vehicles, daily statistics, and performance metrics
- **Product Catalog**: Manage products with delivery tiers and pricing
- **Address Management**: Store and manage customer and delivery addresses
- **Push Notifications**: Integrate Expo push notifications for real-time alerts
- **Location Services**: Haversine distance calculations for delivery optimization
- **Revenue Tracking**: Track revenue entries and financial data
- **API First**: RESTful APIs built with Laravel Sanctum for authentication
- **High Performance**: Powered by Laravel Octane (Swoole/FrankenPHP) for better throughput
- **Testing**: Comprehensive test suite using Pest PHP

## 🛠️ Tech Stack

### Backend
- **Framework**: Laravel 12
- **PHP Version**: 8.2+
- **Server**: Laravel Octane 2 (Swoole/FrankenPHP/RoadRunner compatible)
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Sanctum 4
- **Testing**: Pest PHP 3, PHPUnit 11

### Frontend Build Tools
- **Vite**: 7.0.7+
- **Tailwind CSS**: 4.0+
- **JavaScript**: ES6+ with Axios

### Key Dependencies
- `cloudinary-labs/cloudinary-laravel`: Image management and storage
- `laravel/boost`: Development tools
- `laravel/pint`: Code formatting
- `laravel/pail`: Log viewing

## 📦 Prerequisites

Before you begin, ensure you have the following installed:

- **PHP 8.2 or higher** with extensions:
  - OpenSSL
  - PDO
  - Mbstring
  - Tokenizer
  - XML
  - Ctype
  - JSON
  - BCMath
  - Curl

- **Composer** (latest version)
- **Node.js** 16.x or higher (for frontend assets)
- **MySQL** 8.0 or higher
- **Git**

### Optional but Recommended
- **Docker** (for consistent development environments)
- **Redis** (for caching and queue management)

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd kayora_backend
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Create Environment File

```bash
cp .env.example .env
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Install Frontend Dependencies

```bash
npm install
```

### 6. Run Database Migrations

```bash
php artisan migrate
```

### 7. Seed the Database (Optional)

```bash
php artisan db:seed
```

### Quick Setup

Alternatively, use the provided setup script:

```bash
composer run setup
```

This will:
- Install Composer dependencies
- Create `.env` file from `.env.example`
- Generate the application key
- Run migrations
- Install npm packages
- Build frontend assets

## ⚙️ Configuration

### Environment Variables

Create a `.env` file in the project root with the following variables:

```env
APP_NAME=Kayora
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kayora_backend
DB_USERNAME=root
DB_PASSWORD=

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Redis (Optional)
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail Configuration
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@kayora.com
MAIL_FROM_NAME="${APP_NAME}"

# Cloudinary (Image Management)
CLOUDINARY_URL=

# Octane Configuration
# Available servers: swoole, roadrunner, frankenphp
OCTANE_SERVER=swoole
```

### Key Configuration Files

- `config/app.php` - Application settings
- `config/database.php` - Database connections
- `config/auth.php` - Authentication & authorization
- `config/sanctum.php` - API token management
- `config/queue.php` - Queue connections
- `config/cache.php` - Cache stores
- `bootstrap/app.php` - Middleware and exception handling (Laravel 12)

## 🏃 Running the Application

### Development with Hot Reload

Run the development server with concurrent processes (server, queue, and Vite):

```bash
composer run dev
```

This starts:
- Laravel development server on `http://localhost:8000`
- Queue listener for background jobs
- Vite dev server for frontend hot-reload

### Production with Octane

For high-performance production environments:

```bash
php artisan octane:start --server=swoole --workers=4
```

Available Octane servers:
- **Swoole**: High performance, recommended for Linux
- **FrankenPHP**: Modern PHP runtime, works on all platforms
- **RoadRunner**: Cross-platform, efficient resource usage

### Standard Laravel Server

```bash
php artisan serve
```

### Frontend Asset Building

Development:
```bash
npm run dev
```

Production (minified):
```bash
npm run build
```

## 🗄️ Database

### Migrations

Run migrations to create database tables:

```bash
php artisan migrate
```

Rollback migrations:

```bash
php artisan migrate:rollback
```

Reset database (development only):

```bash
php artisan migrate:refresh
```

Reset with seeds:

```bash
php artisan migrate:refresh --seed
```

### Models

The application includes the following Eloquent models:

- **User** - Customer users
- **Admin** - Administrative users
- **Driver** - Delivery drivers
- **DriverProfile** - Driver detailed information
- **DriverDailyStat** - Daily driver statistics
- **Order** - Customer orders
- **OrderItem** - Items within orders
- **OrderDecline** - Declined orders tracking
- **Product** - Catalog products
- **DeliveryTier** - Delivery pricing tiers
- **Address** - Customer and delivery addresses
- **Vehicle** - Driver vehicles
- **RevenueEntry** - Financial tracking
- **AdminPushToken** - Push notification tokens

### Seeders

Run seeders to populate sample data:

```bash
php artisan db:seed --class=AdminSeeder
```

## 🧪 Testing

### Run All Tests

```bash
composer run test
```

### Run Specific Test Suite

```bash
php artisan test --filter=FeatureName
```

### Run Unit Tests Only

```bash
php artisan test --compact --filter=UnitTest
```

### Test Coverage

Generate coverage report:

```bash
php artisan test --coverage
```

### Writing Tests

Create a new feature test:

```bash
php artisan make:test FeatureNameTest --pest
```

Create a unit test:

```bash
php artisan make:test UnitNameTest --pest --unit
```

The project uses **Pest PHP** for modern, readable testing syntax.

## 📁 Project Structure

```
kayora_backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/      # Request handlers
│   │   ├── Middleware/       # HTTP middleware
│   │   └── Resources/        # API response formatters
│   ├── Models/               # Eloquent models
│   ├── Services/             # Business logic
│   │   ├── ExpoPushService.php
│   │   └── HaversineService.php
│   └── Providers/            # Service providers
├── bootstrap/
│   ├── app.php              # Application bootstrap
│   └── providers.php        # Service provider registration
├── config/                   # Configuration files
├── database/
│   ├── factories/           # Model factories for testing
│   ├── migrations/          # Database migrations
│   └── seeders/             # Database seeders
├── public/                  # Public assets
├── resources/
│   ├── css/                # Stylesheets
│   ├── js/                 # Frontend JavaScript
│   └── views/              # Blade templates
├── routes/
│   ├── api.php            # API routes
│   ├── console.php        # Console commands
│   └── web.php            # Web routes
├── storage/               # Application storage
├── tests/                 # Test suite
│   ├── Feature/          # Feature tests
│   └── Unit/             # Unit tests
├── vendor/               # Composer dependencies
├── composer.json        # PHP dependencies
├── package.json         # Node dependencies
├── vite.config.js       # Vite build configuration
├── phpunit.xml          # PHPUnit configuration
└── README.md           # This file
```

## 📚 API Documentation

### Authentication

The API uses **Laravel Sanctum** for token-based authentication:

```bash
POST /api/login
POST /api/logout
POST /api/register
GET /api/user (requires token)
```

### Common Endpoints

View all available routes:

```bash
php artisan route:list
```

Filter by method, name, or path:

```bash
php artisan route:list --method=GET
php artisan route:list --name=users
php artisan route:list --path=api
```

### Making API Requests

All API requests require the `Authorization` header:

```
Authorization: Bearer {token}
```

Example with Axios:

```javascript
axios.get('/api/users', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
})
```

## 🌐 Deployment

### Using Laravel Cloud

The fastest way to deploy to production:

```bash
composer require laravel/cloud
php artisan cloud:deploy
```

See [Laravel Cloud Documentation](https://cloud.laravel.com/)

### Manual Deployment

1. **Clone repository on server**
   ```bash
   git clone <repository-url>
   cd kayora_backend
   ```

2. **Install dependencies**
   ```bash
   composer install --optimize-autoloader --no-dev
   npm install --production
   npm run build
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run migrations**
   ```bash
   php artisan migrate --force
   ```

5. **Set permissions**
   ```bash
   chmod -R 755 storage bootstrap/cache
   chmod -R 777 storage bootstrap/cache
   ```

6. **Start Octane**
   ```bash
   php artisan octane:start --server=swoole --workers=4 --host=0.0.0.0 --port=8000
   ```

### Environment-Specific Configuration

**Production (.env)**:
```env
APP_ENV=production
APP_DEBUG=false
CACHE_STORE=redis
SESSION_DRIVER=cookie
QUEUE_CONNECTION=redis
```

**Staging (.env)**:
```env
APP_ENV=staging
APP_DEBUG=true
CACHE_STORE=redis
```

## 🤝 Contributing

1. Create a feature branch
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. Make your changes following the code style

3. Run tests and code formatter
   ```bash
   composer run test
   vendor/bin/pint
   ```

4. Commit your changes
   ```bash
   git commit -m "Add your feature description"
   ```

5. Push to the branch
   ```bash
   git push origin feature/your-feature-name
   ```

6. Create a Pull Request

### Code Style

This project uses **Laravel Pint** for code formatting:

```bash
vendor/bin/pint
```

Fix formatting issues automatically:

```bash
vendor/bin/pint --dirty
```

## 🔧 Troubleshooting

### Common Issues

#### "Unable to locate file in Vite manifest"

Run frontend build:
```bash
npm run build
```

Or start development server:
```bash
npm run dev
```

#### Database connection error

1. Verify `.env` database configuration
2. Ensure MySQL is running
3. Create database manually if needed:
   ```bash
   mysql -u root -p
   CREATE DATABASE kayora_backend;
   ```

#### Permission denied errors

Fix file permissions:
```bash
chmod -R 755 storage bootstrap/cache
```

#### Composer/npm lock conflicts

Clear cache and reinstall:
```bash
rm -rf vendor node_modules
composer install
npm install
```

#### Queue not processing jobs

Check queue configuration and listener:
```bash
php artisan queue:listen
```

Monitor with Pail:
```bash
php artisan pail
```

### Debugging

View application logs:
```bash
php artisan pail
```

Clear application cache:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:cache
```

Run Tinker for interactive debugging:
```bash
php artisan tinker
```

## 💬 Support

For issues, bugs, or feature requests:

1. Check existing [documentation](https://laravel.com/docs/12)
2. Review [Laravel community forums](https://laracasts.com)
3. Open an issue in the repository

## 📄 License

This project is licensed under the MIT License. See the LICENSE file for details.

---

**Happy Coding! 🚀**

For more information, visit:
- [Laravel Documentation](https://laravel.com/docs/12)
- [Laravel Octane Documentation](https://laravel.com/docs/12/octane)
- [Pest PHP Documentation](https://pestphp.com)
