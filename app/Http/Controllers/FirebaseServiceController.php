<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;

class FirebaseServiceController extends Controller
{
    public function sendNotification(string $deviceToken, string $title, string $body, array $data = [])
    {
        $apiKey = config('services.fcm.api_key');
        $projectId = config('services.fcm.project_id');
        $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

        $curl = curl_init($url);

        $payload = [
            "to" => $deviceToken,
            "notification" => [
                "title" => $title,
                "body" => $body
            ],
        ];

        if (!empty($data)) {
            $payload["data"] = $data;
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $apiKey",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            throw new Exception("FCM Notification Error: $error");
        }

        return json_decode($response, true);
    }
}
