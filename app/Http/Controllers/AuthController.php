<?php

namespace App\Http\Controllers;

use App\Http\Controllers\CampusReach\DashboardController;
use App\Http\Requests\LoginUserRequest;
use App\Models\Channel;

// use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Models\Institution;
use App\Models\SubChannel;
use App\Models\PendingAdmin;
use App\Notifications\forgotPasswordNotification;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\Validator as Validate;
use Illuminate\Support\Facades\DB;
use App\Notifications\VerifyEmailNotification;
use App\Http\Controllers\InviteAdminController;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\PostsResource;
use App\Http\Resources\UserResource;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use HttpResponses;

    protected $cradc;
    protected $inviteAdminController;

    public function __construct(DashboardController $dashboardController, InviteAdminController $inviteAdminController)
    {
        $this->cradc = $dashboardController;
        $this->inviteAdminController = $inviteAdminController;
    }

    /**
     * @OA\Post(
     * path="/api/login",
     * operationId="userLogin",
     * tags={"Authentication"},
     * summary="Logins a user",
     * description="Return User Data",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"email", "password", "device_token"},
     *               @OA\Property(property="email", type="email"),
     *               @OA\Property(property="password", type="password"),
     *		        @OA\Property(property="device_token", type="text")
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Login Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Login Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found", @OA\JsonContent()),
     * )
     */

    public function login(LoginUserRequest $request)
    {
        $request->validated($request->all());
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error(null, 'Credentials Invalid', 422);
        }

        // Convert the subscribed_channel data into an array
        $subscribedChannels = is_null($user->channels_subscribed) ? [] : json_decode($user->channels_subscribed, true);
        $subscribedSubChannels = is_null($user->subchannels_subscribed) ? [] : json_decode($user->subchannels_subscribed, true);

        // check device_token is same
        $userToken = trim(strtolower($user->device_token));
        $requestToken = trim(strtolower($request->device_token));
        if ($requestToken !== $userToken) {
            $user->device_token = $request->device_token;
            $user->save();
            $firebase = new FirebaseController();
            if (!$firebase->unsubscribedFromAllTopics($userToken)) {
                return $this->error([], 'Something went wrong!', 400);
            }
            // perform query to channels and subchannels against the user list of channels and subchannels subscribed
            $channels = !empty($subscribedChannels) ? Channel::whereIn('id', $subscribedChannels)->get() : collect();
            $subchannels = !empty($subscribedSubChannels) ? SubChannel::whereIn('id', $subscribedSubChannels)->get() : collect();

            // Combine topic names into a single array
            $topics = $channels->pluck('topic_name')->merge($subchannels->pluck('topic_name'));

            if ($topics->isNotEmpty()) {
                if (!$firebase->subscribeToTopics($topics->toArray(), $request->device_token)) {
                    return $this->error([], 'Something went wrong!', 400);
                }
            }
        }

        $institution = $user->institution;
        $managed = $this->getChannelsManaged($user->id);
        $userResource = UserResource::make($user);

        return $this->success([
            'user' => $userResource,
            'institution' => $institution ? [
                'id' => $institution->id,
                'name' => $institution->name,
                'website' => $institution->website ?? null
            ] : null,
            'channel_managed' => $managed[0],
            'subchannel_managed' => $managed[1],
            'token' => $user->createToken('mobile')->plainTextToken
        ]);
    }

    /**
     * @OA\Post(
     * path="/api/register",
     * operationId="userRegister",
     * tags={"Authentication"},
     * summary="Registers a new User",
     * description="Creates new user and returns token",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"firstname", "lastname", "user_type","email", "password", "username", "provider", "device_token"},
     *               @OA\Property(property="firstname", type="text"),
     *               @OA\Property(property="lastname", type="text"),
     *               @OA\Property(property="user_type", type="text"),
     *               @OA\Property(property="email", type="email"),
     *               @OA\Property(property="password", type="password"),
     *               @OA\Property(property="username", type="text"),
     *		        @OA\Property(property="provider", type="text"),
     *		        @OA\Property(property="device_token", type="text")
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Register Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Register Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Credentials Invalid",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found", @OA\JsonContent()),
     * )
     */

    public function register(StoreUserRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $validatedData['password'] = Hash::make($validatedData['password']);
            $validatedData['tracking_id'] = Str::random(10);

            $user = User::create($validatedData);
            $user->notify(new VerifyEmailNotification($user));
            $token = $user->createToken('mobile')->plainTextToken;

            $requestAccepted = null;
            $pending_admin = PendingAdmin::where('email', $user->email)->first();
            $pending_admin ? $requestAccepted = $this->inviteAdminController->makeAdmin($user->email):null;

            $userRefresh = $user->refresh();
            $userPrimaryInstitutionId = $user->primary_institution_id;
            $institution = Institution::find($userPrimaryInstitutionId);
            $userResource = UserResource::make($userRefresh);
            $managed = $this->getChannelsManaged($user->id);

            return $this->success([
                'user' => $userResource,
                'institution' => $institution ? [
                    'id' => $institution->id,
                    'name' => $institution->name,
                ] : null,
                'token' => $token,
                'channel_managed' => $managed[0],
                'subchannel_managed' => $managed[1],
                'adminRequestAccepted' => $requestAccepted
            ], 'Account registered!', 201);
        } catch (\Throwable $th) {
            Log::error("Register failed: $th");
            return $this->error(null, $th->getMessage(), 400);
        }
    }

    private function getChannelsManaged($id)
    {
        $checkChannelsManaged = Channel::where('super_admin_id', $id)->get();
        $checkSubChannelsManaged = SubChannel::where('admin_id', $id)->get();

        return [
            $checkChannelsManaged->isEmpty() ? null : $checkChannelsManaged->pluck('id'), 
            $checkSubChannelsManaged->isEmpty() ? null : $checkSubChannelsManaged->pluck('id'),
        ];
    }

    /**
     * @OA\Post(
     * path="/api/logout",
     * operationId="userLogout",
     * tags={"Authentication"},
     * summary="Logs out a user",
     * description="Destroys user token",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="number")
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Logout Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Logout Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function logout()
    {
        try {
            $user = request()->user();
            $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();

            return $this->success(null, 'Logged out!');
        } catch (\Throwable $th) {
            return $this->error(null, $th->getMessage(), 422);
        }
    }

    /**
     * @OA\Post(
     * path="/api/forgot-password",
     * operationId="forgotPassword",
     * tags={"Authentication"},
     * summary="Sends a email to the user",
     * description="sends email that contains newly updated password for the user",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"email"},
     *               @OA\Property(property="email", type="email"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="An email has been sent to you!",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="An email has been sent to you!",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found", @OA\JsonContent()),
     * )
     */

    public function forgotPassword(Request $request) //sends mail of the password to the user
    {
        try {
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return $this->error(null, 'Unable to process request!', 404);
            }
            $user->notify(new forgotPasswordNotification($user));
            return $this->success(null, 'An email has been sent to you!');
        } catch(\Throwable $th) {
            Log::error("forgot-password controller error: $th");
            return $this->error(null, $th->getMessage(), 422);
        }
    }

    /**
     * @OA\Post(
     * path="/api/user/password-reset",
     * operationId="changeGeneratedForgotPassword",
     * tags={"Authentication"},
     * summary="Changes the password",
     * description="Updates user password or changes the password",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"user_id", "password", "new_password"},
     *               @OA\Property(property="user_id", type="text"),
     *               @OA\Property(property="password", type="password"),
     *               @OA\Property(property="new_password", type="password"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Password changed successfully!",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Password changed successfully!",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Password invalid!",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="An entry is required!", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found", @OA\JsonContent()),
     * )
     */

    public function changeGeneratedForgotPassword(Request $request) //changes the password sent to the user and also changes password for user
    {
        $validatedData = $request->validate([
            'user_id' => 'required',
            'password' => 'required|string',
            'new_password' => 'required|string|min:8'
        ]);
        $user = User::findOrFail($validatedData['user_id']);
        if (empty($validatedData['password']) || empty($validatedData['new_password'])) return $this->error(null, 'An entry is required!', 400);
        if (!Hash::check($validatedData['password'], $user->password)) return $this->error(null, 'Password invalid!', 422);
        $hashedPassword = Hash::make($validatedData['new_password']);
        if ($user->reset_password == true) {
            $user->update([
                'reset_password' => false,
                'password' => $hashedPassword,
            ]);
            return $this->success([
                'user' => $user->refresh(),
            ], 'Password changed successfully!');
        }
        $user->update([
            'password' => $hashedPassword,
        ]);

        return $this->success([
            'user' => $user->refresh(),
        ], 'Password changed successfully!');
    }

    public function adminLogin(LoginUserRequest $request)
    {
        $request->validated($request->all());

        $user = User::where('email', $request->email)->first();

        // Token-based login check
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error(null, 'Credentials Invalid', 422);
        }

        $institution = $this->cradc->getAdminInstitution($user->primary_institution_id);

        $token = $user->createToken('web_admin')->plainTextToken;

        return $this->success([
            'user' => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'username' => $user->username,
                'user_type' => $user->user_type,
                'email_verified_at' => $user->email_verified_at,
                'primary_institution_id' => (int)$user->primary_institution_id,
                'reset_password' => $user->reset_password,
            ],
            'institution' => $institution ? [
                'id' => $institution->id,
                'name' => $institution->name,
            ] : null,
            'token' => $token,
        ]);
    }

    public function crAdminHomeSectionData(User $user)
    {
      try {
        $posts = $this->cradc->getAdminInstitutionPosts($user->id, $user->primary_institution_id);
        $managedChannels = $this->cradc->getChannelsManaged($user->id);
        $campusPopulation = $this->cradc->getCampusPopulation($user->primary_institution_id);
        $subAdmins = $this->cradc->getSubAdminsUnderAdminChannels($user->id);

        $channelIds = $managedChannels[0]->pluck('id')->toArray();
        $subChannelIds = $managedChannels[1]->pluck('id')->toArray();
        $pendingAdmins = $this->cradc->getPendingAdmins($channelIds, $subChannelIds);

        $institution = $this->cradc->getAdminInstitution($user->primary_institution_id);
        $activities = $this->cradc->getChannelActivityByInstitution($user->primary_institution_id);

        return $this->success([
            'amount_of_posts' => $posts['total'],
            'campus_population' => $campusPopulation,
            'channels_managed' => $managedChannels[0]->isEmpty() ? null : $managedChannels[0]->pluck('id'),
            'sub_channels_managed' => $managedChannels[1]->isEmpty() ? null : $managedChannels[1]->pluck('id'),
            'post_stats' => $posts['stats'],
            'sub_admins' => $subAdmins,
            'pending_admins' => $pendingAdmins,
            'institution' => $institution ? [
                'id' => $institution->id,
                'name' => $institution->name,
            ] : null,
            'activities' => $activities,
        ]);
      } catch(\Throwable $th) {
         Log::error("Fetch Admin data error $th");
         return $this->error(null, $th->getMessage(), 400);
      }
    }
}
