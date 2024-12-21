<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Http\Controllers\Controller;
use App\Services\PostService;
use App\Http\Resources\PostsResource;
use App\Models\Channel;
use App\Models\SubChannel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PostsController extends Controller
{
    use HttpResponses;

    /**
     * @OA\Get(
     * path="/api/post/posts",
     * operationId="getAllPosts",
     * tags={"Post"},
     * summary="Get all posts",
     * description="responds with a set of post data",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"type"},
     *               @OA\Property(property="lastPostId", type="integer"),
     *               @OA\Property(property="type", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="posts",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="posts",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Unauthorized", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function getAllPosts(Request $request, PostService $postService)
    {
        $lastPostId = $request->input('lastPostId', 20);
        $type = $request->input('type', 'others');
        $user = $request->user();

        $response = $postService->retrievePostsWhereIdLessThanPostId($lastPostId, $user, $type);

        return response()->json($response);
    }

    /**
     * @OA\Get(
     * path="/api/post/posts/paginate",
     * operationId="getPostsByPagination",
     * tags={"Post"},
     * summary="Get all posts by pagination format",
     * description="receives two properties pageIndex and type, responds with a set of post data based on the type and pageIndex",
     *     @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(
     *             property="pageIndex",
     *             type="integer"
     *         ),
     *         @OA\Property(
     *             property="type",
     *             type="string"
     *         )
     *       )
     *     ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="{'data': {'pageIndex': int, 'totalPosts': int, 'totalPages': int, 'lastPage': boolean, 'posts': [{ 'viewed': boolean},{}]}}",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=204,
     *          description="{'data': [], 'message': 'No posts'}",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Unauthorized", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function getPostsByPagination(Request $request, PostService $postService)
    {
        if ($request->user()->id !== Auth::guard('sanctum')->id()) {
            return $this->error([], 'Unauthorized', 401);
        }

        $user = $request->user();
        $pageIndex = $request->input('pageIndex', 1);
        $type = $request->input('type', 'others');

        $response = $postService->getPaginatedPostsWithMetadata($pageIndex, $type, $user);

        return response()->json($response);
    }

    /**
     * @OA\Post(
     *   path="/api/post/create",
     *   operationId="createPost",
     *   tags={"Post"},
     *   summary="Create a new post",
     *   @OA\RequestBody(
     *     @OA\JsonContent(),
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         required={"channel_id", "poster_id", "viewType"},
     *         @OA\Property(property="channel_id", type="integer"),
     *         @OA\Property(property="sub_channel_id", type="integer"),
     *         @OA\Property(property="poster_id", type="integer"),
     *         @OA\Property(property="post_title", type="string"),
     *         @OA\Property(property="post_body", type="string"),
     *         @OA\Property(property="post_images[]", type="array", @OA\Items(type="string", format="binary")),
     *         @OA\Property(property="viewType", type="string"),
     *       ),
     *     ),
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Post created successfully",
     *     @OA\JsonContent()
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Error creating post",
     *     @OA\JsonContent()
     *   ),
     * )
     */

    public function createPost(Request $request) //create a post
    {
        try {
            $validatedData = $request->validate([
                'channel_id' => 'nullable|integer|exists:channels,id',
                'sub_channel_id' => 'nullable|integer|exists:sub_channels,id',
                'poster_id' => 'required|exists:users,id',
                'post_title' => 'max:100',
                'post_body' => 'max:2000',
                'post_images.*' => 'nullable|image',
                'viewType' => 'required'
            ]);

            if ($validatedData['poster_id'] != Auth::guard('sanctum')->id()) {
                return $this->error([], 'Forbidden', Response::HTTP_FORBIDDEN);

            }
            if (empty($validatedData['post_title']) && empty($validatedData['post_body']) && empty($validatedData['post_images'])) {
                return $this->error([], 'Must include at least 1 Post attribute', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $postImages = $this->handlePostImages($request);

            $validatedData['post_images'] = json_encode($postImages);
            $validatedData['uuid'] = Str::uuid();

            $newPost = Post::create($validatedData);
            if (!$newPost) {
                return $this->error([], 'Error publishing post', Response::HTTP_BAD_REQUEST);
            }

            $postCollection = new PostsResource($newPost);
            // send the new post notifications
            $this->sendNewPostNotifications($validatedData);

            return $this->success($postCollection, 'Post published successfully', Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            Log::error("Error creating new post: " . $th); // Log the error for debugging
            return $this->error([], $th->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    private function handlePostImages(Request $request): array
    {
        $postImages = [];

        if ($request->hasFile('post_images')) {
            foreach ($request->file('post_images') as $image) {
                if ($image->isValid()) {
                    $newPostImageName = 'https://images.silfrica.com/post_imgs/' . time() . '-' . trim($image->getClientOriginalName());
                    $imagePath = config('app.image_path') . '/post_imgs/';
                    $image->move(public_path($imagePath), $newPostImageName);
                    $postImages[] = $newPostImageName;
                } else {
                    return $this->error([], 'Invalid file upload', Response::HTTP_BAD_REQUEST);
                }
            }
        }

        return $postImages;
    }

    private function sendNewPostNotifications(array $validatedData)
    {
        $group = $this->handleNotificationTopics($validatedData);
        $topicName = null;

        // send push notification with firebase
        $firebase = new FirebaseController();

        if ($validatedData['sub_channel_id'] == null) {
            $topicName = $group['channel']->topic_name;

            $firebase->togglePushNotificationChannel($topicName, $validatedData['post_title'], $validatedData['post_body']);
        } elseif ($validatedData['channel_id'] == null) {
            $topicName = $group['sub_channel']->topic_name;

            $firebase->togglePushNotificationChannel($topicName, $validatedData['post_title'], $validatedData['post_body']);
            
        } else {
            $topicNames = $group['both_models'];
            foreach ($topicNames as $topicName) {
                if ($topicName) {
                    $topicName = $topicName->topic_name;
                    $firebase->togglePushNotificationChannel($topicName, $validatedData['post_title'], $validatedData['post_body']);
                }
            }
        }
    }

    private function handleNotificationTopics(array $model): array
    {
        $channel = $model['channel_id']
            ? Channel::where('id', $model['channel_id'])
                ->first()
            : [];
        $sub_channel = $model['sub_channel_id']
            ? SubChannel::where('id', $model['sub_channel_id'])
                ->first()
            : [];

        $topics = [
            'channel' => $channel,
            'sub_channel' => $sub_channel,
            'both' => [$channel, $sub_channel]
        ];

        return $topics;
    }

    /**
     * @OA\Post(
     *   path="/api/post/save",
     *   operationId="savePost",
     *   tags={"Post"},
     *   summary="Saves a post",
     *   @OA\RequestBody(
     *     @OA\JsonContent(),
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         required={"user_id", "post_id"},
     *         @OA\Property(property="user_id", type="integer"),
     *         @OA\Property(property="post_id", type="integer"),
     *       ),
     *     ),
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Post saved successfully",
     *     @OA\JsonContent()
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Error saving post",
     *     @OA\JsonContent()
     *   ),
     * )
     */

    public function savePost(Request $request) //to save post for user, not storage-wise. Accepts user_id and post_id
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'post_id' => 'required|exists:posts,id',
        ]);
        try {
            $user = User::findOrFail($validatedData['user_id']);
            $saved_posts = json_decode($user->saved_posts, true) ?: [];
            $already_saved = in_array($validatedData['post_id'], $saved_posts);
            if ($already_saved) {
                return $this->success([], 'Post already saved!');
            }
            $user->saved_posts = json_encode(array_unique(array_merge($saved_posts, [intval($validatedData['post_id'])])));
            $user->save();

            $message = 'Post saved!';
            return $this->success([], $message);
        } catch (\Throwable $th) {
            return $this->error([], 'Error saving post: ' . $th->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/post/get/{user_id}/savedPosts",
     * operationId="retrieveSavedPost",
     * tags={"Post"},
     * summary="Get saved posts",
     * description="responds with a set of saved post data",
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
     *          description="saved posts",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="saved posts",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Unauthorized", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function retrieveSavedPost($user_id) //retrieve saved post of the user
    {
        try {
            $user = User::findOrFail($user_id);
            $saved_posts = json_decode($user->saved_posts, true) ?: [];
            if (empty($saved_posts)) {
                return $this->success([], 'No Saved Post!');
            }
            $getSavedPosts = Post::whereIn('id', $saved_posts)->get();
            $savedPostsCollection = PostsResource::collection($getSavedPosts);
            return $this->success($savedPostsCollection, 'Your saved posts!');
        } catch (\Throwable $th) {
            //throw $th;
            return $this->error([], 'Unable to get saved posts: ' . $th->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/post/channel/{channel_id}/posts",
     * operationId="getChannelPosts",
     * tags={"Post"},
     * summary="Get channel posts",
     * description="responds with a set of channel posts",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"channel_id"},
     *               @OA\Property(property="channel_id", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="channel posts",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="channel posts",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Unauthorized", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function getChannelPosts($channel_id)
    {
        $checkChannelPostExists = Post::where('channel_id', $channel_id)->get();
        if ($checkChannelPostExists->isEmpty()) {
            // 'No posts for this channel!'
            return response()->json(['data' => []]);
        }
        $channelPostDetails = PostsResource::collection($checkChannelPostExists);
        // 'Posts for this channel!'
        return $channelPostDetails;
    }

    /**
     * @OA\Get(
     * path="/api/post/subchannel/{sub_channel_id}/posts",
     * operationId="getSubchannelPosts",
     * tags={"Post"},
     * summary="Get subchannel posts",
     * description="responds with a set of subchannel posts",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"channel_id"},
     *               @OA\Property(property="channel_id", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="subchannel posts",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="subchannel posts",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Unauthorized", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function getSubchannelPosts($sub_channel_id)
    {
        $checkSubchannelPostExists = Post::where('sub_channel_id', $sub_channel_id)->get();
        if ($checkSubchannelPostExists->isEmpty()) {
            // 'No posts for this subchannel!'
            return response()->json(['data' => []]);
        }
        $subchannelPostDetails = PostsResource::collection($checkSubchannelPostExists);
        // 'Posts for this subchannel!'
        return $subchannelPostDetails;
    }

    /**
     * @OA\Post(
     *   path="/api/post/count-view",
     *   operationId="incrementPostCountView",
     *   tags={"Post"},
     *   summary="Increment post view count",
     *   @OA\RequestBody(
     *     @OA\JsonContent(),
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         required={"user_id", "post_id"},
     *         @OA\Property(property="user_id", type="integer"),
     *         @OA\Property(property="post_id[]", type="array", @OA\Items(type="integer", format="int64")),
     *       ),
     *     ),
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Post count view incremented successfully!",
     *     @OA\JsonContent()
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Bad request",
     *     @OA\JsonContent()
     *   ),
     * )
     */

    public function incrementPostCountView(Request $request, PostService $postService)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'post_id' => 'required|array',
        ]);

        try {
            $user = User::findOrFail($validatedData['user_id']);
            $postIds = $validatedData['post_id'];

            $postService->incrementPostViewCount($user, $postIds);

            return $this->success([], 'Post count view incremented successfully!');
        } catch (\Throwable $th) {
            Log::error("Error incrementing post view count: " . $th); // Log the error for debugging
            return $this->error([], 'Post count view increment failed!', 400);
        }
    }

    public function stressPostRequest()
    {
        $stress_data = [
            "name" => "I am stressing this endpoint!",
            "status" => "Stressed!",
            "message" => "small data"
        ];

        return $this->success($stress_data, 'Stressed!');
    }

    /**
     * @OA\Get(
     * path="/api/post/{post_id}",
     * operationId="getOneSinglePost",
     * tags={"Post"},
     * summary="Get a post",
     * description="responds with a single post data",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"post_id"},
     *               @OA\Property(property="post_id", type="integer"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="post",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Unauthorized", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

     public function getOneSinglePost(int $post_id, PostService $postService)
     {
         try {
            $response = $postService->retrieveOneSinglePost($post_id);
            return $this->success($response, "Retrieved Post $post_id");
         } catch (\Throwable $th) {
            Log::error("Error getting a post: " . $th);
            return $this->error([], $th->getMessage(), 400);
         }
     }

}
