<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\HttpResponses;
use App\Models\User;

class checkUsernameController extends Controller
{
    use HttpResponses;
    /**
        * @OA\Post(
        * path="/api/usercheck",
        * operationId="username",
        * tags={"Verify Username"},
        * summary="Username Auth",
        * description="Check Username Here",
        *     @OA\RequestBody(
        *         @OA\JsonContent(),
        *         @OA\MediaType(
        *            mediaType="multipart/form-data",
        *            @OA\Schema(
        *               type="object",
        *               required={"username"},
        *               @OA\Property(property="username", type="text")
        *            ),
        *        ),
        *    ),
        *      @OA\Response(
        *          response=201,
        *          description="Username exist",
        *          @OA\JsonContent()
        *       ),
        *      @OA\Response(
        *          response=200,
        *          description="Username exist",
        *          @OA\JsonContent()
        *       ),
        *      @OA\Response(
        *          response=422,
        *          description="Unprocessable Entity",
        *          @OA\JsonContent()
        *       ),
        *      @OA\Response(response=400, description="Bad request"),
        *      @OA\Response(response=401, description="username exist not", @OA\JsonContent()),
        *      @OA\Response(response=404, description="Resource Not Found"),
        * )
    */
    public function userCheck(Request $request)
    {
    $username = User::where('username', $request->username)->first();
    if(! $username){
      return $this->success([], 'username does not exist', 200);
    }
    return $this->error([],'username exist', 409);
    } 
}
