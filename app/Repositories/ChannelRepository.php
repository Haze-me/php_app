<?php

namespace App\Repositories;

use App\Models\Channel;
use Illuminate\Database\Eloquent\Collection;

class ChannelRepository
{
   public function getOneChannel(int|string $id): Channel
   {
      return Channel::find($id);
   }

   public function newChannel(array $attributes): Channel
   {
      return Channel::create($attributes);
   }

   public function getChannelByType(string $type): Channel
   {
      return Channel::where('type', $type)->first();
   }

   public function getSuperAdminChannel(int|string $id, int|string $admin_id): Channel
   {
      return Channel::where([
         ['super_admin_id', $admin_id],
         ['id', $id]
      ])->first();
   }

   public function getChannelBySuperId(int $id): Channel
   {
      return Channel::where('super_admin_id', $id)->first();
   }

   public function getChannelByPrimary(int|string $id, int|bool $bool): Channel
   {
      return Channel::where('id', $id)->where('is_primary', $bool)->first();
   }

   public function getOneChannelSubChannels(int|string $id): array
   {
      $channel = Channel::findOrFail($id);
      return collect(json_decode($channel->sub_channels, true))->filter()->toArray();
   }

   public function getOneChannelSubAdmins(int|string $id): array
   {
      $channel = Channel::findOrFail($id);
      return collect(json_decode($channel->sub_admins, true))->filter()->toArray();
   }

   public function getChannelsByInstitutionId(int|string $id): Channel|Collection
   {
      return Channel::where('institution_id', $id)->get();
   }
}
