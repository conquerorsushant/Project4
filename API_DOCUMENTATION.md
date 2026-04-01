# Laravel Product API - Setup & Documentation

## Project Overview
A Laravel 10 REST API backend for managing products with full CRUD operations.

---

## Prerequisites
- PHP >= 8.1
- Composer
- MySQL (XAMPP)
- Postman or any API testing tool

---

## Installation Steps

### Step 1: Laravel Project Already Created ✓
The project has been created using:
```bash
composer create-project laravel/laravel .
```

### Step 2: Configure Database
The `.env` file is already configured with XAMPP MySQL settings:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=project3
DB_USERNAME=root
DB_PASSWORD=
```

**IMPORTANT**: Make sure to create the `project3` database in MySQL:
1. Start XAMPP (Apache & MySQL)
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Create new database named `project3`

### Step 3: Install Dependencies (if needed)
```bash
composer install
```

### Step 4: Generate Application Key (if needed)
```bash
php artisan key:generate
```

### Step 5: Run Database Migrations
This will create the `products` table with all required fields:
```bash
php artisan migrate
```

Expected output:
```
Migration table created successfully.
Migrating: 2019_12_14_000001_create_personal_access_tokens_table
Migrated:  2019_12_14_000001_create_personal_access_tokens_table
Migrating: 2026_03_30_112629_create_products_table
Migrated:  2026_03_30_112629_create_products_table
```

### Step 6: Start Development Server
```bash
php artisan serve
```

The API will be available at: `http://127.0.0.1:8000`

---

## Project Structure

### Files Created/Modified

#### 1. **Migration File**
- Location: `database/migrations/2026_03_30_112629_create_products_table.php`
- Creates `products` table with fields: id, name, description, price, timestamps

#### 2. **Product Model**
- Location: `app/Models/Product.php`
- Defines fillable fields: name, description, price
- Casts price as integer

#### 3. **Form Request Validation**
- `app/Http/Requests/StoreProductRequest.php` - Validates product creation
- `app/Http/Requests/UpdateProductRequest.php` - Validates product updates

**Validation Rules:**
- **name**: required, string, max 255 characters
- **description**: optional, string
- **price**: required, integer, minimum 0

#### 4. **API Resource**
- Location: `app/Http/Resources/ProductResource.php`
- Provides consistent JSON response format

#### 5. **Product Controller**
- Location: `app/Http/Controllers/ProductController.php`
- Implements all CRUD operations with proper error handling

#### 6. **API Routes**
- Location: `routes/api.php`
- Uses `apiResource` for RESTful routing

#### 7. **Exception Handler**
- Location: `app/Exceptions/Handler.php`
- Custom error handling for API responses

#### 8. **Environment Configuration**
- Location: `.env`
- MySQL database configuration

---

## API Endpoints

All endpoints are prefixed with `/api`

### 1. Get All Products
**Endpoint:** `GET /api/products`

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Product 1",
      "description": "Description here",
      "price": 1000,
      "created_at": "2026-03-30 12:00:00",
      "updated_at": "2026-03-30 12:00:00"
    }
  ]
}
```

### 2. Get Single Product
**Endpoint:** `GET /api/products/{id}`

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Product 1",
    "description": "Description here",
    "price": 1000,
    "created_at": "2026-03-30 12:00:00",
    "updated_at": "2026-03-30 12:00:00"
  }
}
```

**Error (404):**
```json
{
  "message": "Resource not found"
}
```

### 3. Create Product
**Endpoint:** `POST /api/products`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "name": "New Product",
  "description": "Product description",
  "price": 1500
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": 2,
    "name": "New Product",
    "description": "Product description",
    "price": 1500,
    "created_at": "2026-03-30 12:30:00",
    "updated_at": "2026-03-30 12:30:00"
  }
}
```

**Validation Error (422):**
```json
{
  "message": "Validation failed",
  "errors": {
    "name": ["Product name is required"],
    "price": ["Product price is required"]
  }
}
```

### 4. Update Product
**Endpoint:** `PUT /api/products/{id}` or `PATCH /api/products/{id}`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "name": "Updated Product",
  "price": 2000
}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Updated Product",
    "description": "Original description",
    "price": 2000,
    "created_at": "2026-03-30 12:00:00",
    "updated_at": "2026-03-30 13:00:00"
  }
}
```

### 5. Delete Product
**Endpoint:** `DELETE /api/products/{id}`

**Response:**
```json
{
  "message": "Product deleted successfully"
}
```

---

## Testing with Postman

