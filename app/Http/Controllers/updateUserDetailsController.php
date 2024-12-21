<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\HttpResponses;
use App\Models\User;
use App\Models\Channel;
use App\Models\Institution;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class updateUserDetailsController extends Controller
{
    use HttpResponses;

    /**
     * @OA\Post(
     * path="/api/updatePrimaryInstitution",
     * operationId="updateInstiution",
     * tags={"Institution"},
     * summary="Primary Institution update",
     * description="updates institution",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"primary_institution_id", "user_id"},
     *               @OA\Property(property="primary_institution_id", type="text"),
     *               @OA\Property(property="user_id", type="text")
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Updated",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Updated",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Primary Institution cannot be updated",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request", @OA\JsonContent()),
     *      @OA\Response(response=401, description="Request Unauthorized", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function updatePrimaryInstitution(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'primary_institution_id' => 'required|exists:institutions,id',
                'user_id' => 'required|exists:users,id',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->messages()], 400);
            }
    
            $user = User::find($request->user_id);
    
            if (!$this->isUserAuthorized($user)) {
                throw new \Exception('Unauthorized', 401);
            }
    
            if (!is_null($user->primary_institution_id)) {
                throw new \Exception('Primary Institution cannot be updated', 422);
            }
    
            $primaryChannelOfInstitution = $this->getPrimaryChannel($request->primary_institution_id);
    
            $channelSubscribed = $primaryChannelOfInstitution
                ? json_encode([$primaryChannelOfInstitution->id])
                : null;
            DB::beginTransaction();
    
            $user->update([
                'primary_institution_id' => $request->primary_institution_id,
                'channels_subscribed' => $channelSubscribed,
            ]);

            $primaryChannelOfInstitution->increment('subscribers');
            $primaryChannelOfInstitution->save();
            DB::commit();
    
            $firebase = new FirebaseController();
            if (!$firebase->subscribeToTopic($primaryChannelOfInstitution->topic_name, $user->device_token)) {
                return $this->error([], 'Something went wrong!', 400);
            }
    
            $user->refresh();
            $subscribedChannelIds = json_decode($user->channels_subscribed, true);
    
            return $this->success([
                'channels_subscribed' => $subscribedChannelIds ?? [],
            ], 'Primary Institution updated', 201);
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return $this->error([], $th->getMessage(), 422);
        }
    }

    private function isUserAuthorized($user)
    {
        return $user->id == Auth::guard('sanctum')->id();
    }

    private function getPrimaryChannel($institutionId)
    {
        return Channel::where([
            ['institution_id', $institutionId],
            ['is_primary', true],
        ])->first();
    }
}
