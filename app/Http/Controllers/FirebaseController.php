<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;

class FirebaseController extends Controller
{
    public function togglePushNotificationChannel(?string $topicOrToken, ?string $title, ?string $body, ?string $condition = null, bool $is_topic = true) : bool
    {
        try {
            $messaging = app('firebase.messaging');
            $title ??= "You've got a new post, check your feed";
            $messageBody = $body ?? 'There is a new notification for this feed';

            $notification = Notification::create($title, $messageBody);
            $data = [
                'priority' => 'high',
                'vibrate' => 'true',
                'sound' => 'true',
            ];

            if ($is_topic) {
                $condition 
                    ? $message = CloudMessage::withTarget('condition', $condition)
                        ->withNotification($notification)
                            ->withData($data) 
                    : $message = CloudMessage::withTarget('topic', $topicOrToken)
                        ->withNotification($notification)
                            ->withData($data);
            } else {
                $message = CloudMessage::withTarget('token', $topicOrToken)
                    ->withNotification($notification)
                    ->withData($data);
            }

            $messaging->send($message);
            return true;
        } catch (Exception $exception) {
            Log::error("Notification Failure: $exception");
            return false;
        }
    }

    /*
    *Subscribe to a topic in fcm
    */
    public function subscribeToTopic($topic, $deviceToken) : bool
    {
        try {
            // Initialize the Firebase Admin SDK and subscribe to the topic
            $messaging = app('firebase.messaging');
            $messaging->subscribeToTopic($topic, $deviceToken);
            return true;
        } catch (Exception $exception) {
            Log::error("Topic Subscription Failure: $exception");
            return false;
        }
    }

    /*
    *Unsubscribe from a topic in fcm
    */
    public function unsubscribeFromTopic($topic, $deviceToken) : bool
    {
        try {
            // initialize the firebase admin sdk and unsubscribe from the topic
            $messaging = app('firebase.messaging');
            $messaging->unsubscribeFromTopic($topic, $deviceToken);
            return true; 
        } catch (Exception $exception) {
            Log::error("Topic Unsubscription Failure: $exception");
            return false;
        }
    }

    /*
    *Used for creating new topic for the Channel model
    * used by the web admin
    **/
    public function createTopic($topic)
    {
        // $topic = $request->input('topic_name');

        try {
            // Initialize the Firebase Admin SDK
            $messaging = app('firebase.messaging');

            // Create the topic
            $topicManagement = $messaging->getTopicManagement();
            /** @var TopicManagementResponse $response */
            $response = $topicManagement->createTopic($topic);

            // Check if the topic creation was successful
            if ($response->isSuccess()) {
                return response()->json(['message' => 'Topic created successfully.'], 200);
                // return true;
            } else {
                // If not successful, handle the error
                return response()->json(['error' => $response->error()], 500);
                // return false;
            }
        } catch (Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
            // return false;
        }
    }

    /*
    * In a case where the device_token is changed
    * 
    */
    public function unsubscribedFromAllTopics($deviceToken) : bool
    {
        try {
            // initialize the firebase admin sdk and unsubscribe from the topic
            $messaging = app('firebase.messaging');
            $messaging->unsubscribeFromAllTopics($deviceToken);
            return true;
        } catch (Exception $exception) {
            Log::error("Topics Unsubscription Failure: $exception");
            return false;
        }
    }

    public function subscribeToTopics(array $topics, $deviceToken) : bool
    {
        try {
            $messaging = app('firebase.messaging');
            $messaging->subscribeToTopics($topics, $deviceToken);
            return true;
        } catch (Exception $exception) {
            Log::error("Topics Subscription Failure: $exception");
            return false;
        }
    }
}
