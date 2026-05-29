<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\InstallmentController;
use App\Http\Controllers\Api\UserDashboardController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\AdminUpdateController;
use App\Http\Controllers\UnitImageController;
use App\Http\Controllers\Api\UnitUpdateController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ClientDeviceController;


//Notifications Routes

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/notifications', [NotificationController::class, 'index']);

    Route::post('/notifications/read/{id}', [NotificationController::class, 'markAsRead']);

    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    Route::post('/save-device-token', [NotificationController::class, 'saveDeviceToken']);
});


Route::post('/v1/client/save-device-token', 
    [ClientDeviceController::class, 'saveDeviceToken']
);
// ============ Routes للمستخدمين (العملاء) الأساسية ============
Route::post('client/login', [AuthController::class, 'clientLogin']);
Route::post('client/register', [AuthController::class, 'clientRegister']);

// Routes محمية للمستخدم العادي
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
    Route::get('user/dashboard', [UserDashboardController::class, 'dashboard']);
    Route::post('comments', [CommentController::class, 'addComment']);
});



// Routes خاصة بالأدمن (إدارة كاملة)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('comments', [CommentController::class, 'getCommentsForAdmin']);
    Route::put('comments/{id}/read', [CommentController::class, 'markAsRead']);
    Route::post('unit-updates', [AdminUpdateController::class, 'addUpdate']);
    Route::get('unit-updates/{unitId}', [AdminUpdateController::class, 'getUnitUpdates']);
});

// ============ Prefix v1 (لجميع API's) ============
Route::prefix('v1')->group(function () {

 Route::get('units/{unitId}/updates', [UnitUpdateController::class, 'index']);
    Route::post('unit-updates', [UnitUpdateController::class, 'store']);
    Route::delete('unit-updates/{id}', [UnitUpdateController::class, 'destroy']);

    // اختبار
    Route::get('test', function () {
        return response()->json(['message' => 'API is working']);
    });


    Route::get('units/{unitId}/updates', [UnitUpdateController::class, 'index']);

    // إضافة مرحلة جديدة
    Route::post('unit-updates', [UnitUpdateController::class, 'store']);

Route::get('/clients/export-csv', [ClientController::class, 'exportCsv']);
Route::post('/clients/import-csv', [ClientController::class, 'importCsv']);

    // Public Routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/client/login', [AuthController::class, 'clientLogin']);
    Route::post('/client/register', [AuthController::class, 'clientRegister']);

    // Protected Routes (للمستخدم المسجل سواء أدمن أو عميل)
    Route::middleware('auth:sanctum')->group(function () {

        // معلومات المستخدم الحالي
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/client/logout', [AuthController::class, 'clientLogout']);

        // Dashboard الإحصائيات (للوحة تحكم الأدمن)
        Route::get('/dashboard/stats', [UserDashboardController::class, 'stats']);

        // إدارة العملاء (CRUD كامل)
Route::post('/clients', [ClientController::class, 'store']);
Route::get('/clients', [ClientController::class, 'index']);
Route::get('/clients/{id}', [ClientController::class, 'show']);
Route::put('/clients/{id}', [ClientController::class, 'update']);
Route::delete('/clients/{id}', [ClientController::class, 'destroy']);
Route::get('/clients/{id}/units', [ClientController::class, 'getClientUnits']);

// Client-Unit (linking)
Route::post('/client-unit', [ClientController::class, 'linkToUnit']);
Route::get('/clients/{clientId}/client-units', [ClientController::class, 'getClientUnits']);

        // إدارة الوحدات (CRUD كامل + إضافات)
        Route::apiResource('units', UnitController::class);
        Route::get('/units/{id}/updates', [UnitController::class, 'getUnitUpdates']);
        Route::get('/units/{id}/images', [UnitImageController::class, 'getUnitImages']);
        Route::post('/unit-images', [UnitImageController::class, 'upload']);


        // إدارة الأقساط (CRUD كامل + دفع)
        Route::apiResource('installments', InstallmentController::class);
        Route::post('/installments/{id}/pay', [InstallmentController::class, 'pay']);
    });
});