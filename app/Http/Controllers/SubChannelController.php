<?php

namespace App\Http\Controllers;

use App\Models\SubChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportSubChannelsRequest;
use App\Models\Channel;
use App\Imports\SubChannelsImport;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use App\Http\Resources\SubChannelResource;
use App\Services\FileService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SubChannelController extends Controller
{
    use HttpResponses;

    /**
     * @OA\Get(
     * path="/api/channel/subchannels/{user_id}/{channel_id}",
     * operationId="index",
     * tags={"Subchannels"},
     * summary="Get subchannels attached to a channel",
     * description="Display List of subchannel resource",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"user_id", "channel_id"},
     *               @OA\Property(property="user_id", type="text"),
     *               @OA\Property(property="channel_id", type="text")
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Subchannels List",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Subchannels List",
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

    public function index($user_id, $channel_id) //get subchannels attached to channel
    {
        if (Auth::guard('sanctum')->id() != $user_id) {
            return $this->error([], 'Unauthorized', 403);
        }
        try {
            $channel = Channel::findOrFail($channel_id);
            $subChannelIds = json_decode($channel->sub_channels, true) ?: [];
            if (empty($subChannelIds)) {
                return $this->error([], 'No subchannels for this channel', 200);
            }
            $subChannels = SubChannel::whereIn('id', $subChannelIds)
                ->where('deleted', false)
                ->get();
            $subChannelDetails = SubChannelResource::collection($subChannels);
            return $subChannelDetails;
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->error([], $th->getMessage(), 404);
        }
    }

    /**
     * @OA\Post(
     * path="/api/subchannel",
     * operationId="store",
     * tags={"Subchannels"},
     * summary="Create a subchannel attached to channel",
     * description="Save subchannel into resource",
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
     *               @OA\Property(property="type", type="text"),
     *               @OA\Property(property="description", type="text"),
     *               @OA\Property(property="admin_id", type="text"),
     *               @OA\Property(property="category", type="text"),
     *               @OA\Property(property="targetAudience", type="text"),
     *               @OA\Property(property="subchannelWebsite", type="text"),
     *               @OA\Property(property="primaryCampus", type="text"),
     *               @OA\Property(property="status", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Subchannels Created",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Subchannels Created",
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

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id',
                'channel_id' => 'required|exists:channels,id',
                'name' => 'required|string|max:150',
                'profileImage' => 'nullable|image|max:5120',
                'type' => 'required|string',
                'description' => 'required|string',
                'category' => 'required|string',
                'targetAudience' => 'required|string',
                'subchannelWebsite' => 'nullable|string',
            ], [
                'image.max' => 'Image size must be less than 5 MB',
            ]);


            $superadmin_AdminChannel = Channel::where([
                ['super_admin_id', $validatedData['user_id']],
                ['type', 'administration']
            ])->first();

            $channelInstitutionId = $superadmin_AdminChannel->institution_id;

            if (!$superadmin_AdminChannel) {
                return $this->error([], 'Forbidden', 403);
            }

            $newImageName = '';
            $length = 7;
            $randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);

            if ($request->hasFile('profileImage')) {
                if ($request->file('profileImage')->isValid()) {
                    $newImageName = time() . '-' . $randomString . '.' . $request->file('profileImage')->extension();
                    $imagePath = config('app.image_path') . '/profile_imgs/';
                    $request->profileImage->move(public_path("$imagePath/profile_imgs/"), $newImageName);
                    $validatedData['profileImage'] = $newImageName;
                } else {
                    return $this->error([], 'Invalid file upload', 400);
                }
            }

            $subChannel = SubChannel::create($validatedData);

            // generate random topic name
            $topicController = new TopicController();
            $randomTopic = $topicController->generateRandomTopic();

          
            $subChannel->update([
              'admin_id' => $request->user_id,
              'status' => 0,
              'primary_institution_id' => $channelInstitutionId,
              'profileImage' => $newImageName ? "https://images.silfrica.com/profile_imgs/$newImageName" : null,
              'topic_name' => $randomTopic,
            ]);
            // the user who created the subchannel should also be automatically subscribed to the subchannel just created
            $user = User::findOrFail($validatedData['user_id']);
            $subchannels_subscribed = json_decode($user->subchannels_subscribed, true) ?: [];
            $subchannels_subscribed[] = $subChannel->id;
            $user->subchannels_subscribed = json_encode($subchannels_subscribed);
            $user->save();

            //insert the subchannel admin and id into the sub_admins column and sub_channels column respectively in the channels table
            $channel = Channel::find($request->channel_id);
            $subAdmins = json_decode($channel->sub_admins, true) ?: [];
            // Check if the user_id already exists in sub_admins
            if (!in_array($validatedData['user_id'], $subAdmins)) {
                $subAdmins[] = $user->id;
                $channel->sub_admins = json_encode(array_values($subAdmins));
            }

            $subChannels = json_decode($channel->sub_channels, true) ?: [];
            $subChannels[] = $subChannel->id;

            $channel->sub_channels = json_encode(array_values($subChannels));
            $channel->save();

            // create new topic in firebase
            $topicName = $subChannel->topic_name;
            $firebase = new FirebaseController();
            $firebase->subscribeToTopic($topicName, $user->device_token);

            return $this->success($subChannel, 'You have successfully created a sub channel!', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
                throw new HttpResponseException(
                    response('The request timed out. Please try again later.', Response::HTTP_REQUEST_TIMEOUT),
                );
            }

            throw $e;
        }
    }

    /**
     * @OA\Post(
     * path="/api/subchannel/update",
     * operationId="updateSubchannelDetails",
     * tags={"Subchannels"},
     * summary="Update a subchannel",
     * description="update subchannel details",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"user_id", "sub_channel_id"},
     *               @OA\Property(property="user_id", type="text"),
     *               @OA\Property(property="sub_channel_id", type="text"),
     *               @OA\Property(property="name", type="text"),
     *               @OA\Property(property="profileImage", type="file"),
     *               @OA\Property(property="description", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Subchannel Updated",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Subchannel Updated",
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

    public function updateSubchannelDetails(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'sub_channel_id' => 'required|exists:sub_channels,id',
            'name' => 'string|max:150|nullable',
            'profileImage' => 'nullable|image|max:5120',
            'description' => 'nullable|string|max:160',
        ], [
            'image.max' => 'Image size must be less than 5 MB',
        ]);
        if (empty($validatedData['name']) && empty($validatedData['profileImage']) && empty($validatedData['description'])) {
            return $this->error([], 'Must update at least 1 Subchannel details', 422);
        }
        $isSubchannelAdmin = SubChannel::where([
            ['id', $validatedData['sub_channel_id']],
            ['admin_id', $validatedData['user_id']],
        ])->first();
        if (!$isSubchannelAdmin) {
            return $this->error([], 'You are not an Admin of this Subchannel!', 403);
        }
        $getImageData = $isSubchannelAdmin->profileImage;
        $newImageName = '';
        $length = 7;
        $randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
        if ($request->hasFile('profileImage')) {
            if ($getImageData !== null) {
                // Delete old image from storage folder
                $delete_old_image = new FileController();
                $delete_old_image->deleteFile(config('app.image_path') . '/profile_imgs/' . $getImageData);
            }
            if ($request->file('profileImage')->isValid()) {
                $newImageName = time() . '-' . $randomString . '.' . $request->file('profileImage')->extension();
                $imagePath = config('app.image_path') . '/profile_imgs/';
                $request->profileImage->move(public_path($imagePath), $newImageName);
                $validatedData['profileImage'] = $newImageName;
            } else {
                return $this->error([], 'Invalid file upload', 400);
            }
        }
        $isSubchannelAdminUpdateDetails = $isSubchannelAdmin->update([
            'name' => $validatedData['name'],
            'profileImage' => "https://images.silfrica.com/profile_imgs/$newImageName",
            'description' => $validatedData['description'],
        ]);
        if ($isSubchannelAdminUpdateDetails) {
            return $this->success([], 'Updated!', Response::HTTP_CREATED);
        }
        return $this->error([], 'Unable to Update!', 500);
    }

    /**
     * @OA\Get(
     * path="/api/channel/subchannel/{user_id}/{sub_channel_id}",
     * operationId="show",
     * tags={"Subchannels"},
     * summary="Get a subchannel",
     * description="Receiving a subchannel",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"user_id", "subchannel_id"},
     *               @OA\Property(property="user_id", type="text"),
     *               @OA\Property(property="subchannel_id", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Found Subchannel",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Found Subchannel",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Unauthorized Subchannel", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function show($user_id, $sub_channel_id) //get a sub channel
    {
        try {
            if ($user_id != Auth::guard('sanctum')->id()) {
                return $this->error([], 'Forbidden', 403);
            } else {
                $subChannel = SubChannel::findOrFail($sub_channel_id);
                if ($subChannel) {
                    $subchannelDetail = SubChannelResource::make($subChannel);
                    return $subchannelDetail;
                }
                return $this->error([], 'No Subchannel like that!', Response::HTTP_NO_CONTENT);
            }
        } catch (\Throwable $th) {
            $this->error(['status' => 'failed'], $th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     * path="/api/subchannel/suspension",
     * operationId="suspendORunsuspend",
     * tags={"Subchannels"},
     * summary="Suspend/Unsuspend a subchannel",
     * description="toggle suspension on a subchannel(protected)",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"user_id", "subchannel_id"},
     *               @OA\Property(property="user_id", type="text"),
     *               @OA\Property(property="subchannel_id", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="This sub channel is now successfully suspended or active",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="This sub channel is now successfully suspended or active",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Unauthorized Subchannel", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function suspendORunsuspend(Request $request) //suspension of a sub channel
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'sub_channel_id' => 'required|exists:sub_channels,id',
        ]);

        $subChannel = SubChannel::findOrFail($validatedData['sub_channel_id']);

        $already_suspended = SubChannel::where([
            ['id', $subChannel->id],
            ['status', 2]
        ])->exists();

        if ($request->user_id != Auth::guard('sanctum')->id()) {
            return $this->error([], 'Unauthorized', 401);
        } elseif ($already_suspended) {
            //activate
            $status = 1;
        } else {
            //suspend
            $status = 2;
        }

        $subChannel->status = $status;
        $subChannel->save();
        return $this->success([
            'subchannel' => $subChannel->refresh(),
        ], 'This sub channel is now successfully ' . $status);
    }

    /**
     * @OA\Get(
     * path="/api/channel/subchannel/suspended/{user_id}/{channel_id}",
     * operationId="getSusSubChans",
     * tags={"Subchannels"},
     * summary="Get a suspended subchannel",
     * description="Received suspended subchannels attached to channel_id",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"user_id", "subchannel_id"},
     *               @OA\Property(property="user_id", type="text"),
     *               @OA\Property(property="subchannel_id", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Suspended Subchannel",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Suspended Subchannel",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Unauthorized", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function getSusSubChans($user_id, $channel_id) //get suspended subchannels attached to channel
    {
        if ($user_id != Auth::guard('sanctum')->id()) {
            return $this->error([], 'Forbidden', 403);
        }
        $channel = Channel::findOrFail($channel_id);
        if ($channel->sub_channels === null) {
            return $this->success([], 'No subchannels found');
        }
        $subChannelIds = json_decode($channel->sub_channels, true);
        $subChannels = SubChannel::whereIn('id', $subChannelIds)
            ->where('status', 2)
            ->where('deleted', false)
            ->get();

        $subChannelDetails = SubChannelResource::collection($subChannels);
        return $subChannelDetails;
    }

    /**
     * @OA\Post(
     * path="/api/subchannel/delete",
     * operationId="delete",
     * tags={"Subchannels"},
     * summary="Delete a subchannel",
     * description="Destroys a subchannel attached to channel_id",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"user_id", "subchannel_id"},
     *               @OA\Property(property="user_id", type="text"),
     *               @OA\Property(property="subchannel_id", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Deleted Subchannel",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Deleted Subchannel",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Unauthorized", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function delete(Request $request) //delete sub channel
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'sub_channel_id' => 'required|exists:sub_channels,id',
        ]);

        $superadmin = Channel::where('super_admin_id', $request->user_id)->first();

        if ($request->user_id != Auth::guard('sanctum')->id() || !$superadmin) {
            return $this->error([], 'Unauthorized', Response::HTTP_UNAUTHORIZED);
        } else {
            $deleted = SubChannel::where('id', $request->sub_channel_id)->update(['deleted' => true]);
            if ($deleted) {
                return $this->success([], 'This sub channel is now successfully deleted', Response::HTTP_CREATED);
            }
        }
    }

    /**
     * @OA\Get(
     * path="/api/subchannel/recommendation/{user_id}",
     * operationId="showRecommendedSubchannels",
     * tags={"Subchannels"},
     * summary="Get all recommended subchannels",
     * description="retrieves all recommended subchannels(protected)",
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
     *          description="subchannels",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="subchannels",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Unauthorized", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function showRecommendedSubchannels($user_id) //get all recommended subchannels for a user
    {
        if ($user_id != Auth::guard('sanctum')->id()) {
            return $this->error([], 'Unauthorized', 403);
        }
        try {
            $user = User::findOrFail($user_id);
            $userSubscribedChannelIds = json_decode($user->channels_subscribed, true) ?: [];
            $userSubscribedSubChannel = json_decode($user->subchannels_subscribed, true) ?: [];
            $recommendedPrivateSubchannels = [];
            if (!empty($userSubscribedChannelIds)) {
                if (!empty($userSubscribedSubChannel)) {
                    foreach ($userSubscribedChannelIds as $channelId) {
                        $channel = Channel::find($channelId);
                        if ($channel) {
                            $associatedSubchannels = json_decode($channel->sub_channels, true) ?: [];
                            foreach ($associatedSubchannels as $subchannelId) {
                                $subchannel = SubChannel::find($subchannelId);
                                if ($subchannel && !in_array($subchannelId, $userSubscribedSubChannel)) {
                                    $recommendedPrivateSubchannels[] = $subchannel;
                                }
                            }
                        }
                    }
                    $recommendedSubchannelsResource = SubChannelResource::collection($recommendedPrivateSubchannels);
                    return $this->success($recommendedSubchannelsResource, 'Recommended subchannels!');
                } else {
                    foreach ($userSubscribedChannelIds as $channelId) {
                        $channel = Channel::find($channelId);
                        if ($channel) {
                            $associatedSubchannels = json_decode($channel->sub_channels, true) ?: [];
                            foreach ($associatedSubchannels as $subchannelId) {
                                $subchannel = SubChannel::find($subchannelId);
                                // Check if $subchannel is not null before adding it
                                if ($subchannel) {
                                    $recommendedPrivateSubchannels[] = $subchannel;
                                }
                            }
                        }
                    }
                    $recommendedSubchannelsResource = SubChannelResource::collection($recommendedPrivateSubchannels);
                    return $this->success($recommendedSubchannelsResource, 'Recommended subchannels with Private!');
                }
            } elseif (empty($userSubscribedChannelIds)) {
                if (!empty($userSubscribedSubChannel) && is_array($userSubscribedSubChannel)) {
                    $subChannelsOnlyPublic = SubChannel::whereNotIn('id', $userSubscribedSubChannel)
                        ->where([
                            ['type', 'Public'],
                            ['deleted', false],
                            ['status', 1],
                        ])
                        ->get();
                    $subscribedSubChannelsResource = SubChannelResource::collection($subChannelsOnlyPublic);
                    if ($subChannelsOnlyPublic->isNotEmpty()) {
                        return $this->success($subscribedSubChannelsResource, 'Recommendations for you!');
                    } else {
                        return $this->success([], 'Oops No subchannels right now! v2');
                    }
                } else {
                    $subChannelsOnlyPublic = SubChannel::where([
                        ['type', 'Public'],
                        ['deleted', false],
                        ['status', 1],
                    ])->get();

                    $subscribedSubChannelsResource = SubChannelResource::collection($subChannelsOnlyPublic);
                    return $this->success($subscribedSubChannelsResource, "Here's a list of subchannels!");
                }
            } else {
                return $this->success([], 'No recommendations currently!');
            }
        } catch (ModelNotFoundException $e) {
            return $this->error([], 'Unable to handle request', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @OA\Get(
     * path="/api/subchannel/subchannels/get/{user_id}",
     * operationId="showSubchannels",
     * tags={"Subchannels"},
     * summary="Get all subchannels",
     * description="retrieves all subchannels(protected)",
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
     *          description="subchannels",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="subchannels",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Unauthorized", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function showSubchannels($user_id) //get all subchannels for a user
    {
        $user = User::findOrFail($user_id);
        $primaryChannelIds = json_decode($user->channels_subscribed, true);
        $subChannelsUserSubscribedTo = json_decode($user->subchannels_subscribed, true);
        if (empty($primaryChannelIds) && empty($subChannelsUserSubscribedTo)) {
            return $this->success([], 'No Subscribed Subchannels');
        }
        if (empty($subChannelsUserSubscribedTo)) {
            return $this->success([], 'No Subscribed Subchannels');
        }
        $getSubChannelPublic = SubChannel::whereIn('id', $subChannelsUserSubscribedTo)
            ->where('type', 'Public')
            ->where('deleted', false)
            ->get();

        $getSubChannelPrivate = Channel::whereIn('id', $primaryChannelIds)
            ->whereJsonContains('sub_channels', $subChannelsUserSubscribedTo)
            ->get();

        if (!empty($getSubChannelPrivate) && is_array($getSubChannelPrivate)) {
            $getSubChannel = SubChannel::whereIn('id', $subChannelsUserSubscribedTo)
                ->where('deleted', false)
                ->get();
            return $this->success($getSubChannel, 'All Subchannels');
        }

        return $this->success($getSubChannelPublic, 'All Subchannels');
    }

    /**
     * @OA\Get(
     * path="/api/subchannel/subscribed/{user_id}",
     * operationId="getUserSubscribedSubchannel",
     * tags={"Subchannels"},
     * summary="Get user subscribed subchannels",
     * description="retrieves subscribed subchannels(protected)",
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
     *          description="subscribed subchannels",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="subscribed subchannels",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Unauthorized", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function getUserSubscribedSubchannel($user_id) //retrieve subscribed subchannels
    {
        $user = User::where('id', $user_id)->first();
        $subChannelsUserSubscribedTo = json_decode($user->subchannels_subscribed, true) ?: [];
        if (empty($subChannelsUserSubscribedTo)) {
            return $this->success([], 'No Subscribed Subchannels');
        }
        $getSubChannel = SubChannel::whereIn('id', $subChannelsUserSubscribedTo)
            ->where('deleted', false)
            ->get();
        $subChannelResource = SubChannelResource::collection($getSubChannel);
        return $this->success($subChannelResource, 'All Subscribed Subchannels');
    }

    /**
     * Handle the file upload and import.
     *
     * @param ImportSubChannelsRequest $request
     * @return \Illuminate\Http\Response
     */
    public function storeBulk(ImportSubChannelsRequest $request)
    {
        try {
            $fileService = new FileService();
            $fileValidation = $fileService->validateFileRows($request->file('file'));
            if ($fileValidation !== true) {
                return $this->error([], "File: $fileValidation", Response::HTTP_BAD_REQUEST);
            }
            $userId = $request->input('user_id');
            Excel::import(new SubChannelsImport($userId), $request->file('file'));
    
            return $this->success([], 'Subchannels imported successfully', Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            Log::error('Subchannel creation failed: ' . $th->getMessage());
            return $this->error([], $th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
