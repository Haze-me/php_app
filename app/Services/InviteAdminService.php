<?php

namespace App\Services;

use App\Http\Controllers\FirebaseController;
use App\Models\SubChannel;
use App\Models\PendingAdmin;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvitationMail;
use App\Mail\InvitationNoUrlMail;
use App\Repositories\ChannelRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InviteAdminService
{
   public function handleAdminInvite(array $data)
   {
      try {
         $channelRepository = new ChannelRepository();

         $channel_id = $data['channel_id'];
         $sub_channel_id = $data['sub_channel_id'];
         $channel_name = '';

         $superadmin = $channelRepository->getSuperAdminChannel($channel_id, $data['user_id']);
         $channel_name = $superadmin->name;

         if (!$superadmin && $this->validateInviteAuth($data['user_id'])) {
            throw new \Exception('Unauthorized', 401);
         } elseif ($sub_channel_id !== null) {
            $subOrChannelPendingAdminExists = PendingAdmin::where('sub_channel_id', $sub_channel_id)->first();
            $getSubChannel = SubChannel::where('id', $sub_channel_id)->first();
            $channel_name = $getSubChannel->name;
         } elseif ($sub_channel_id === null) {
            $subOrChannelPendingAdminExists = PendingAdmin::where([
               ['channel_id', $channel_id],
               ['sub_channel_id', null]
            ])->first();
         }

         DB::beginTransaction();
         if ($subOrChannelPendingAdminExists) {
            if ($subOrChannelPendingAdminExists->email === $data['email_invited']) {
               $addPendingAdmin = $subOrChannelPendingAdminExists->update([
                  'email' => $data['email_invited'],
               ]);
            } else {
               $uuid = Str::uuid();
               $addPendingAdmin = $subOrChannelPendingAdminExists->update([
                  'email' => $data['email_invited'],
                  'uuid' => $uuid,
               ]);
            }
            $pendingAdmin = $subOrChannelPendingAdminExists->refresh();
            $uuid = $pendingAdmin->uuid;
         } else {
            $uuid = Str::uuid();
            $toStoreData = [
               'email' => $data['email_invited'],
               'sub_channel_id' => $data['sub_channel_id'],
               'channel_id' => $data['channel_id'],
               'uuid' => $uuid
            ];
            $addPendingAdmin = PendingAdmin::create($toStoreData);
         }
         DB::commit();

         if (!$addPendingAdmin) {
            throw new \Exception('Error while creating pending admin!', 422);
         }

         if (!$this->handleAdminMail($data, $channel_name, $uuid)) {
            throw new \Exception('Mail failed to send!', 500);
         }

         return 'Invitation email sent successfully';
      } catch (\Throwable $th) {
         DB::rollback();
         Log::error($th->getMessage());
         throw $th;
      }
   }

   public function validateInviteAuth(int|string $id): bool
   {
      return $id != Auth::guard('sanctum')->id();
   }

   public function handleAdminMail(array $data, string $channel_name, string $uuid): bool
   {
      try {
         $userRepository = new UserRepository();
         $checkIfUserExists = $userRepository->getUserByEmail($data['email_invited']);
         $inviter = $userRepository->getUserById($data['user_id']);
         $inviterFullName = "$inviter->firstname $inviter->lastname";
         $mailData = [
            'subject' => "$inviterFullName Invited you to Administrate",
            'title' => "$inviterFullName has invited you to manage $channel_name on Silfrica",
            'body' => "$inviterFullName : " .$data['email_body'],
            'url' => env('APP_URL') . '/api/accepted/invite/' . $uuid,
         ];
         if ($checkIfUserExists) {
            // Code to send the email here with url
            Mail::to($data['email_invited'])->send(new InvitationMail($mailData));
            $firebase = new FirebaseController();
            $firebase->togglePushNotificationChannel($checkIfUserExists->device_token, $mailData['title'], $mailData['body'], null, false);
         } else {
            // Code to send the email here with no url
            Mail::to($data['email_invited'])->send(new InvitationNoUrlMail($mailData));
         }

         return true;
      } catch (\Throwable $th) {
         throw $th;
      }
   }
}
