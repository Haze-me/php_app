<?php

namespace App\Imports;

use App\Models\Channel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ChannelsImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Channel([
            'name' => $row['name'],
            'profileImage' => $row['profile_image'],
            'description' => $row['description'],
            'type' => $row['type'],
            'targetAudience' => $row['target_audience'],
            'super_admin_id' => $row['super_admin_id'],
            'institution_id' => $row['institution_id'],
            'channelWebsite' => $row['channelWebsite'],
            'topic_name' => $row['topic_name'],
        ]);
    }
}
