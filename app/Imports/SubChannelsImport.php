<?php

namespace App\Imports;

use App\Models\SubChannel;
use App\Models\User;
use App\Models\Channel;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\FirebaseController;
use App\Services\InviteAdminService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;

class SubChannelsImport implements ToCollection, WithHeadingRow
{
    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try{
                $rowArray = $row->toArray();
                $validatedData = $this->validateRow($rowArray);

                $super_admin_channel = Channel::where([
                    ['super_admin_id', $this->userId],
                    ['type', 'administration']
                ])->first();

                if (!$super_admin_channel) {
                    throw new \Exception('Forbidden: Not a Super Admin Permission!');
                }

                $channelInstitutionId = $super_admin_channel->institution_id;

                // Generate random topic name
                $topicController = new TopicController();
                $randomTopic = $topicController->generateRandomTopic();

                DB::beginTransaction();

                $subChannel = new SubChannel([
                    'channel_id' => $super_admin_channel->id,
                    'name' => $validatedData['name'],
                    'description' => $validatedData['description'],
                    'type' => $validatedData['type'],
                    'category' => $validatedData['category'],
                    'targetAudience' => $validatedData['audience'],
                    'admin_id' => $this->userId,
                    'primary_institution_id' => $channelInstitutionId,
                    'topic_name' => $randomTopic,
                ]);

                $subChannel->save();
                DB::commit();

                $this->subscribeUserToSubChannel($this->userId, $subChannel->id);
                $this->updateChannelSubAdminsAndSubChannels($super_admin_channel->id, $subChannel->id);

                // Create new topic in Firebase
                $firebase = new FirebaseController();
                $firebase->subscribeToTopic($subChannel->topic_name, User::find($this->userId)->device_token);

                // call inviteAdminService for its service and create new pending_admin model for each of the subchannel created
                $pa_data = [
                    'channel_id' => $super_admin_channel->id,
                    'sub_channel_id' => $subChannel->id,
                    'user_id' => $this->userId,
                    'email_invited' => $validatedData['email'],
                    'email_body' => "You've been assigned for this service!"
                ];
                $ivc = new InviteAdminService();
                $ivc->handleAdminInvite($pa_data);
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'description' => 'required|string|max:160',
            'type' => 'required|string',
            'category' => 'required|string',
            'audience' => 'required|string',
            'email' => 'required|string|email',
        ];
    }

    private function validateRow(array $row)
    {
        return Validator::make($row, $this->rules())->validate();
    }

    private function subscribeUserToSubChannel($userId, $subChannelId)
    {
        try {
            DB::beginTransaction();
            $user = User::findOrFail($userId);
            $subchannels_subscribed = json_decode($user->subchannels_subscribed, true) ?: [];
            $subchannels_subscribed[] = $subChannelId;
            $user->subchannels_subscribed = json_encode($subchannels_subscribed);
            $user->save();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        
    }

    private function updateChannelSubAdminsAndSubChannels($channelId, $subChannelId)
    {
        try {
            DB::beginTransaction();
            $channel = Channel::find($channelId);
            $subAdmins = json_decode($channel->sub_admins, true) ?: [];

            if (!in_array($this->userId, $subAdmins)) {
                $subAdmins[] = $this->userId;
                $channel->sub_admins = json_encode($subAdmins, JSON_UNESCAPED_UNICODE);
            }

            $subChannels = json_decode($channel->sub_channels, true) ?: [];
            $subChannels[] = $subChannelId;
            $channel->sub_channels = json_encode(array_values($subChannels));
            $channel->save();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
