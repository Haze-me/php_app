<?php

namespace App\Repositories;

use App\Http\Resources\PostsResource;
use App\Models\Post;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PostRepository
{
  private $userRepository;

  public function __construct(UserRepository $userRepository)
  {
    $this->userRepository = $userRepository;
  }

  /**
   * Returns a query builder for the posts table.
   */
  private function buildBaseQuery()
  {
    return Post::query();
  }

  protected function queryPost()
  {
    return $this->buildBaseQuery();
  }

  /**
   * Builds posts with default builder query.
   */
  private function applyDefaultFilters(Builder $query)
  {
    return $query->latest()->limit(10);
  }

  public function getOnePost(int $id): Post
  {
   return Post::findOrFail($id);
  }

  public function getOnePostResource(int $id): PostsResource
  {
   $post = $this->getOnePost($id);
   return $this->makePost($post);
  }

  public function getPostsByIds(array $ids): Collection
  {
    return Post::whereIn('id', $ids)->get();
  }

  public function updatePostViewData(array $cachedPostViewData): void
  {
    foreach ($cachedPostViewData as $id => $data) {

      $post = Post::find($id);

      if ($post) {
        $post->count_view = $data['viewCount'];
        $post->users_viewed = json_encode(array_unique($data['usersViewed']));
        $post->save();
      }
    }
  }

  public static function totalPosts(): int
  {
    return Post::count();
  }

  public function getPaginatedSubscribedPosts(int $offset, int $postsPerPage, User $user): Collection
  {
      $userChannelsSubscribed = $this->userRepository->getUserChannelsSubscribed($user);
      $userSubChannelsSubscribed = $this->userRepository->getUserSubChannelsSubscribed($user);

      $subscribedPosts = Post::whereIn('channel_id', $userChannelsSubscribed)
          ->orWhereIn('sub_channel_id', $userSubChannelsSubscribed)
          ->latest()
          ->skip($offset)
          ->take($postsPerPage)
          ->get();

      return $subscribedPosts;
  }

  public function getPaginatedNotSubscribedPosts(int $offset, int $postsPerPage, User $user): Collection
  {
    $userChannelsSubscribed = $this->userRepository->getUserChannelsSubscribed($user);
    $userSubChannelsSubscribed = $this->userRepository->getUserSubChannelsSubscribed($user);

    $nonSubscribedPosts = Post::whereNotIn('channel_id', $userChannelsSubscribed)
          ->orWhereNotIn('sub_channel_id', $userSubChannelsSubscribed)
          ->latest()
          ->skip($offset)
          ->take($postsPerPage)
          ->get();

      return $nonSubscribedPosts;
  }

  /**
   * Makes a new PostsResource object.
   * This will transform the Post model into a PostsResource object instance for a modifiable and consumable response.
   */
  public function makePost($post): PostsResource
  {
    return PostsResource::make($post);
  }

  public function fetchWithWhereInColumn(User $user, int|string $lastPostId)
  {
    $userChannelsSubscribed = $this->userRepository->getUserChannelsSubscribed($user);
    $userSubChannelsSubscribed = $this->userRepository->getUserSubChannelsSubscribed($user);
    return $this->applyDefaultFilters($this->getPostsWhereIdGreaterThan($lastPostId)->whereIn('channel_id', $userChannelsSubscribed)
      ->orWhereIn('sub_channel_id', $userSubChannelsSubscribed))->get();
  }

  public function fetchWithWhereNotInColumn(User $user, int|string $lastPostId)
  {
    $userChannelsSubscribed = $this->userRepository->getUserChannelsSubscribed($user);
    $userSubChannelsSubscribed = $this->userRepository->getUserSubChannelsSubscribed($user);
    return $this->applyDefaultFilters($this->getPostsWhereIdGreaterThan($lastPostId)->whereNotIn('channel_id', $userChannelsSubscribed)
      ->orWhereNotIn('sub_channel_id', $userSubChannelsSubscribed))->get();
  }

  /**
   * Retrieves posts with IDs greater than a given value.
   *
   * @param int $id
   * @return Collection
   */
  public function getPostsWhereIdGreaterThan($id): Builder
  {
    return $this->buildBaseQuery()->where('id', '>', $id);
  }

  /**
   * Retrieves all posts with pagination and ordering.
   *
   * @return Collection
   */
  public function getAllPosts()
  {
    $query = $this->buildBaseQuery();
    return $this->applyDefaultFilters($query)->get();
  }
}
