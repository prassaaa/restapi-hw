# Library Management System - REST API

A comprehensive REST API for managing a library system built with Laravel 12 and React frontend.

## Features

- **Authentication** - Login and registration using Laravel Sanctum
- **Book Categories** - CRUD operations for book categories
- **Books Management** - Complete book management with search and filtering
- **Borrowing System** - Borrow and return books with business logic
- **Borrowed Books List** - Track all borrowed books with advanced filtering

## Tech Stack

- **Backend:** Laravel 12
- **Frontend:** React with Inertia.js
- **Authentication:** Laravel Sanctum (API tokens)
- **Database:** MySQL/PostgreSQL
- **Testing:** PHPUnit (84 tests passing)

## Installation

1. Clone the repository
```bash
git clone https://github.com/prassaaa/restapi-hw.git
cd restapi-hw
```

2. Install dependencies
```bash
composer install
npm install
```

3. Setup environment
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure database in `.env`
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=library_db
DB_USERNAME=root
DB_PASSWORD=
```

5. Run migrations
```bash
php artisan migrate
```

6. Start the server
```bash
php artisan serve
npm run dev
```

## API Endpoints

### Authentication
- `POST /api/login` - Login
- `POST /api/register` - Register

### Categories
- `GET /api/categories` - List all categories
- `POST /api/categories` - Create category
- `GET /api/categories/{id}` - Get category
- `PUT /api/categories/{id}` - Update category
- `DELETE /api/categories/{id}` - Delete category

### Books
- `GET /api/books` - List all books (with search & filter)
- `POST /api/books` - Create book
- `GET /api/books/{id}` - Get book
- `PUT /api/books/{id}` - Update book
- `DELETE /api/books/{id}` - Delete book

### Borrowing
- `GET /api/borrowed-books` - List borrowed books (with filters)
- `POST /api/books/{id}/borrow` - Borrow a book
- `POST /api/books/{id}/return` - Return a book

## API Usage Examples

### Get API Token
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

### List Books
```bash
curl http://localhost:8000/api/books \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Borrow a Book
```bash
curl -X POST http://localhost:8000/api/books/1/borrow \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"due_days":14,"notes":"Need for research"}'
```

## Business Rules

- Maximum 5 books can be borrowed per user
- Default borrow period is 14 days
- Cannot borrow unavailable books
- Cannot delete categories with associated books
- Cannot delete books with active borrows
- Books automatically marked as overdue after due date

## Testing

Run all tests:
```bash
php artisan test
```

Run specific test suite:
```bash
php artisan test --filter=CategoryTest
php artisan test --filter=BookTest
php artisan test --filter=BorrowTest
```

**Test Coverage:** 84 tests, 324 assertions - All passing ✅

## Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   ├── ApiController.php
│   │   ├── CategoryController.php
│   │   ├── BookController.php
│   │   └── BorrowController.php
│   ├── Requests/
│   └── Resources/
├── Models/
│   ├── BookCategory.php
│   ├── Book.php
│   └── BookBorrow.php
└── Traits/
    └── ApiResponse.php

tests/Feature/Api/
├── ApiInfrastructureTest.php
├── CategoryTest.php
├── BookTest.php
├── BorrowTest.php
├── BorrowedBooksTest.php
└── ReturnTest.php
```
