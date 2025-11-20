<?php

use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BorrowController;
use App\Http\Controllers\Api\CategoryController;
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

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Book Categories
    Route::apiResource('categories', CategoryController::class);

    // Books
    Route::apiResource('books', BookController::class);

    // Borrow & Return
    Route::get('borrowed-books', [BorrowController::class, 'index']);
    Route::post('books/{book}/borrow', [BorrowController::class, 'borrow']);
    Route::post('books/{book}/return', [BorrowController::class, 'return']);
});

