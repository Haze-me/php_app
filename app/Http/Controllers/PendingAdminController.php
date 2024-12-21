<?php

namespace App\Http\Controllers;

use App\Models\PendingAdmin;
use App\Models\SubChannel;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\updateUserDetailsController;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Validator;


class PendingAdminController extends Controller
{
    use HttpResponses;
    public function sendAdminRequestEmailAndInsertPendingAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'sub_channel_id' => 'exists:sub_channels,id',
            'channel_id' => 'exists:channels,id',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }

        $channel_id = $request->channel_id;
        $user_id = $request->user_id;

        $superadmin = Channel::where([
            ['super_admin_id', $user_id],
            ['id', $channel_id]
        ])->first();

        if (!$superadmin && $user_id != Auth::guard('sanctum')->id()) {
            return $this->error(null, 'Unauthorized', 401);

        }

        $sub_channel_id = $request->sub_channel_id;
        if ($channel_id === null && $sub_channel_id !== null) {
            $subOrChannelPendingAdminExists = PendingAdmin::where('sub_channel_id', $sub_channel_id)->first();
            $type = 'Subchannel';

        } elseif ($channel_id !== null && $sub_channel_id === null) {
            $subOrChannelPendingAdminExists = PendingAdmin::where('channel_id', $channel_id)->first();
            $type = 'Channel';

        } elseif ($channel_id !== null && $sub_channel_id !== null) {
            return $this->error(null, 'Channel id OR subchannel id (not both) required for an admin request', 400);

        } else {
            return $this->error(null, 'Incomplete request, channel id or subchannel id required', 400);

        }

        if ($subOrChannelPendingAdminExists) {
            $subOrChannelPendingAdminExists->update([
                'email' => $request->email,
                'created_at' => now(),
            ]);
            $pendingAdmin = $subOrChannelPendingAdminExists->refresh();

        } else {
            $pendingAdmin = PendingAdmin::create([
                'email' => $request->email,
                'sub_channel_id' => $sub_channel_id,
                'channel_id' => $channel_id,
                'created_at' => now(),
            ]);

        }

        $pendingAdminToken = $pendingAdmin->createToken('pendingAdmin', [
            'expires_in' => 60 * 24 * 3
        ])->plainTextToken;

        //write code for sending an email to notify the person that they're an admin here
        //check if the email exists in the user table...if so, then send a link. if not, then send a message telling them to register
        //if you're sending a link, send the token(important) and pendingAdmin deets(if you want) to the email

        return $this->success(['message' => $type . ' admin request sent successfully.',
            'token' => $pendingAdminToken,
        ], 201);

    }
    public function adminRequestAccepted(Request $request)
    {
        // after registering it brings you here or you clicked the link in the email and now you're going to be accepted as an admin 

        $pending_admin = PendingAdmin::where('email', $request->email)->get();
        // Auth::guard('pending_admin')->id();
        if ($pending_admin) {
            $user = User::where('email', $request->email)->first();

            if (Auth::guard('pending_admin')->user() !== null) {
                $pending_admin = Auth::guard('pending_admin')->user();
                $pending_admin = [$pending_admin];
                $token = $request->header('Authorization');
            } else {
                $token = $request->token;

            }

            foreach ($pending_admin as $eachPendingAdmin) {

                $sub_channel_id = $eachPendingAdmin->sub_channel_id;
                $channel_id = $eachPendingAdmin->channel_id;

                $subscribeController = new subscribeController();

                if ($channel_id === null && $sub_channel_id === null) {
                    return $this->error(null, 'channel id or subchannel id required', 400);

                } elseif ($channel_id === null && $sub_channel_id !== null) {
                    $subChannel = SubChannel::where('id', $sub_channel_id)->first();

                    if ($subChannel->admin_id === $user->id) {
                        return $this->error(null, 'Already an admin of the subchannel', 400);

                    } else {

                        $subChannel->update([
                            'admin_id' => $user->id
                        ]);

                        $data = [
                            'user_id' => $user->id,
                            'sub_channel_id' => $subChannel->id,
                        ];

                        $myRequest = Request::create('/subscription/subchannel', 'POST', $data);
                        $myRequest->headers->set('Authorization', $token);

                        $subscriptionRequest = $subscribeController->toggleSubChannelSubscription($myRequest);

                    }

                } elseif ($channel_id !== null && $sub_channel_id === null) {
                    $channel = Channel::where('id', $channel_id)->first();
                    if ($channel->super_admin_id === $user->id) {
                        return $this->error(null, 'Already an admin of the channel', 400);

                    } else {

                        $channel->update([
                            'super_admin_id' => $user->id
                        ]);

                        if ($channel->institution_id !== null) {
                            $data = [
                                'user_id' => $user->id,
                                'primary_institution_id' => $channel->institution_id,
                            ];

                            $updateUserDetailsController = new updateUserDetailsController();

                            $myRequest = Request::create('/updatePrimaryInstitution', 'POST', $data);
                            $myRequest->headers->set('Authorization', $token);

                            $updatePrimaryInstitutionRequest = $updateUserDetailsController->updatePrimaryInstitution($myRequest);
                        }

                        if ($channel->is_primary != true) {
                            $data = [
                                'user_id' => $user->id,
                                'channel_id' => $channel->id,
                            ];

                            $myRequest = Request::create('/subscription', 'POST', $data);
                            $myRequest->headers->set('Authorization', $token);

                            $subscriptionRequest = $subscribeController->toggleChannelSubscription($myRequest);
                        }

                    }
                }

                try {
                    if ((isset($subscriptionRequest) && isset($updatePrimaryInstitutionRequest)) ||
                        (isset($subscriptionRequest) && !isset($updatePrimaryInstitutionRequest)) ||
                        (!isset($subscriptionRequest) && isset($updatePrimaryInstitutionRequest))) {

                        $eachPendingAdmin->delete();

                    }
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                    $errorCode = $e->getCode();

                    if (isset($error)) {
                        return $this->error(null, $error, $errorCode);
                    }
                }
            }

            return $this->success(['message' => 'Admin request successfully accepted.'], 201);
        }

    }
}
