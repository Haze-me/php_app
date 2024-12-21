<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Channel;
use App\Models\SubChannel;
use Illuminate\Support\Str;
use App\Models\PendingAdmin;
use Illuminate\Http\Request;
use App\Traits\HttpResponses;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvitationMail;
use App\Mail\InvitationNoUrlMail;
use App\Models\Institution;
use App\Services\InviteAdminService;
use Illuminate\Support\Facades\Auth;

class InviteAdminController extends Controller
{
    use HttpResponses;

    /**
     * @OA\Post(
     * path="/api/email/invite/user",
     * operationId="sendAdminMail",
     * tags={"Invitation"},
     * summary="Send email with link/no link to invited User",
     * description="Send email with link/no link to invited User(protected)",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"channel_id", "sub_channel_id", "user_id", "email_invited", "email_body"},
     *               @OA\Property(property="channel_id", type="text"),
     *               @OA\Property(property="sub_channel_id", type="text"),
     *               @OA\Property(property="user_id", type="text"),
     *               @OA\Property(property="email_invited", type="email"),
     *               @OA\Property(property="email_body", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Email sent successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Email sent successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Invalid OTP",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Unauthorized User", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function sendAdminMail(Request $request, InviteAdminService $inviteAdminService) //subchannel id, or the channel id, the id of the user sending the email
    {
        try {
            $validatedData = $request->validate([
                'channel_id' => 'required|integer|exists:channels,id',
                'sub_channel_id' => 'nullable|integer|exists:sub_channels,id',
                'user_id' => 'required|integer|exists:users,id',
                'email_invited' => 'required|email',
                'email_body' => 'required|string',
            ]);

            $res = $inviteAdminService->handleAdminInvite($validatedData);
    
            return $this->success([], $res);
        } catch (\Throwable $th) {
            return $this->error(null, $th->getMessage(), 400);
        }
    }

    /**
     * Sets the identifier to be an admin of the channel or sub-channel.
     * If admin of channel, it sets the institution admin to the identifier
     *
     * @param string $identifier
     * @param string $device_token
     * @return string
     */
    public function makeAdmin($identifier, $device_token = null)
    {
        $pendingAdmin = $this->getPendingAdmin($identifier);
        if (!$pendingAdmin) {
            return 'LINK EXPIRED';
        }

        $user = User::where('email', $pendingAdmin->email)->first();
        $subChannelId = $pendingAdmin->sub_channel_id;
        $channelId = $pendingAdmin->channel_id;

        $firebase = new FirebaseController();
        $title = 'Admin Request Accepted!';

        if ($subChannelId !== null) {
            $subChannelId = SubChannel::find($subChannelId);
            $this->makeAdminForSubChannel($user, $subChannelId, $channelId, $firebase, $title, $device_token);
        } elseif ($subChannelId === null) {
            $channelId = Channel::find($pendingAdmin->channel_id);
            $this->makeAdminForChannel($user, $channelId, $firebase, $title, $device_token);
        }

        $pendingAdmin->delete();
        return 'ADMIN REQUEST SUCCESSFULLY ACCEPTED!';
    }

