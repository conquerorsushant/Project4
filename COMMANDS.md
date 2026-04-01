# Quick Commands Reference

## Initial Setup (Already Done) ✓

```bash
# 1. Laravel project created
composer create-project laravel/laravel .

# 2. Database configured in .env
# DB_DATABASE=project3

# 3. Migrations run successfully
php artisan migrate
```

---

## Essential Commands to Run NOW

### 1. Start the Development Server
```bash
php artisan serve
```
**Server will run at:** http://127.0.0.1:8000

---

## API Testing Commands (cURL)

### Create a Product
```bash
curl -X POST http://127.0.0.1:8000/api/products -H "Content-Type: application/json" -H "Accept: application/json" -d "{\"name\":\"Laptop\",\"description\":\"Gaming laptop\",\"price\":85000}"
```

### Get All Products
```bash
curl -X GET http://127.0.0.1:8000/api/products -H "Accept: application/json"
```

### Get Single Product (ID: 1)
```bash
curl -X GET http://127.0.0.1:8000/api/products/1 -H "Accept: application/json"
```

### Update Product (ID: 1)
```bash
curl -X PUT http://127.0.0.1:8000/api/products/1 -H "Content-Type: application/json" -H "Accept: application/json" -d "{\"name\":\"Updated Laptop\",\"price\":90000}"
```

### Delete Product (ID: 1)
```bash
curl -X DELETE http://127.0.0.1:8000/api/products/1 -H "Accept: application/json"
```

---

## Postman Testing URLs

Base URL: `http://127.0.0.1:8000`

- **GET**    `/api/products` - List all products
- **POST**   `/api/products` - Create product
- **GET**    `/api/products/1` - Get product by ID
- **PUT**    `/api/products/1` - Update product
- **DELETE** `/api/products/1` - Delete product

**Headers Required:**
- Content-Type: application/json
- Accept: application/json

---

## Useful Laravel Commands

### View All API Routes
```bash
php artisan route:list --path=api
```

### Check Migration Status
```bash
php artisan migrate:status
```

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
```

### Rollback Last Migration
```bash
php artisan migrate:rollback
```

### Fresh Migration (Drop all & Re-run)
```bash
php artisan migrate:fresh
```

---

## Generated Artisan Commands (Already Executed) ✓

```bash
# Migration
php artisan make:migration create_products_table

# Model
php artisan make:model Product

# Requests
php artisan make:request StoreProductRequest
php artisan make:request UpdateProductRequest

# Resource
php artisan make:resource ProductResource

# Controller
php artisan make:controller ProductController --api
```

---

## Project File Structure

```
Project4/
├── app/
│   ├── Exceptions/
│   │   └── Handler.php                    # Custom error handling
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── ProductController.php      # CRUD operations
│   │   ├── Requests/
│   │   │   ├── StoreProductRequest.php    # Create validation
│   │   │   └── UpdateProductRequest.php   # Update validation
│   │   └── Resources/
│   │       └── ProductResource.php        # JSON formatting
│   └── Models/
│       └── Product.php                    # Product model
├── database/
│   └── migrations/
│       └── 2026_03_30_112629_create_products_table.php
├── routes/
│   └── api.php                            # API routes
├── .env                                   # Database config
├── API_DOCUMENTATION.md                   # Full documentation
└── COMMANDS.md                            # This file
```

---

## Next Step

**Start the server and test:**
```bash
php artisan serve
```

Then open Postman or use cURL to test the API endpoints!
