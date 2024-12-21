<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Repositories\PostRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;

class PostService
{
    protected $postRepository;
    private $postsDetails;

    public function __construct(PostRepository $postRepository)
    {
        $this->postRepository = $postRepository;
    }

    public function incrementPostViewCount(User $user, array $postIds): void
    {
        if (!$user instanceof User) {
            throw new InvalidArgumentException('Invalid user object');
        }

        if (!is_array($postIds)) {
            throw new InvalidArgumentException('Post IDs must be an array');
        }

        $postIds = array_unique($postIds);

        $cachedData = Cache::get('post_view_data', []);

        // Update cached data if needed (optimized based on cache key)
        $updatedCacheData = $this->updateCachedData($postIds, $cachedData, $user);

        try {
            // Persist updated data to DB (consider transactions)
            $this->postRepository->updatePostViewData($updatedCacheData);
            Cache::put('post_view_data', $updatedCacheData, 60); // Update cache
        } catch (\Exception $e) {
            Log::error("Error saving posts: " . $e->getMessage());
            throw $e;
        }
    }

    private function updateCachedData(array $postIds, array $cachedData, User $user): array
    {
        $updatedData = [];
        $userViewed = $user->id;

        foreach ($postIds as $id) {
            if (!isset($cachedData[$id])) {
                $cachedData[$id] = ['viewCount' => 0, 'usersViewed' => []];
            }

            $post = &$cachedData[$id]; // Use reference for performance optimization

            if (!in_array($userViewed, $post['usersViewed'])) {
                $post['viewCount']++;
                $post['usersViewed'][] = $userViewed;
            } else {
                // Handle user already viewed case (return flag or throw later)
                throw new \Exception('User already viewed', 200);
            }

            $updatedData[$id] = $post; // Update main data structure
        }

        return $updatedData;
    }

    public function mergePostsMetaData(Collection $posts, int $user_id): array
    {
        $this->postsDetails = [];

        foreach ($posts as $post) {
            $isPostViewedByUser = !empty($post->users_viewed) ? in_array($user_id, json_decode($post->users_viewed, true)) : false;

            $postResource = $this->postRepository->makePost($post);
            $postArray = $postResource->resolve();

            $merged = collect($postArray)->merge(['viewed' => $isPostViewedByUser]);

            $this->postsDetails[] = $merged->all();
        }

        return $this->postsDetails;
    }

    public function getPaginatedPostsWithMetadata(int $pageIndex, string $postType, User $user): array
    {
        try {
            $totalPosts = $this->postRepository::totalPosts();
            $postsPerPage = config('app.posts_per_page', 20);
            $totalPages = ceil($totalPosts / $postsPerPage);

            $pageIndex = $this->validatePageIndex($pageIndex, $totalPages);

            $offset = ($pageIndex - 1) * $postsPerPage;

            if ($postType !== 'subscribed') {
                $posts = $this->postRepository->getPaginatedNotSubscribedPosts($offset, $postsPerPage, $user);
            } else {
                $posts = $this->postRepository->getPaginatedSubscribedPosts($offset, $postsPerPage, $user);
            }

            $postsDetails = $this->mergePostsMetaData($posts, $user->id);
            $lastPage = ($pageIndex == $totalPages);

            return $this->formatResponse($pageIndex, $totalPosts, $totalPages, $lastPage, $postsDetails);
        } catch (\Throwable $th) {
            Log::error("Error retrieving paginated posts: " . $th->getMessage());
            return $this->formatResponse($pageIndex, 0, 0, 1, []);
        }
    }

    private function validatePageIndex(int $pageIndex, int $totalPages): int
    {
        if ($pageIndex < 1) {
            return 1;
        }

        if ($pageIndex > $totalPages) {
            return $totalPages;
        }

        return $pageIndex;
    }

    private function formatResponse(int $pageIndex, int $totalPosts, int $totalPages, bool $lastPage, array $postsDetails): array
    {
        return [
            'data' => [
                'pageIndex' => $pageIndex,
                'totalPosts' => $totalPosts,
                'totalPages' => $totalPages,
                'lastPage' => $lastPage,
                'posts' => $postsDetails,
            ],
        ];
    }

    public function retrievePostsWhereIdLessThanPostId(int|string $lastPostId, User $user, string $type): array
    {
        if ($type === 'subscribed') {
            $posts = $this->postRepository->fetchWithWhereInColumn($user, $lastPostId);
        } else {
            $posts = $this->postRepository->fetchWithWhereNotInColumn($user, $lastPostId);
        }

        $this->postsDetails = $this->mergePostsMetaData($posts, $user->id);

        // format the response
        $response = [
            'data' => [
                'posts' => $this->postsDetails,
            ],
        ];

        return $response;
    }

    public function retrieveAllPosts(): Collection
    {
        return $this->postRepository->getAllPosts();
    }

    public function retrieveOneSinglePost(int $id)
    {
      return $this->postRepository->getOnePostResource($id);
    }
}
