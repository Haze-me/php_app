<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ChannelFactory extends Factory
{
    protected $model = Channel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'profileImage' => $this->faker->word(),
            'description' => $this->faker->text(),
            'type' => $this->faker->word(),
            'rating' => $this->faker->numberBetween(0,5),
            'subscribers' => $this->faker->randomNumber(),
            'sub_admins' => json_encode($this->faker->randomDigitNotZero()),
            'sub_channels' => json_encode($this->faker->randomDigitNotZero()),
            'topic_name' => $this->faker->name(),
            'channelWebsite' => $this->faker->url(),
            // 'suspended_admins' => $this->faker->words(),
            // 'pending_admins' => $this->faker->words(),
            // 'removed_admins' => $this->faker->words(),
            'is_primary' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'institution_id' => Institution::factory(),
            'super_admin_id' => User::factory(),
        ];
    }
}
