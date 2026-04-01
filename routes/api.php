<?php

use App\Http\Controllers\CustomerDetailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



/*
|--------------------------------------------------------------------------
| Customer Details API Routes
|--------------------------------------------------------------------------
|
| apiResource automatically creates the following routes:
| GET    /api/customer-details       - index()   - List all customer details
| POST   /api/customer-details       - store()   - Create new customer detail
| GET    /api/customer-details/{id}  - show()    - Show single customer detail
| PUT    /api/customer-details/{id}  - update()  - Update customer detail
| DELETE /api/customer-details/{id}  - destroy() - Delete customer detail
|
*/
Route::apiResource('customer-details', CustomerDetailController::class);
