<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\Post;
use App\Models\SubChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'post_title' => $this->faker->word(),
            'post_body' => $this->faker->word(),
            'post_images' => $this->faker->imageUrl(),
            'viewType' => $this->faker->word(),
            'count_view' => $this->faker->randomNumber(),
//            'users_viewed' => [2],
            'deleted' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'channel_id' => Channel::factory(),
            'sub_channel_id' => SubChannel::factory(),
            'poster_id' => User::factory(),
            'uuid' => $this->faker->ipv4(),
        ];
    }
}
