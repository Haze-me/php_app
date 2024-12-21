<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubChannelResource extends JsonResource
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
            'name' => $this->name,
            'profileImage' => $this->profileImage,
            'description' => $this->description,
            'rating' => (float)$this->rating,
            'type' => $this->type,
            'category' => $this->category,
            'targetAudience' => $this->targetAudience,
            'subscribers' => (int)$this->subscribers,
            'admin_id' => (int)$this->admin_id,
            'admin' => new UserResource($this->admin),
            'subchannelWebsite' => $this->subchannelWebsite,
            'status' => (int)$this->status,
            'deleted' => $this->deleted,
            'primary_institution_id' => (int)$this->primary_institution_id
        ];
    }
}
