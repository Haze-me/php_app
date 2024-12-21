<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubChannel;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Validator;

class SubscribeController extends Controller
{
    use HttpResponses;

    /**
     * @OA\Post(
     * path="/api/subscription",
     * operationId="toggleChannelSubscription",
     * tags={"Channels"},
     * summary="Subscribe to channel",
     * description="toggle subscription to channel_id",
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
     *          description="Subscribed or Unsuscribed to channel",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Subscribed or Unsuscribed to channel",
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

    public function toggleChannelSubscription(Request $request)
    {
        $validator = $this->validateSubscriptionRequest($request, 'channel_id', 'channels');

        if ($validator->fails()) {
            return $this->validationError($validator->messages());
        } elseif ($request->user_id != Auth::guard('sanctum')->id()) {
            return $this->error([], 'Unauthorized', 401);
        }

        $user = $request->user();
        $channels_subscribed = json_decode($user->channels_subscribed, true) ?: [];

        return $this->handleSubscriptionFind($request->channel_id, $user, $channels_subscribed, true);
    }

    /**
     * @OA\Post(
     * path="/api/subscription/subchannel",
     * operationId="toggleSubChannelSubscription",
     * tags={"Subchannels"},
     * summary="Subscribe to subchannel",
     * description="toggle subscription to subchannel_id",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"user_id", "sub_channel_id"},
     *               @OA\Property(property="user_id", type="text"),
     *               @OA\Property(property="sub_channel_id", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Subscribed or Unsuscribed to subchannel",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Subscribed or Unsuscribed to subchannel",
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

    public function toggleSubChannelSubscription(Request $request)
    {
        $validator = $this->validateSubscriptionRequest($request, 'sub_channel_id', 'sub_channels');

        if ($validator->fails()) {
            return $this->validationError($validator->messages());
        } elseif ($request->user_id != Auth::guard('sanctum')->id()) {
            return $this->error([], 'Unauthorized', 401);
        }

        $user = $request->user();
        $subchannels_subscribed = json_decode($user->subchannels_subscribed, true) ?: [];

        return $this->handleSubscriptionFind($request->sub_channel_id, $user, $subchannels_subscribed, false);
    }

    private function validateSubscriptionRequest(Request $request, $tbl_name_prop, $field)
    {
        return Validator::make($request->all(), [
            $tbl_name_prop => 'required|exists:' . $field . ',id',
            'user_id' => 'required|exists:users,id',
        ]);
    }

    private function validationError($messages)
    {
        return response()->json(['errors' => $messages], 400);
    }

    private function handleSubscriptionFind($id, $user, $subscribed, $isChannelOrSubChannel = true)
    {
        $firebase = new FirebaseController();

        if ($isChannelOrSubChannel) {
            $channel = Channel::find($id);
            $already_subscribed = in_array($channel->id, $subscribed);
            $titleNotification = 'Yo! something happended!';

            if ($already_subscribed) { //unsubscribe
                $channel->decrement('subscribers');

                $subscribed = $this->handleArray($channel->id, $subscribed);
                $titleNotification = "Unsubscription Alert!";
                $bodyNotification = "$user->user_name has unsubscribed from $channel->name!";

                // unsubscribe from the topic in fcm
                if (!$firebase->unsubscribeFromTopic($channel->topic_name, $user->device_token)) {
                    return $this->error([], 'Something went wrong!', 400);
                }

                $firebase->togglePushNotificationChannel($channel->user->device_token, $titleNotification, $bodyNotification, null, false);

                $message = 'You have successfully unsubscribed from a Channel';

            } else { //subscribe
                $channel->increment('subscribers');

                $subscribed[] = $channel->id;
                $titleNotification = "Subscription Alert!";
                $bodyNotification = "$user->user_name has subscribed to $channel->name!";

                // subscribe to the topic in fcm
                if (!$firebase->subscribeToTopic($channel->topic_name, $user->device_token)) {
                    return $this->error([], 'Something went wrong!', 400);
                }

                $firebase->togglePushNotificationChannel($channel->user->device_token, $titleNotification, $bodyNotification, null, false);

                $message = 'You have successfully subscribed to a Channel';
            }

            $user->channels_subscribed = json_encode($subscribed);
            $user->save();

            return $this->success([
                'channel' => $channel->refresh(),
            ], $message);

        } else {
            $subchannel = SubChannel::find($id);
            $already_subscribed = in_array($subchannel->id, $subscribed);

            if ($already_subscribed) { // unsubscribe
                $subchannel->decrement('subscribers');

                $subscribed = $this->handleArray($subchannel->id, $subscribed);
                $titleNotification = "Unsubscription Alert!";
                $bodyNotification = "$user->user_name has unsubscribed from $subchannel->name!";

                if (!$firebase->unsubscribeFromTopic($subchannel->topic_name, $user->device_token)) {
                    return $this->error([], 'Something went wrong!', 400);
                }

                $firebase->togglePushNotificationChannel($subchannel->admin->device_token, $titleNotification, $bodyNotification, null, false);

                $message = 'You have successfully unsubscribed from a subchannel';

            } else { // subscribe
                $subchannel->increment('subscribers');

                $subscribed[] = $subchannel->id;
                $titleNotification = "Subscription Alert!";
                $bodyNotification = "$user->user_name has subscribed from $subchannel->name!";

                if (!$firebase->subscribeToTopic($subchannel->topic_name, $user->device_token)) {
                    return $this->error([], 'Something went wrong!', 400);
                }

                $firebase->togglePushNotificationChannel($subchannel->admin->device_token, $titleNotification, $bodyNotification, null, false);

                $message = 'You have successfully subscribed to a subchannel';
            }

            $user->subchannels_subscribed = json_encode($subscribed);
            $user->save();

            return $this->success([
                'subchannel' => $subchannel->refresh(),
            ], $message);
        }
    }

    private function handleArray($id, $array)
    {
        $index = array_search($id, $array);

        if ($index !== false) {
            array_splice($array, $index, 1);
        }
        return array_unique($array);
    }
}
