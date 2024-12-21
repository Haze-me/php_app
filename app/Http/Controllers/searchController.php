<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Institution;
use App\Models\SubChannel;
use Illuminate\Http\Request;

class searchController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/search/institution",
     * operationId="searchInstitution",
     * tags={"Search"},
     * summary="Search Institution query",
     * description="Receiving Institution Query Data",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"query"},
     *               @OA\Property(property="query", type="text")
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Found Institution",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Found Institution",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Institution exist not", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function searchInstitution(Request $request)
    {
        $query = trim($request->input('query'));

        // Access the authenticated user
        $user = auth('api')->user();

        if (empty($query)) {
            return response()->json([]);
        }

        $institutes = Institution::where('name', 'LIKE', "%$query%")
            ->limit(10)
            ->select('id', 'name')
            ->get();
        if ($institutes->isNotEmpty()) {
            return response()->json($institutes);
        } else {
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json([]);
            } else {
                return response('', 204);
            }
        }
    }

    /**
     * @OA\Get(
     * path="/api/search/channel",
     * operationId="searchChannel",
     * tags={"Search"},
     * summary="Searches Institution channel",
     * description="Receiving Institution Channel Data",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"queryChannel"},
     *               @OA\Property(property="queryChannel", type="text")
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Found Channel",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Found Channel",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Unauthorized access", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function searchChannel(Request $request)
    {
        $query = trim($request->input('queryChannel'));

        // Access the authenticated user
        $user = auth('api')->user();

        if (empty($query)) {
            return response()->json([]);
        }

        if (!$user) {
            return $this->error(null, 'Unauthorized', 403);
        }

        $channels = Channel::where('name', 'LIKE', "%$query%")
            ->limit(10)
            ->get();

        $subchannels = SubChannel::where('name', 'LIKE', "%$query%")
            ->limit(10)
            ->get();

        if ($channels->isEmpty() && $subchannels->isEmpty()) {
            return response()->json([]);
        }

        $response = [];

        if (!$channels->isEmpty()) {
            $channelItems = $channels->map(function ($channel) {
                $channel['sub_admins'] = json_decode($channel['sub_admins'], true);
                return $channel;
            });

            $response['channels'] = $channelItems;
        }

        if (!$subchannels->isEmpty()) {
            $response['subchannels'] = $subchannels;
        }

        return response()->json($response);

    }

}
