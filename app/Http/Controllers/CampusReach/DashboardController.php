<?php

namespace App\Http\Controllers\CampusReach;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Institution;
use App\Models\PendingAdmin;
use App\Models\Post;
use App\Models\SubChannel;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\ChannelResource;
use App\Http\Resources\SubChannelResource;

class DashboardController extends Controller
{
    public function getAdminInstitutionPosts(int $adminId, int $institutionId)
    {
        $totalPosts = Post::whereHas('channel', function ($query) use ($adminId, $institutionId) {
            $query->where([
                'super_admin_id' => $adminId,
                'institution_id' => $institutionId
            ]);
        })->count();

        $last7DaysPosts = Post::whereHas('channel', function ($query) use ($adminId, $institutionId) {
            $query->where([
                'super_admin_id' => $adminId,
                'institution_id' => $institutionId
            ]);
        })->where('created_at', '>=', now()->subDays(7))->count();

        $last30DaysPosts = Post::whereHas('channel', function ($query) use ($adminId, $institutionId) {
            $query->where([
                'super_admin_id' => $adminId,
                'institution_id' => $institutionId
            ]);
        })->where('created_at', '>=', now()->subDays(30))->count();

        return [
            'total' => $totalPosts,
            'stats' => [
                'all' => $totalPosts,
                'last_7_days' => $last7DaysPosts,
                'last_30_days' => $last30DaysPosts
            ]
        ];
    }

    public function getCampuspopulation(int $institution_id): int
    {
        $users = User::where('primary_institution_id', $institution_id)->count();
        return $users;
    }

    public function getChannelsManaged(int $adminId): array
    {
        $channels = Channel::where('super_admin_id', $adminId)->get();
        $subChannels = SubChannel::whereIn('admin_id', $channels->pluck('super_admin_id'))->get();

        return [$channels, $subChannels];
    }

    public function getSubAdminsUnderAdminChannels(int $adminId)
    {
        $subAdmins = User::whereHas('channels', function ($query) use ($adminId) {
            $query->where('super_admin_id', $adminId);
        })->orWhereHas('subChannels', function ($query) use ($adminId) {
            $query->whereHas('channel', function ($subQuery) use ($adminId) {
                $subQuery->where('super_admin_id', $adminId);
            });
        })->get();

        return $subAdmins->map(function ($subAdmin) {
            return [
                'id' => $subAdmin->id,
                'firstname' => $subAdmin->firstname,
                'lastname' => $subAdmin->lastname,
                'email' => $subAdmin->email,
                'channels' => ChannelResource::collection($subAdmin->channels),
                'sub_channels' => SubChannelResource::collection($subAdmin->subChannels),
            ];
        });
    }

    public function getPendingAdmins(array $channelIds, array $subChannelIds)
    {
        return PendingAdmin::whereIn('channel_id', $channelIds)
            ->orWhereIn('sub_channel_id', $subChannelIds)
            ->get(['email', 'channel_id', 'sub_channel_id'])
            ->map(function ($pendingAdmin) {
                return [
                    'email' => $pendingAdmin->email,
                    'channel_id' => $pendingAdmin->channel_id,
                    'sub_channel_id' => $pendingAdmin->sub_channel_id
                ];
            });
    }

    public function getAdminInstitution(int $id)
    {
        return Institution::find($id);
    }

    public function getChannelActivityByInstitution($institutionId): array
   {
      // Query for posts associated with channels and sub_channels belonging to the institution

      // For all posts
      $allPostsCount = Post::whereHas('channel', function ($query) use ($institutionId) {
            $query->where('institution_id', $institutionId);
      })
      ->orWhereHas('sub_channel', function ($query) use ($institutionId) {
            $query->whereHas('channel', function ($subQuery) use ($institutionId) {
               $subQuery->where('primary_institution_id', $institutionId);
            });
      })->count();

      // For posts in the last 7 days
      $last7DaysPostsCount = Post::whereHas('channel', function ($query) use ($institutionId) {
            $query->where('institution_id', $institutionId);
      })
      ->orWhereHas('sub_channel', function ($query) use ($institutionId) {
            $query->whereHas('channel', function ($subQuery) use ($institutionId) {
               $subQuery->where('primary_institution_id', $institutionId);
            });
      })
      ->where('created_at', '>=', now()->subDays(7))->count();

      // For posts in the last 30 days
      $last30DaysPostsCount = Post::whereHas('channel', function ($query) use ($institutionId) {
            $query->where('institution_id', $institutionId);
      })
      ->orWhereHas('sub_channel', function ($query) use ($institutionId) {
            $query->whereHas('channel', function ($subQuery) use ($institutionId) {
               $subQuery->where('primary_institution_id', $institutionId);
            });
      })
      ->where('created_at', '>=', now()->subDays(30))->count();

      // Return the response with counts
      return [
         'total_posts' => $allPostsCount,
         'last_7_days_posts' => $last7DaysPostsCount,
         'last_30_days_posts' => $last30DaysPostsCount,
      ];
   }
}
