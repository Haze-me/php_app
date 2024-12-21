<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
     * @OA\Info(
     *      version="1.0.0",
     *      title="SILFRICA Swagger API Documentation with Sanctum Auth ",
     *      description="Implementation of Swagger in Laravel 10",
     *      @OA\Contact(
     *          email="diochuks65@gmail.com"
     *      ),
     *      @OA\License(
     *          name="Apache 2.0",
     *          url="http://www.apache.org/licenses/LICENSE-2.0.html"
     *      )
     * )
     *
     * @OA\Server(
     *      url="https://test-api.silfrica.com",
     *      description="Silfrica API Server"
     * )

     *
     *
     */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    // protected $firebase;
    // protected $inviteAdmin;
    // protected $topicControl;

    // public function __construct(FirebaseController $firebase, InviteAdminController $inviteAdmin, TopicController $topicControl)
    // {
    //     $this->firebase = $firebase;
    //     $this->inviteAdmin = $inviteAdmin;
    //     $this->topicControl = $topicControl;
    //     parent::__construct();
    // }
}
