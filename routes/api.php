<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\checkUsernameController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\GetInTouchController;
use App\Http\Controllers\InviteAdminController;
use App\Http\Controllers\MailingListController;
use App\Http\Controllers\PendingAdminController;
use App\Http\Controllers\PostsController;
use App\Http\Controllers\searchController;
use App\Http\Controllers\subAdminController;
use App\Http\Controllers\SubChannelController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\TasksController;
use App\Http\Controllers\testRegisterController;
use App\Http\Controllers\updateUserDetailsController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Verify Email
Route::get('email/verify', function (Request $request) {

    $request->user()->sendEmailVerificationNotification();

    return response()->json(['message' => 'Verification link sent'], 200);
})->middleware(['auth:api', 'throttle:6,1'])->name('verification.send');

Route::get('email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return response()->json(['message' => 'Email verified'], 200);
})->middleware(['auth:api', 'signed', 'throttle:6,1'])->name('verification.verify');

Route::post('email/resend', function (Request $request) {
    if ($request->user()->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified'], 200);
    }

    $request->user()->sendEmailVerificationNotification();

    return response()->json(['message' => 'Verification link sent'], 200);
})->middleware(['auth:api', 'throttle:6,1'])->name('verification.resend');

// verify otp from user
Route::post('email/otp/verify', [EmailVerificationController::class, 'verifyOtpEmail'])->middleware(['auth:api', 'throttle:6,1']);

// resend otp to user email
Route::post('email/otp/resend', [EmailVerificationController::class, 'resendOtpVerification'])->middleware(['auth:api', 'throttle:6,1']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
   return $request->user();
});