### 1. Create Product (POST)
- URL: `http://127.0.0.1:8000/api/products`
- Method: `POST`
- Headers:
  - `Content-Type: application/json`
  - `Accept: application/json`
- Body (raw JSON):
```json
{
  "name": "Laptop",
  "description": "High performance laptop",
  "price": 75000
}
```

### 2. Get All Products (GET)
- URL: `http://127.0.0.1:8000/api/products`
- Method: `GET`

### 3. Get Single Product (GET)
- URL: `http://127.0.0.1:8000/api/products/1`
- Method: `GET`

### 4. Update Product (PUT)
- URL: `http://127.0.0.1:8000/api/products/1`
- Method: `PUT`
- Headers:
  - `Content-Type: application/json`
  - `Accept: application/json`
- Body (raw JSON):
```json
{
  "name": "Updated Laptop",
  "price": 80000
}
```

### 5. Delete Product (DELETE)
- URL: `http://127.0.0.1:8000/api/products/1`
- Method: `DELETE`

---

## Testing with cURL

### Create Product
```bash
curl -X POST http://127.0.0.1:8000/api/products \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"name\":\"Laptop\",\"description\":\"High performance laptop\",\"price\":75000}"
```

### Get All Products
```bash
curl -X GET http://127.0.0.1:8000/api/products \
  -H "Accept: application/json"
```

### Get Single Product
```bash
curl -X GET http://127.0.0.1:8000/api/products/1 \
  -H "Accept: application/json"
```

### Update Product
```bash
curl -X PUT http://127.0.0.1:8000/api/products/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"name\":\"Updated Laptop\",\"price\":80000}"
```

### Delete Product
```bash
curl -X DELETE http://127.0.0.1:8000/api/products/1 \
  -H "Accept: application/json"
```

---

## Database Schema

### Products Table
```sql
CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
);
```

---

## Error Handling

The API provides consistent error responses:

### Validation Error (422)
```json
{
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### Not Found (404)
```json
{
  "message": "Resource not found"
}
```

### Server Error (500)
```json
{
  "message": "An error occurred"
}
```

---

## Artisan Commands Reference

### View All Routes
```bash
php artisan route:list
```

### View API Routes Only
```bash
php artisan route:list --path=api
```

### Clear Cache (if needed)
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Run Migrations
```bash
php artisan migrate
```

### Rollback Migrations
```bash
php artisan migrate:rollback
```

### Fresh Migration (drop all tables and re-migrate)
```bash
php artisan migrate:fresh
```

---

## Code Best Practices Implemented

1. ✅ **Form Request Validation** - Separates validation logic from controllers
2. ✅ **API Resources** - Consistent JSON response formatting
3. ✅ **Route Model Binding** - Automatic model retrieval and 404 handling
4. ✅ **Custom Exception Handling** - Proper error responses for APIs
5. ✅ **Fillable Properties** - Protection against mass assignment vulnerabilities
6. ✅ **Type Hinting** - All methods have proper return types
7. ✅ **Comments** - Well-documented code explaining each step
8. ✅ **RESTful Conventions** - Standard HTTP methods and status codes

---

## Troubleshooting

### Problem: Database connection error
**Solution:**
- Make sure XAMPP MySQL is running
- Verify database `project3` exists
- Check `.env` database credentials

### Problem: 500 Internal Server Error
**Solution:**
- Check `storage/logs/laravel.log` for detailed errors
- Ensure proper file permissions: `chmod -R 775 storage bootstrap/cache`
- Run `php artisan config:clear`

### Problem: Class not found
**Solution:**
- Run `composer dump-autoload`
- Clear cache: `php artisan cache:clear`

### Problem: Migration already exists
**Solution:**
- Check `migrations` table in database
- Run `php artisan migrate:status` to see migration status

---

## Quick Start Commands (Summary)

```bash
# 1. Create database 'project3' in phpMyAdmin

# 2. Run migrations
php artisan migrate

# 3. Start server
php artisan serve

# 4. Test API
curl http://127.0.0.1:8000/api/products
```

---

## Next Steps (Optional Enhancements)

- Add authentication with Laravel Sanctum
- Implement pagination for product listing
- Add search and filtering capabilities
- Create seeders for sample data
- Add API rate limiting
- Implement soft deletes
- Add product categories
- Upload product images

---

## Support

For Laravel documentation: https://laravel.com/docs/10.x

---

**Project Created:** March 30, 2026
**Laravel Version:** 10.x
**PHP Version:** 8.1+
