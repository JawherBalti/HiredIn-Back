<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\JobOfferController;
use App\Http\Controllers\ResumeController;

// Route::middleware('auth:sanctum')->get('/user', function(Request $request) {
//     return $request->user();
// });

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->get('/companies', [CompanyController::class, 'index']);
Route::middleware('auth:sanctum')->get('/companies/current-user-companies', [CompanyController::class, 'getCurrentUserCompanies']);
Route::middleware('auth:sanctum')->post('/companies/createCompany', [CompanyController::class, 'store']);

Route::get('/job-offers', [JobOfferController::class, 'index']);
Route::get('/job-offers/{id}', [JobOfferController::class, 'getOneJobOffer']);
Route::middleware('auth:sanctum')->post('/job-offers/createJob', [JobOfferController::class, 'store']);
Route::middleware('auth:sanctum')->post('/job-offers/update/{jobOffer}', [JobOfferController::class, 'update']);



    // Apply to job
    Route::middleware('auth:sanctum')->post('/job-offers/{jobOffer}/apply', [ResumeController::class, 'store']);
    Route::middleware('auth:sanctum')->get('/job-offers/user/applications', [ResumeController::class, 'getUserApplications']);
    
    // Get applications for a job
    Route::get('/job-offers/{jobOffer}/applications', [ResumeController::class, 'index']);
    
    // Download resume
    Route::get('/job-offers/resumes/{resume}/download', [ResumeController::class, 'download']);
    
    Route::patch('/job-offers/resumes/{resume}/status', [ResumeController::class, 'updateStatus']);
    
    // New notification routes
    Route::get('/notifications', function (Request $request) {
        return response()->json([
            'unread' => $request->user()->unreadNotifications,
            'read' => $request->user()->readNotifications
        ]);
    });

    Route::post('/notifications/mark-as-read', function (Request $request) {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'Notifications marked as read']);
    });
    
    Route::post('/notifications/{id}/read', function (Request $request, $id) {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        
        if ($notification) {
            $notification->markAsRead();
            return response()->json(['message' => 'Notification marked as read']);
        }
        
        return response()->json(['message' => 'Notification not found'], 404);
    });

Broadcast::routes(['middleware' => ['auth:sanctum']]);