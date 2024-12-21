<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'profileImage' => $this->profileImage,
            'description' => $this->description,
            'type' => $this->type,
            'institution_id' => (integer)$this->institution_id,
            'is_primary' => $this->is_primary,
            'rating' => (float)$this->rating,
            'subscribers' => (integer)$this->subscribers,
            'super_admin_id' => (integer)$this->super_admin_id,
            'sub_admins' => json_decode($this->sub_admins, true),
            'sub_channels' => json_decode($this->sub_channels, true),
            'channelWebsite' => $this->channelWebsite,
            'suspended_admins' => json_decode($this->suspended_admins, true),
            'pending_admins' => json_decode($this->pending_admins, true),
            'removed_admins' => json_decode($this->removed_admins, true)
        ];
    
        $data['institution'] = new InstitutionResource($this->institution);
        
        return $data;
        
    }
}