// for swagger implementation
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/mailing-list', [MailingListController::class, 'store']);
Route::post('/usercheck', [checkUsernameController::class, 'userCheck']);
Route::post('/testregister', [testRegisterController::class, 'testRegister']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::get('/stress/posts', [PostsController::class, 'stressPostRequest']);

// campus reach admin routes
Route::post('/cra/login', [AuthController::class, 'adminLogin']);

// retrieve admin home data
Route::get('/cra/{user}/home', [AuthController::class, 'crAdminHomeSectionData'])->middleware(['auth:api', 'throttle:6,1']);
// send out alert for an institution
Route::post('/cra/alert', [TasksController::class, 'alertGroup'])->middleware(['auth:sanctum', 'throttle:6,1']);

// Route::post('/test-login', [testRegisterController::class, 'testLogin']);

// make invited user an admin
Route::get('/accepted/invite/{identifier}', [InviteAdminController::class, 'makeAdmin']);

// Protecting our routes from unauthorized requests
// Protected Routes
Route::group(['middleware' => ['auth:sanctum']], function () {

    //api to retrieve user managed channels and subchannels.
    Route::get('/user/{user_id}/managed/channels_subchannels', [TasksController::class, 'userManagedPlatforms']);

    // get institutions
    Route::get('/institution/get', [TasksController::class, 'retrieveInstitutions']);

    // reset user password
    Route::post('/user/password-reset', [AuthController::class, 'changeGeneratedForgotPassword']);

    //send admin invitation email
    Route::post('/email/invite/user', [InviteAdminController::class, 'sendAdminMail']);

    // send email - 'get in touch'
    Route::post('/email/notify', [GetInTouchController::class, 'getInTouchWithEmail']);

    // route endpoint for searching institution
    Route::get('/search/institution', [searchController::class, 'searchInstitution']);

    // route endpoint for searching channel
    Route::get('/search/channel', [searchController::class, 'searchChannel']);

    // update primary institution for users
    Route::post('/updatePrimaryInstitution', [updateUserDetailsController::class, 'updatePrimaryInstitution']);

    // get all posts
    Route::get('/post/posts', [PostsController::class, 'getAllPosts']);

    // get all posts by pagination
    Route::get('/post/posts/paginate', [PostsController::class, 'getPostsByPagination']);

    // increase post count view
    Route::post('/post/count-view', [PostsController::class, 'incrementPostCountView']);

    // get channel posts
    Route::get('/post/channel/{channel_id}/posts', [PostsController::class, 'getChannelPosts']);

    // get subchannel posts
    Route::get('/post/subchannel/{sub_channel_id}/posts', [PostsController::class, 'getSubchannelPosts']);

    // create new post
    Route::post('/post/create', [PostsController::class, 'createPost']);

    //to save post
    Route::post('/post/save', [PostsController::class, 'savePost']);

    // retrieve saved posts
    Route::get('/post/get/{user_id}/savedPosts', [PostsController::class, 'retrieveSavedPost']);

   //  retrieve a single post
   Route::get('/post/{post_id}', [PostsController::class, 'getOneSinglePost']);

    // toggle subscription to channel
    Route::post('/subscription', [SubscribeController::class, 'toggleChannelSubscription']);

    // toggle subscription to subchannel
    Route::post('/subscription/subchannel', [SubscribeController::class, 'toggleSubChannelSubscription']);

    // update a channel
    Route::post('/channel/update', [ChannelController::class, 'updateChannelDetails']);

    // get recommended channels
    Route::get('/channel/recommendation/{user_id}', [ChannelController::class, 'showRecommendedChannels']);

    //get a channel
    Route::get('/channel/{channel_id}', [ChannelController::class, 'showChannel']);

    //get channel(s) user subscribed to
    Route::get('/channel/subscribed/{user_id}', [ChannelController::class, 'channelSubscribedTo']);

    // create sub channel
    Route::post('/subchannel', [SubChannelController::class, 'store']);

    // update a sub channel
    Route::post('/subchannel/update', [SubChannelController::class, 'updateSubchannelDetails']);

    // get subscribed subchannels
    Route::get('/subchannel/subscribed/{user_id}', [SubChannelController::class, 'getUserSubscribedSubchannel']);

    //get subchannels attached to channel
    Route::get('/channel/subchannels/{user_id}/{channel_id}', [SubChannelController::class, 'index']);

    //get a sub channel
    Route::get('/channel/subchannel/{user_id}/{sub_channel_id}', [SubChannelController::class, 'show']);

    //suspend/unsuspend subchannel
    Route::post('/subchannel/suspension', [SubChannelController::class, 'suspendORunsuspend']);

    // get all recommended subchannels by user id
    Route::get('/subchannel/recommendation/{user_id}', [SubChannelController::class, 'showRecommendedSubchannels']);

    //get all subchannels by user id ,alternative route
    Route::get('/subchannel/subchannels/get/{user_id}', [SubChannelController::class, 'showSubchannels']);

    //get suspended subchannels attached to channel
    Route::get('/channel/subchannel/suspended/{user_id}/{channel_id}', [SubChannelController::class, 'getSusSubChans']);

    // get all subchannels managed by subadmin
    Route::get('/subadmin/{user_id}/{channel_id}', [subAdminController::class, 'getSubadminSubchannels']);

    // get all subadmins details
    Route::get('/subadmin/subadmins/{user_id}/{channel_id}', [subAdminController::class, 'getSubadmins']);

    //delete sub channel
    Route::post('/subchannel/delete', [SubChannelController::class, 'delete']);

    //send subchannel admin request (pending)
    Route::post('/adminPending', [PendingAdminController::class, 'sendAdminRequestEmailAndInsertPendingAdmin']);

    //send subchannel admin request (accepted)
    Route::post('/adminAccepted', [PendingAdminController::class, 'adminRequestAccepted']);

    //get deleted or removed subadmins
    Route::get('/channel/subadmins/{channel_id}/removed', [subAdminController::class, 'getDeletedSubadmins']);

    //get suspended subadmins
    Route::get('/channel/subadmins/suspended/{channel_id}', [subAdminController::class, 'getSuspendedSubadmins']);

    //get subchannel&channel managed by user
    Route::get('/channel_subchannel/subadmins/managed/{user_id}', [subAdminController::class, 'getChannelOrSubchannelManagedByUser']);

    //to suspend a subadmin
    Route::post('/channel/{channel_id}/subadmin/{admin_id}/suspend', [subAdminController::class, 'suspendSubadmin']);

    //to unsuspend a subadmin
    Route::post('/channel/{channel_id}/unsuspend/subadmin/{admin_id}/subchannel/{subchannel_id}', [subAdminController::class, 'unsuspendSubadmin']);

    // to log the user out
    Route::post('/logout', [AuthController::class, 'logout']);

    // to create bulk data for channels
    Route::post('/import-channels', [ChannelController::class, 'importChannels']);

    // to create bulk data for sub_channels
    Route::post('/subchannels/bulk', [SubChannelController::class, 'storeBulk']);
});
