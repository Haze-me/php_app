<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    /**
     * Get a user by their email address.
     *
     * @param string $email The email address of the user.
     * @return User|null The user if found, otherwise null.
     */
    public function getUserByEmail(string $email): User|NULL
    {
        return User::where('email', $email)->first();
    }

    /**
     * Get a user by their ID.
     *
     * @param int $id The ID of the user.
     * @return User The user if found.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If no user is found.
     */
    public function getUserById(int $id): User
    {
        return User::findOrFail($id);
    }

    /**
     * Get a user by their username.
     *
     * @param string $username The username of the user.
     * @return User|null The user if found, otherwise null.
     */
    public function getUserByUsername(string $username): User|NULL
    {
        return User::where('username', $username)->first();
    }

    /**
     * Get a user by their username or email address.
     *
     * @param string $usernameOrEmail The username or email address of the user.
     * @return User|null The user if found, otherwise null.
     */
    public function getUserByUsernameOrEmail(string $usernameOrEmail): User|NULL
    {
        return User::where('username', $usernameOrEmail)->orWhere('email', $usernameOrEmail)->first();
    }

    /**
     * Get a user by their tracking ID.
     *
     * @param string $trackingId The tracking ID of the user.
     * @return User|null The user if found, otherwise null.
     */
    public function getUserByTrackingId(string $trackingId): User|NULL
    {
        return User::where('tracking_id', $trackingId)->first();
    }

    /**
     * Get a user by their primary institution ID.
     *
     * @param int $id The ID of the primary institution.
     * @return User|null The user if found, otherwise null.
     */
    public function getUserByPrimaryInstitutionId(int $id): User|NULL
    {
        return User::where('primary_institution_id', $id)->first();
    }

    /**
     * Get the channels the user is subscribed to.
     *
     * @param User $user The user instance.
     * @return array An array of channel IDs the user is subscribed to.
     */
    public function getUserChannelsSubscribed(User $user): array
    {
        return collect(json_decode($user->channels_subscribed, true))->filter()->toArray();
    }

    /**
     * Get the sub-channels the user is subscribed to.
     *
     * @param User $user The user instance.
     * @return array An array of sub-channel IDs the user is subscribed to.
     */
    public function getUserSubChannelsSubscribed(User $user): array
    {
        return collect(json_decode($user->subchannels_subscribed, true))->filter()->toArray();
    }
}
