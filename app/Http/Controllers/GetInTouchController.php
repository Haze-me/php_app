<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\GetInTouch;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Validator;
use App\Policies\SpamPolicy;
use Illuminate\Support\Facades\Mail;

class GetInTouchController extends Controller
{
    use HttpResponses;

    /**
        * @OA\Post(
        * path="/api/email/notify",
        * operationId="getInTouchWithEmail",
        * tags={"Get-in-Touch"},
        * summary="Send email with link/no link to notify User",
        * description="Send email with link/no link to notify User(protected)",
        *     @OA\RequestBody(
        *         @OA\JsonContent(),
        *         @OA\MediaType(
        *            mediaType="multipart/form-data",
        *            @OA\Schema(
        *               type="object",
        *               required={"user_id", "poster_email", "message"},
        *               @OA\Property(property="user_id", type="text"),
        *               @OA\Property(property="poster_email", type="email"),
        *               @OA\Property(property="message", type="text"),
        *            ),
        *        ),
        *    ),
        *      @OA\Response(
        *          response=201,
        *          description="Email sent successfully",
        *          @OA\JsonContent()
        *       ),
        *      @OA\Response(
        *          response=200,
        *          description="Email sent successfully",
        *          @OA\JsonContent()
        *       ),
        *      @OA\Response(
        *          response=422,
        *          description="Unprocessable Entity",
        *          @OA\JsonContent()
        *       ),
        *      @OA\Response(response=400, description="Bad request"),
        *      @OA\Response(response=401, description="Unauthorized User", @OA\JsonContent()),
        *      @OA\Response(response=404, description="Resource Not Found"),
        *      @OA\Response(response=451, description="Content flagged!", @OA\JsonContent()),
        * )
    */

    public function getInTouchWithEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'poster_email' => 'required|exists:users,email',
            'message' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }
        try {
            $message = $request->message;
            $user = User::findOrFail($request->user_id);
            $receiverEmail = User::where('email', $request->poster_email)->first();
            $userFullName = $user->firstname.' '.$user->lastname;
            $mailData = [
                'title' => $userFullName.' wants to connect with you!',
                'message' => $message,
                'email_requested' => $user->email,
            ];
            if ((new SpamPolicy)->isCleanContent($message)) {
                // Code to send the email here
                Mail::to($request->poster_email)->send(new GetInTouch($mailData));
                $firebase = new FirebaseController();
                $firebase->togglePushNotificationChannel($receiverEmail->device_token, $mailData['title'], $mailData['message'], false);
                return $this->success([],'Connection request successful!');
            } else {
                return $this->error([], 'Content flagged!', 451);
            }
        } catch (\Throwable $th) {
            return $this->error([], 'Failed to process the request', 500);
        }
    }
}
