<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChannelResource;
use App\Http\Resources\SubChannelResource;
use App\Models\Channel;
use App\Models\Institution;
use App\Models\SubChannel;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TasksController extends Controller
{
    use HttpResponses;

    /**
     * @OA\Get(
     * path="/api/user/{user_id}/managed/channels_subchannels",
     * operationId="userManagedPlatforms",
     * tags={"Managed-Platforms"},
     * summary="user managed channels and subchannels.",
     * description="responds with a set of channels/subchannels",
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
     *          description="managed platforms",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="managed platforms",
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

    public function userManagedPlatforms($user_id) //api to retrieve user managed channels and subchannels.
    {
        $user = User::findOrFail($user_id);
        $checkChannelsManaged = Channel::where('super_admin_id', $user->id)->get();
        $checkSubchannelsManaged = SubChannel::where('admin_id', $user->id)->get();

        $channelR = $checkChannelsManaged->isEmpty() ? [] : ChannelResource::collection($checkChannelsManaged);
        $subChannelR = $checkSubchannelsManaged->isEmpty() ? [] : SubChannelResource::collection($checkSubchannelsManaged);

        return $this->success([
            'channels' => $channelR,
            'sub_channels' => $subChannelR,
        ]);
    }

    /**
     * @OA\Get(
     * path="/api/institution/get",
     * operationId="retrieveInstitutions",
     * tags={"Institution"},
     * summary="Gets all institutions",
     * description="retrieves institution",
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
     *          description="institutions = {id:value, name:value}",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="institutions = {id:value, name:value}",
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
    public function retrieveInstitutions()
    {
        // Eager load the 'campuses' relationship
        $institutions = Institution::with('campuses')->get();

        if ($institutions->isEmpty()) {
            return $this->success([], 'Oops! No Institutions!');
        }

        $fi = $institutions->map(function ($institution) {
            return [
                'id' => $institution->id,
                'name' => $institution->name,
                'website' => $institution->website,
                'campuses' => $institution->campuses->map(function ($campus) {
                    return [
                        'id' => $campus->id,
                        'name' => $campus->name,
                    ];
                }),
            ];
        });

        return $this->success([
            'institutions' => $fi,
        ]);
    }

    public function alertGroup(Request $request)
    {
      try {
         $validatedData = $request->validate([
            'user_id' => 'required',
            'title' => 'required|string',
            'message' => 'required|string|min:8',
            'audience' => 'required|string',
            'schedule' => 'nullable|string'
         ]);
         $user = $request->user();
         $channel = Channel::where('institution_id', $user->primary_institution_id)->get();
         $sub_channel = SubChannel::where('primary_institution_id', $user->primary_institution_id)->get();
         if (!$channel || !$sub_channel) {
            return $this->error(null, 'Alert Failed!', 400);
         }
         $firebase = new FirebaseController();
         if($channel) {
            $firebase->togglePushNotificationChannel($channel->topic_name, $validatedData['title'], $validatedData['message']);
         }
         if($sub_channel) {
            $firebase->togglePushNotificationChannel($sub_channel->topic_name, $validatedData['title'], $validatedData['message']);
         }
         return $this->success(null, 'Alert Signaled!');
      } catch (\Throwable $th) {
         Log::error("Alert failed $th");
         return $this->error(null, $th->getMessage(), 500);
      }
    }

}