    private function getPendingAdmin(string $identifier)
    {
        if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $identifier)) {
            return PendingAdmin::where('uuid', $identifier)->first();
        }

        return PendingAdmin::where('email', $identifier)->first();
    }

    private function makeAdminForSubChannel(User $user, SubChannel $subChannelId, $channelId, FirebaseController $firebase, string $title, ?string $device_token)
    {
        if ($subChannelId->admin_id === $user->id) {
            return $this->error([], 'Already an admin of the subchannel', 400);
        }

        $this->updateSubChannelAdmin($subChannelId, $user, $channelId, $firebase, $device_token);
        $this->notifySuperAdmin($subChannelId, $user, $firebase, $title);
    }

    private function makeAdminForChannel(User $user, Channel $channelId, FirebaseController $firebase, string $title, ?string $device_token)
    {
        if ($channelId->super_admin_id === $user->id) {
            return $this->error([], 'Already an admin of the channel', 400);
        }

        $this->updateChannelAdmin($channelId, $user, $firebase, $device_token);
        $this->notifySuperAdmin($channelId, $user, $firebase, $title);
    }

    private function updateSubChannelAdmin(SubChannel $subChannel, User $user, $channelId, FirebaseController $firebase, ?string $device_token)
    {
        $subChannel->update(['admin_id' => $user->id]);
        $this->subscribeUserToChannels($user, $subChannel, $channelId, false, $firebase, $device_token);
    }

    private function updateChannelAdmin(Channel $channel, User $user, FirebaseController $firebase, ?string $device_token)
    {
        $channel->update(['super_admin_id' => $user->id]);
        $this->subscribeUserToChannels($user, $channel, null, true, $firebase, $device_token);
        $this->updateInstitutionAdmin($channel, $user);
    }

    private function subscribeUserToChannels(User $user, Channel|SubChannel $field, $channelId, bool $isChannel, FirebaseController $firebase, ?string $device_token)
    {
        $subchannelsSubscribed = json_decode($user->subchannels_subscribed, true) ?: [];
        $channelsSubscribed = json_decode($user->channels_subscribed, true) ?: [];

        if ($isChannel) {
            $alreadySubscribedToChannel = in_array($field->id, $channelsSubscribed);
            if (!$alreadySubscribedToChannel) {
                $field->increment('subscribers');
                $channelsSubscribed[] = $field->id;
                $user->channels_subscribed = json_encode(array_unique($channelsSubscribed));
                $user->primary_institution_id = $field->primary_institution_id;

                $this->subscribeToFirebaseTopic($field, $firebase, $device_token ?: $user->device_token);
            }
        }
        if (!$isChannel) {
            $alreadySubscribedToSubChannel = in_array($field->id, $subchannelsSubscribed);
            if (!$alreadySubscribedToSubChannel) {
                $field->increment('subscribers');
                $subchannelsSubscribed[] = $field->id;
                $channelsSubscribed[] = intVal($channelId);
                $user->subchannels_subscribed = json_encode(array_unique($subchannelsSubscribed));
                $user->channels_subscribed = json_encode(array_unique($channelsSubscribed));
                $user->primary_institution_id = $field->primary_institution_id;

                $this->subscribeToFirebaseTopic($field, $firebase, $device_token ?: $user->device_token);
            }
        }

        $user->save();
    }

    private function updateInstitutionAdmin($channel, $user)
    {
        if ($channel->institution_id !== null && $channel->is_primary) {
            $primaryChannel = Channel::where([
                ['institution_id', $channel->institution_id],
                ['type', 'Administration'],
                ['is_primary', true]
            ])->first();

            $channelsSubscribed = json_decode($user->channels_subscribed, true) ?: [];
            if ($primaryChannel && !in_array($primaryChannel->id, $channelsSubscribed)) {
                $channelsSubscribed[] = $primaryChannel->id;
                $primaryChannel->increment('subscribers');
            }

            $institution = Institution::find($channel->institution_id);
            if ($institution) {
                $institution->update(['admin_id' => $user->id]);
            }

            $user->update([
                'primary_institution_id' => $channel->institution_id,
                'channels_subscribed' => json_encode(array_unique($channelsSubscribed)),
            ]);
        } elseif (!$channel->is_primary) {
            $channelsSubscribed = json_decode($user->channels_subscribed, true) ?: [];
            $channelsSubscribed[] = $channel->id;
            $channel->increment('subscribers');
            $user->channels_subscribed = json_encode(array_unique($channelsSubscribed));
            $user->save();
        }
    }

    private function subscribeToFirebaseTopic($channel, FirebaseController $firebase, $device_token)
    {
        if (!$firebase->subscribeToTopic($channel->topic_name, $device_token)) {
            return $this->error([], 'Something went wrong!', 400);
        }
    }

    private function notifySuperAdmin($channel, $user, $firebase, $title)
    {
        $getSuperAdmin = $channel->super_admin_id;
        if ($getSuperAdmin) {
            $superAdminUser = User::find($getSuperAdmin);
            if ($superAdminUser) {
                $superAdminUserToken = $superAdminUser->device_token;
                $body = "$user->username has accepted your request to be a super-admin of $channel->name";
                if (!$firebase->togglePushNotificationChannel($superAdminUserToken, $title, $body, null, false)) {
                    return $this->error([], 'Unable to notify superAdmin!', 400);
                }
            }
        }
    }
}
