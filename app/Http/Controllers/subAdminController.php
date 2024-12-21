<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubChannel;
use App\Models\Channel;
use Illuminate\Support\Facades\Auth;
use App\Traits\HttpResponses;
use App\Http\Resources\ChannelResource;
use App\Http\Resources\SubChannelResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class subAdminController extends Controller
{
    use HttpResponses;

    /**
     * @OA\Get(
     * path="/api/subadmin/{user_id}/{channel_id}",
     * operationId="getSubadminSubchannels",
     * tags={"Subadmins"},
     * summary="Get all subchannels managed by subadmin within current channel",
     * description="subchannels data managed by subadmin within current channel",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"user_id", "channel_id"},
     *               @OA\Property(property="user_id", type="text"),
     *               @OA\Property(property="channel_id", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Subchannel",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Subchannel",
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

    public function getSubadminSubchannels($user_id, $channel_id) //Retrieving all subchannels managed by subadmin within channel_id
    {
        if ($user_id != Auth::guard('sanctum')->id()) {
            return $this->error([], 'Unauthorized', 403);
        } else {
            try {
                $channel = Channel::findOrFail($channel_id);
                $subadmins = json_decode($channel->sub_admins, true);
                if ($subadmins == null) {
                    return $this->error([], 'No subadmins found', 200);
                }
                $subAdminIds = json_decode($channel->sub_admins, true);
                $subChannels = SubChannel::whereIn('admin_id', $subAdminIds)
                    ->where('status', 1)
                        ->where('deleted', false)
                            ->get();
                $subChannelDetails = SubChannelResource::collection($subChannels);
                return $subChannelDetails;
            } catch (\Throwable $th) {
                return $this->error([], $th->getMessage(), 404);
            }
        }
    }

    /**
     * @OA\Get(
     * path="/api/subadmin/subadmins/{user_id}/{channel_id}",
     * operationId="getSubadmins",
     * tags={"Subadmins"},
     * summary="Get all subadmins within current channel",
     * description="shows all subadmins data",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"user_id", "channel_id"},
     *               @OA\Property(property="user_id", type="text"),
     *               @OA\Property(property="channel_id", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Subadmins",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Subadmins",
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

    public function getSubadmins($user_id, $channel_id) //Retrieving all subadmins within channel_id
    {
        if ($user_id != Auth::guard('sanctum')->id()) {
            return $this->error([], 'Unauthorized', 403);
        } else {
            try {
                $channel = Channel::findOrFail($channel_id);
                $subadmins = json_decode($channel->sub_admins, true);
                if ($subadmins == null) {
                    return $this->success([], 'No subadmins available');
                }
                $userSubadmins = User::whereIn('id', $subadmins)->get();
                if ($userSubadmins->isEmpty()) {
                    return $this->success([], 'Subadmins not found');
                }
                return UserResource::collection($userSubadmins);
            } catch (\Throwable $th) {
                return $this->error([], $th->getMessage(), 422);
            }
        }
    }


    /**
     * @OA\Get(
     * path="/api/channel/subadmins/{channel_id}/removed",
     * operationId="getDeletedSubadmins",
     * tags={"Subadmins"},
     * summary="Get all removed subadmins for a channel",
     * description="shows all removed subadmins data",
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
     *          description="Subadmins",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Subadmins",
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

    public function getDeletedSubadmins($channel_id) // retrieve deleted subadmins for a channel
    {
        try {
            $channel = Channel::findOrFail($channel_id);
            $deletedSubAdmins = json_decode($channel->removed_admins, true);
            if ($deletedSubAdmins == null) {
                return $this->error([], 'No data found!', 200);
            }
            $userDeletedSubadmins = User::whereIn('id', $deletedSubAdmins)->get();
            if ($userDeletedSubadmins->isEmpty()) {
                return $this->error([], 'Subadmins not found!', 200);
            }
            return UserResource::collection($userDeletedSubadmins);
        } catch (\Throwable $th) {
            // report($th);
            echo "Error: " . $th->getMessage();
            return $this->error([], 'Could not retrieve data!', 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/channel/subadmins/suspended/{channel_id}",
     * operationId="getSuspendedSubadmins",
     * tags={"Subadmins"},
     * summary="Get all suspended subadmins for a channel",
     * description="shows all suspended subadmins data",
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
     *          description="Subadmins",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Subadmins",
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

    public function getSuspendedSubadmins($channel_id) // retrieve suspended subadmins for a channel
    {
        try {
            $channel = Channel::findOrFail($channel_id);
            $deletedSubAdmins = json_decode($channel->suspended_admins, true);
            if ($deletedSubAdmins == null) {
                return $this->error([], 'No data found!', 200);
            }
            $userDeletedSubadmins = User::whereIn('id', $deletedSubAdmins)->get();
            if ($userDeletedSubadmins->isEmpty()) {
                return $this->success([], 'Subadmins not found!');
            }
            return UserResource::collection($userDeletedSubadmins);
        } catch (\Throwable $th) {
            // report($th);
            echo "Error: " . $th->getMessage();
            return $this->error([], 'Could not retrieve data!', 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/channel_subchannel/subadmins/managed/{user_id}",
     * operationId="getChannelOrSubchannelManagedByUser",
     * tags={"Subadmins"},
     * summary="retrieve the channels and subchannels a user is in charge",
     * description="retrieve the channels and subchannels a user is in charge",
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
     *          description="channels and subchannels managed by user",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="channels and subchannels managed by user",
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

    public function getChannelOrSubchannelManagedByUser($user_id) // retrieve the channels and subchannels a user is in charge
    {
        try {
            $checkChannelsManaged = Channel::where('super_admin_id', $user_id)->get();
            $checkSubchannelsManaged = SubChannel::where('admin_id', $user_id)->get();
            if (empty($checkChannelsManaged) && !empty($checkSubchannelsManaged)) {
                return SubChannelResource::collection($checkSubchannelsManaged);
            }
            if (!empty($checkChannelsManaged) && empty($checkSubchannelsManaged)) {
                return ChannelResource::collection($checkChannelsManaged);
            }
            return $this->success([
                'channels_managed' => $checkChannelsManaged,
                'subchannels_managed' => $checkSubchannelsManaged
            ], null);
        } catch (\Throwable $th) {
            //throw $th;
            echo "Error: " . $th->getMessage();
            return $this->error([], 'Could not retrieve data!', 500);
        }
    }

    /**
     * @OA\Post(
     * path="/api/channel/{channel_id}/subadmin/{admin_id}/suspend",
     * operationId="suspendSubadmin",
     * tags={"Subadmins"},
     * summary="to suspend subadmin",
     * description="suspends a subadmin",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"admin_id", "channel_id"},
     *               @OA\Property(property="admin_id", type="text"),
     *               @OA\Property(property="channel_id", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="suspended a subadmin",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="suspended a subadmin",
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

    public function suspendSubadmin($channel_id, int $admin_id) //to suspend subadmin
    {
        try {
            $checkChannelExists = Channel::findOrFail($channel_id);
            $checkSubadminExists = json_decode($checkChannelExists->sub_admins, true) ?: [];
            $checkSuspendedExists = json_decode($checkChannelExists->suspended_admins, true) ?: [];
            $already_subadmin = in_array($admin_id, $checkSubadminExists);
            if ($already_subadmin) {
                $index = array_search($admin_id, $checkSubadminExists);
                if ($index !== false) {
                    array_splice($checkSubadminExists, $index, 1);
                }
                $checkSuspendedExists[] = $admin_id;
                $checkChannelExists->sub_admins = json_encode($checkSubadminExists);
                $checkChannelExists->suspended_admins = json_encode($checkSuspendedExists);
                $checkChannelExists->save();
                $checkAdminSubchannel = SubChannel::where([
                    ['admin_id', $admin_id],
                    ['status', 1]
                ])->first();
                $checkAdminSubchannel->update(['admin_id' => null]);
            }
            $message = 'subadmin suspended!';
            return $this->success([], $message, 201);
        } catch (\Throwable $th) {
            echo "Error: " . $th->getMessage();
            return $this->error([], 'Could not retrieve data!', 500);
        }
    }

    /**
     * @OA\Post(
     * path="/api/channel/{channel_id}/unsuspend/subadmin/{admin_id}/subchannel/{subchannel_id}",
     * operationId="unsuspendSubadmin",
     * tags={"Subadmins"},
     * summary="to unsuspend subadmin",
     * description="unsuspends a subadmin",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"channel_id", "admin_id", "subchannel_id"},
     *               @OA\Property(property="channel_id", type="text"),
     *               @OA\Property(property="admin_id", type="text"),
     *               @OA\Property(property="subchannel_id", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="unsuspends a subadmin and assigns the subadmin to the subchannel id",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="unsuspends a subadmin and assigns the subadmin to the subchannel id",
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

    public function unsuspendSubadmin($channel_id, int $admin_id, $subchannel_id) //to unsuspend a subadmin
    {
        try {
            $checkChannelExists = Channel::findOrFail($channel_id);
            $checkSuspendedAdmin = json_decode($checkChannelExists->suspended_admins, true) ?: [];
            $checkSubadmin = json_decode($checkChannelExists->sub_admins, true) ?: [];
            $already_suspended = in_array($admin_id, $checkSuspendedAdmin);
            if ($already_suspended) {
                $index = array_search($admin_id, $checkSuspendedAdmin);
                if ($index !== false) {
                    array_splice($checkSuspendedAdmin, $index, 1);
                }
                $checkChannelExists->suspended_admins = json_encode($checkSuspendedAdmin);
                $checkSubadmin[] = $admin_id;
                $checkChannelExists->sub_admins = json_encode($checkSubadmin);
                $checkChannelExists->save();
                // Update admin_id field of the subchannel
                SubChannel::where('id', $subchannel_id)
                    ->update(['admin_id' => $admin_id]);
                $message = 'subadmin unsuspended!';
            }
            return $this->success([], $message, 201);
        } catch (\Throwable $th) {
            //throw $th;
            echo "Error: " . $th->getMessage();
            return $this->error([], 'Could not retrieve data!', 500);
        }
    }
}
