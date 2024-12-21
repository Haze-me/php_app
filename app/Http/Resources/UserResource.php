<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_id' => $this->tracking_id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'user_type' => $this->user_type,
            'username' => $this->username,
            'user_image' => $this->user_image,
            'email_verified_at' => $this->email_verified_at,
            'primary_institution_id' => (int)$this->primary_institution_id,
            'saved_posts' => json_decode($this->saved_posts, true) ?: [],
            'reset_password' => $this->reset_password,
            'channels_subscribed' => json_decode($this->channels_subscribed, true) ?: [],
            'subchannels_subscribed' => json_decode($this->subchannels_subscribed, true) ?: [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
