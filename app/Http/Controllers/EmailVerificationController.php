<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use App\Notifications\VerifyEmailNotification;
use App\Models\User;
use App\Notifications\signUpDoneNotification;
use App\Traits\HttpResponses;

class EmailVerificationController extends Controller
{
    use HttpResponses;

    /**
        * @OA\Post(
        * path="/api/email/otp/verify",
        * operationId="verifyOtpEmail",
        * tags={"Authentication"},
        * summary="Verify email with otp",
        * description="Email verification using otp(protected)",
        *     @OA\RequestBody(
        *         @OA\JsonContent(),
        *         @OA\MediaType(
        *            mediaType="multipart/form-data",
        *            @OA\Schema(
        *               type="object",
        *               required={"user_id", "otp"},
        *               @OA\Property(property="user_id", type="text"),
        *               @OA\Property(property="otp", type="text"),
        *            ),
        *        ),
        *    ),
        *      @OA\Response(
        *          response=201,
        *          description="Email verified successfully",
        *          @OA\JsonContent()
        *       ),
        *      @OA\Response(
        *          response=200,
        *          description="Email verified successfully",
        *          @OA\JsonContent()
        *       ),
        *      @OA\Response(
        *          response=422,
        *          description="Invalid OTP",
        *          @OA\JsonContent()
        *       ),
        *      @OA\Response(response=400, description="Bad request"),
        *      @OA\Response(response=401, description="Unauthorized User", @OA\JsonContent()),
        *      @OA\Response(response=404, description="Resource Not Found"),
        * )
    */

    public function verifyOtpEmail(Request $request)
    {
        $user_id = $request->user_id;
        $otp = $request->otp;

        $checkUserOtp = User::where([
            ['id', $user_id],
            ['otp', $otp]
        ])->first();

        // Check if the user has already been verified
        if(!$checkUserOtp){
            return $this->error(null, 'OTP is invalid', 422);
        }

        if ($checkUserOtp->email_verified_at === null) {
            $checkUserOtp->email_verified_at = now();
            $checkUserOtp->save();
            $checkUserOtp->notify(new signUpDoneNotification($checkUserOtp));
            return $this->success(null, 'Verification success', 201);
        } else {
            return $this->error(null, 'Email already verified', 422);
        }

    }

    /**
        * @OA\Post(
        * path="/api/email/otp/resend",
        * operationId="resendOtpVerification",
        * tags={"Authentication"},
        * summary="Resend email with otp",
        * description="Sends otp email to user(protected)",
        *     @OA\RequestBody(
        *         @OA\JsonContent(),
        *         @OA\MediaType(
        *            mediaType="multipart/form-data",
        *            @OA\Schema(
        *               type="object",
        *               required={"user_id"},
        *               @OA\Property(property="user_id", type="text"),
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
        *          description="Unprocessable entity",
        *          @OA\JsonContent()
        *       ),
        *      @OA\Response(response=400, description="Bad request"),
        *      @OA\Response(response=401, description="Unauthorized User", @OA\JsonContent()),
        *      @OA\Response(response=404, description="Resource Not Found"),
        * )
    */
    
    public function resendOtpVerification(Request $request)
    {
        // Check if the user has already been verified
        $user = $request->user();
    
        if ($user->hasVerifiedEmail()) {
            return $this->error(null, 'Email already verified', 422);
        }
        
        $user->notify(new VerifyEmailNotification($user));

        return $this->success(null, 'Email verification sent', 200);
            
    }
        
}
