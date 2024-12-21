<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;


class testRegisterController extends Controller
{
    //
    use HttpResponses;
    
    public function testRegister(Request $request)
    {
        $validatedData = $request->validate([
            'firstname' => 'required|max:100',
            'lastname' => 'required|max:100',
            'email' => 'required|email|unique:users,email',
            'user_type' => 'required',
            'username' => 'required|string|unique:users|max:15',
            'password' => 'required|min:8',
            'provider' => 'required'
        ]);
        
        $user = User::create($validatedData);
        
        return $this->success([
            'user' => $user,
            'token' => $user->createToken('mobile')->plainTextToken
        ]);

    }

    public function testLogin(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6',
        ]);
        $user = User::where('email', $validatedData['email'])->first();
        if (! $user ||! Hash::check($validatedData['password'], $user->password)) {
            return $this->error([],'Credentials not match', 401);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->success($token, 'Login Successful');

    }

    public function test()
    {
        $inviterFullName = 'Response Test';
        $body = 'Lorem ipsum dolor sit amet.';
        $uuid = '62866-892endg12d-2e9379qd-210-m';
        $mailData = [
            'subject' => $inviterFullName.' Invited you to Administrate',
            'title' => $inviterFullName.' has invited you to manage the Beta test program channel on Silfrica',
            'body' => $inviterFullName.': '.$body,
            'url' => env('APP_URL').'/api/accepted/invite/'.$uuid ,
        ];
        return response()->json($mailData);
    }

    public function testNotification(Request $request)
    {
        $token = $request->device_token;
        try {
            $firebase = new FirebaseController();
            $sendNotification = $firebase->togglePushNotificationChannel($token, 'Test notification', 'Some dummy text', false);
            if (!$sendNotification) {
                return $this->error([], 'Notification failed', 503);
            }
            return $this->success([], 'Notification sent', 200);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 'Error occurred sending notification', 500);
        }
    }

    public function testNotificationWithTopic(Request $request)
    {
        $topic = $request->topic;
        try {
            $firebase = new FirebaseController();
            $sendNotification = $firebase->togglePushNotificationChannel($topic, 'Test', 'Some dummy text', true);
            if (!$sendNotification) {
                return $this->error([], 'Notification failed', 503);
            }
            return $this->success([], 'Notification sent', 200);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 'Error occurred sending notification', 500);
        }
    }

    public function testSubscribeToTopic(Request $request)
    {
        $topic = $request->topic;
        $deviceToken = $request->device_token;
        try {
            $firebase = new FirebaseController();
            
            $sendNotification = $firebase->subscribeToTopic($topic, $deviceToken);
            if (!$sendNotification) {
                return $this->error([], 'subscription failed', 503);
            }                                                                                                                                                                                                                                                                                                                                                                                                                                               
            return $this->success([], 'subscription succeed', 200);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 'Error occurred sending subscription', 500);
        }
    }

    public function testTopicName()
    {
        try {
            $topicController = new TopicController();
            $generateTopicName = $topicController->generateRandomTopic();
            return $generateTopicName;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}
