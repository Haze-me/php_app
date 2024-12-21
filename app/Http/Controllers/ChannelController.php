<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Imports\ChannelsImport;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Traits\HttpResponses;
use App\Http\Resources\ChannelResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ChannelController extends Controller
{
    use HttpResponses;

    /**
     * @OA\Get(
     * path="/api/channel/subscribed/{user_id}",
     * operationId="channelSubscribedTo",
     * tags={"Channels"},
     * summary="Get User Channel Subscription",
     * description="Sends user subscribed channel(protected)",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"user_id"},
     *               @OA\Property(property="user_id", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Retrieved Subscribed Channel",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Retrieve Subscribed Channel",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Channel subscription exist not", @OA\JsonContent()),
     *      @OA\Response(response=403, description="Unauthorized", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function channelSubscribedTo($user_id) //get channels user subscribed to
    {

        try {
            $user = User::findOrFail($user_id);
            $subscribedChanIds = json_decode($user->channels_subscribed, true);

            // Check if the user has subscribed channels
            if (!empty($subscribedChanIds) && is_array($subscribedChanIds)) {
                $subscribedChans = Channel::whereIn('id', $subscribedChanIds)->get();
                $subscribedChansDetails = ChannelResource::collection($subscribedChans);

                return $this->success($subscribedChansDetails, 'User subscribed channels');
            } else {
                return $this->success(collect()); // Return empty collection if no subscribed channels
            }
        } catch (ModelNotFoundException $e) {
            return $this->error([], 'User not found', 404);
        }
    }

    /**
     * @OA\Get(
     * path="/api/channel/{channel_id}",
     * operationId="showChannel",
     * tags={"Channels"},
     * summary="Get Channel data",
     * description="Sends a channel data(protected)",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"channel_id"},
     *               @OA\Property(property="channel_id", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Retrieved Channel",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Retrieve Channel",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Channel exist not", @OA\JsonContent()),
     *      @OA\Response(response=403, description="Unauthorized", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function showChannel($channel_id) //get a channel
    {
        // Access the authenticated user
        $user = auth('api')->user();
        if (!$user) {
            return $this->error([], 'Unauthorized', 403);
        } else {
            try {
                $channel = Channel::findOrFail($channel_id);
                return new ChannelResource($channel);
            } catch (ModelNotFoundException $e) {
                return $this->error([], 'channel does not exist', 400);
            }
        }

    }

    /**
     * @OA\Get(
     * path="/api/channel/recommendation/{user_id}",
     * operationId="showRecommendedChannels",
     * tags={"Channels"},
     * summary="Get Channels",
     * description="Returns recommended channels that the user is not subscribed to(protected)",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"token"},
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Retrieved recommended Channels",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Retrieved recommended Channels",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Unauthorized", @OA\JsonContent()),
     *      @OA\Response(response=403, description="Unauthorized access", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function showRecommendedChannels($user_id) //get all channels
    {
        // Access the authenticated user
        if ($user_id != Auth::guard('sanctum')->id()) {
            return $this->error([], 'Unauthorized', 403);
        }
        try {
            $user = User::findOrFail($user_id);
            $subscribedChanIds = json_decode($user->channels_subscribed, true) ?: [];
            // Check if the user has subscribed channels
            if (!empty($subscribedChanIds) && is_array($subscribedChanIds)) {
                $recommendedChans = Channel::whereNotIn('id', $subscribedChanIds)->get();
                $recommendedChansDetails = ChannelResource::collection($recommendedChans);
                return $this->success($recommendedChansDetails, 'Recommended channels!');
            }
            $recommendedChans = Channel::get();
            $recommendedChansDetails = ChannelResource::collection($recommendedChans);
            return $this->success($recommendedChansDetails, 'Recommended channels!');
        } catch (ModelNotFoundException $e) {
            return $this->error([], 'Unable to handle request!', 404);
        }

    }

    /**
     * @OA\Post(
     * path="/api/channel/update",
     * operationId="updateChannelDetails",
     * tags={"Channels"},
     * summary="Update a channel",
     * description="update channel details",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"user_id", "channel_id"},
     *               @OA\Property(property="user_id", type="text"),
     *               @OA\Property(property="channel_id", type="text"),
     *               @OA\Property(property="name", type="text"),
     *               @OA\Property(property="profileImage", type="file"),
     *               @OA\Property(property="description", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Channel Updated",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Channel Updated",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Unauthorized access", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function updateChannelDetails(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'channel_id' => 'required|exists:channels,id',
            'name' => 'string|max:150|nullable',
            'profileImage' => 'nullable|image|max:5120',
            'description' => 'nullable|string|max:160',
        ], [
            'image.max' => 'Image size must be less than 5 MB',
        ]);
        if (empty($validatedData['name']) && empty($validatedData['profileImage']) && empty($validatedData['description'])) {
            return $this->error([], 'Must update at least 1 Channel details', 422);
        }
        $isChannelSuperAdmin = Channel::where([
            ['id', $validatedData['channel_id']],
            ['super_admin_id', $validatedData['user_id']],
        ])->first();
        if (!$isChannelSuperAdmin) {
            return $this->error([], 'You are not a SuperAdmin!', 403);
        }
        $getImageData = $isChannelSuperAdmin->profileImage;
        $newImageName = '';
        $length = 7;
        $randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
        if ($image = $request->hasFile('profileImage')) {
            if ($getImageData !== null) {
                // Delete old image from storage folder
                Storage::disk('public')->delete("/images/$getImageData");
            }
            if ($checkedImg = $request->file('profileImage')->isValid()) {
                $newImageName = time() . '-' . $randomString . '.' . $request->file('profileImage')->extension();
                $imagePath = config('app.image_path') . '/profile_imgs/';
                $request->profileImage->move(public_path("$imagePath/profile_imgs/"), $newImageName);
                $validatedData['profileImage'] = $newImageName;
            } else {
                return $this->error([], 'Invalid file upload', 400);
            }
        }
        $isSubchannelAdminUpdateDetails = $isChannelSuperAdmin->update([
            'name' => $validatedData['name'],
            'profileImage' => "https://images.silfrica.com/profile_imgs/$newImageName",
            'description' => $validatedData['description'],
        ]);
        if ($isSubchannelAdminUpdateDetails) return $this->success([], 'Updated!', 201);
        return $this->error([], 'Unable to Update!', 500);
    }

    public function importChannels(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv|max:2048',
        ]);

        try {
            Excel::import(new ChannelsImport, $request->file('file'));

            return $this->success([], 'Channels imported successfully', Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->error([], 'Error importing channels: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
