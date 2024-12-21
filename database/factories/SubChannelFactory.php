<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\Institution;
use App\Models\SubChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubChannelFactory extends Factory
{
    protected $model = SubChannel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'profileImage' => $this->faker->imageUrl(),
            'description' => $this->faker->text(),
            'type' => $this->faker->word(),
            'category' => $this->faker->word(),
            'targetAudience' => $this->faker->word(),
            'subscribers' => $this->faker->randomNumber(),
            'subchannelWebsite' => $this->faker->url(),
            'primary_institution_id' => Institution::factory(),
            'status' => $this->faker->numberBetween(0, 2),
            'deleted' => $this->faker->boolean(),
            'topic_name' => $this->faker->name(),

            'admin_id' => User::factory(),
            'channel_id' => Channel::factory(),
        ];
    }
}
