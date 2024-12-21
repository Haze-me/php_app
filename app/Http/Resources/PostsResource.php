<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $channel = $this->channel ? new ChannelResource($this->channel) : null;
        $subChannel = $this->sub_channel ? new SubChannelResource($this->sub_channel) : null;
        $poster = $this->poster ? new UserResource($this->poster) : null;
 
        return [
            'id' => $this->id,
            'channel_id' => $channel ? $channel->id : null,
            'channel_name' => $channel ? $channel->name : null,
            'channel_image' => $channel ? $channel->profileImage : null,
            'channel_type' => $channel ? $channel->type : null,
            'sub_channel_id' => $subChannel ? $subChannel->id : null,
            'sub_channel_name' => $subChannel ? $subChannel->name : null,
            'sub_channel_profileImage' => $subChannel ? $subChannel->profileImage : null,
            'sub_channel_category' => $subChannel ? $subChannel->category : null,
            'poster_id' => $poster ? $poster->id : null,
            'poster_firstname' => $poster ? $poster->firstname : null,
            'poster_lastname' => $poster ? $poster->lastname : null,
            'poster_email' => $poster ? $poster->email : null,
            'post_title' => $this->post_title,
            'post_body' => $this->post_body,
            'post_images' => json_decode($this->post_images),
            'viewType' => $this->viewType,
            'view_count' => (int)$this->count_view,
            'users_viewed' => json_decode($this->users_viewed, true),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
