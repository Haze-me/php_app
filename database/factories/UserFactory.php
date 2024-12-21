<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\Institution;
use App\Models\SubChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'firstname' => $this->faker->firstName(),
            'lastname' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'username' => $this->faker->userName(),
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('Password22'),
            'user_type' => $this->faker->word(),
            'provider' => $this->faker->word(),
            'tracking_id' => $this->faker->word(),
            // 'channels_subscribed' => '',
            // 'subchannels_subscribed' => '',
            'saved_posts' => null,
            'remember_token' => Str::random(10),
            'otp' => $this->faker->word(),
            'reset_password' => $this->faker->boolean(),
            'device_token' => Str::random(10),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'primary_institution_id' => Institution::factory(),
        ];
    }
}
