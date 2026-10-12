<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| iCareDonate REST API Routes
|--------------------------------------------------------------------------
| Base URL: /api/v1
*/

Route::prefix('v1')->group(function () {
    // ── Public Endpoints ─────────────────────────────────
    Route::get('/public/home-summary', 'App\Http\Controllers\Api\PublicController@homeSummary');
    Route::get('/public/impact-stats', 'App\Http\Controllers\Api\PublicController@impactStats');
    Route::get('/categories', 'App\Http\Controllers\Api\CategoryController@index');
    Route::get('/donations', 'App\Http\Controllers\Api\DonationController@index');
    Route::get('/donations/{id}', 'App\Http\Controllers\Api\DonationController@show');
    Route::get('/faqs', 'App\Http\Controllers\Api\FaqController@index');
    Route::post('/contact-inquiries', 'App\Http\Controllers\Api\ContactController@store');

    // ── Authentication Endpoints ─────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/register', 'App\Http\Controllers\Api\AuthController@register');
        Route::post('/login', 'App\Http\Controllers\Api\AuthController@login');
        Route::post('/forgot-password', 'App\Http\Controllers\Api\PasswordResetController@sendResetLink');
        Route::post('/reset-password', 'App\Http\Controllers\Api\PasswordResetController@reset');
    });

    // ── Authenticated User & Admin Endpoints ─────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', 'App\Http\Controllers\Api\AuthController@logout');
        Route::get('/auth/me', 'App\Http\Controllers\Api\AuthController@me');

        // User Portal
        Route::prefix('user')->group(function () {
            Route::get('/dashboard', 'App\Http\Controllers\Api\UserDashboardController@index');
            Route::get('/profile', 'App\Http\Controllers\Api\UserProfileController@show');
            Route::put('/profile', 'App\Http\Controllers\Api\UserProfileController@update');
            Route::post('/change-password', 'App\Http\Controllers\Api\UserSecurityController@changePassword');
            Route::post('/privacy', 'App\Http\Controllers\Api\UserProfileController@updatePrivacy');
            Route::post('/region', 'App\Http\Controllers\Api\UserProfileController@updateRegion');
            
            Route::get('/donations', 'App\Http\Controllers\Api\DonationController@userDonations');
            Route::get('/requests', 'App\Http\Controllers\Api\RequestController@userRequests');
            Route::get('/claims', 'App\Http\Controllers\Api\ClaimController@userClaims');
            Route::get('/history', 'App\Http\Controllers\Api\TransactionController@userHistory');
        });

        Route::post('/donations', 'App\Http\Controllers\Api\DonationController@store');
        Route::put('/donations/{id}', 'App\Http\Controllers\Api\DonationController@update');
        Route::post('/requests', 'App\Http\Controllers\Api\RequestController@store');
        Route::get('/requests/{id}', 'App\Http\Controllers\Api\RequestController@show');
        Route::post('/claims/{id}/complete', 'App\Http\Controllers\Api\ClaimController@complete');

        Route::get('/notifications', 'App\Http\Controllers\Api\NotificationController@index');
        Route::get('/notifications/unread-count', 'App\Http\Controllers\Api\NotificationController@unreadCount');
        Route::patch('/notifications/{id}/read', 'App\Http\Controllers\Api\NotificationController@markAsRead');
        Route::post('/reports', 'App\Http\Controllers\Api\ProblemReportController@store');
        Route::post('/support/tickets', 'App\Http\Controllers\Api\SupportTicketController@store');

        // ── Admin Only Endpoints ─────────────────────────
        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::get('/dashboard-stats', 'App\Http\Controllers\Api\AdminDashboardController@stats');
            Route::get('/donations', 'App\Http\Controllers\Api\AdminDonationController@index');
            Route::post('/donations/{id}/decision', 'App\Http\Controllers\Api\AdminDonationController@decision');
            Route::delete('/donations/{id}', 'App\Http\Controllers\Api\AdminDonationController@destroy');

            Route::get('/requests', 'App\Http\Controllers\Api\AdminRequestController@index');
            Route::post('/requests/{id}/decision', 'App\Http\Controllers\Api\AdminRequestController@decision');
            Route::delete('/requests/{id}', 'App\Http\Controllers\Api\AdminRequestController@destroy');

            Route::post('/matches', 'App\Http\Controllers\Api\MatchController@store');
            Route::get('/claims', 'App\Http\Controllers\Api\AdminClaimController@index');
            Route::get('/transactions', 'App\Http\Controllers\Api\AdminTransactionController@index');

            Route::get('/users', 'App\Http\Controllers\Api\AdminUserController@index');
            Route::get('/users/{id}', 'App\Http\Controllers\Api\AdminUserController@show');
            Route::patch('/users/{id}/status', 'App\Http\Controllers\Api\AdminUserController@updateStatus');

            Route::get('/reports', 'App\Http\Controllers\Api\AdminReportController@index');
            Route::post('/reports/{id}/resolve', 'App\Http\Controllers\Api\AdminReportController@resolve');

            Route::apiResource('/categories', 'App\Http\Controllers\Api\AdminCategoryController');
            Route::apiResource('/faqs', 'App\Http\Controllers\Api\AdminFaqController');
            Route::get('/inquiries', 'App\Http\Controllers\Api\AdminInquiryController@index');
            Route::post('/inquiries/{id}/reply', 'App\Http\Controllers\Api\AdminInquiryController@reply');

            Route::get('/admins', 'App\Http\Controllers\Api\AdminManagementController@index');
            Route::post('/invitations', 'App\Http\Controllers\Api\AdminInvitationController@store');
            Route::get('/audit-logs', 'App\Http\Controllers\Api\AuditLogController@index');
        });
    });
});
