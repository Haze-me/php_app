<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserService
{
    protected $userDeviceToken;

    public function getDeviceTokenForUser(int|string $id): string
    {
        try {
            $user = User::findOrFail($id);
            return $this->userDeviceToken = $user->device_token;
        } catch (ModelNotFoundException $model_error) {
            return $model_error->getMessage();
        }
    }
}