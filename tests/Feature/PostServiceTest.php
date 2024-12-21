<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\PostService;
use App\Models\User;
use App\Models\Post;

class PostServiceTest extends TestCase
{
    public function testIncrementPostViewCount(PostService $postService) {
        // Arrange
        $user = new User(['id' => 1]);
        $post1 = new Post(['id' => 2]); 
        $post2 = new Post(['id' => 3]);
        
        $postIds = [2, 3];
        
        // Act
        $postService->incrementPostViewCount($user, $postIds);
        
        // Assert
        $this->assertEquals(1, $post1->count_view); 
        $this->assertEquals(1, $post2->count_view);
        
        $expected1 = [1];
        $this->assertEquals($expected1, json_decode($post1->users_viewed));
        
        $expected2 = [1];
        $this->assertEquals($expected2, json_decode($post2->users_viewed));
      }
    
      public function testIncrementForExistingViewers(PostService $postService) {
        // Arrange
        
        $user1 = new User(['id' => 1]);
        $user2 = new User(['id' => 2]);  
        
        $post1 = new Post([
          'id' => 4,
          'users_viewed' => json_encode([1]) 
        ]);
        
        $postIds = [4];
        
        // Act
        $postService->incrementPostViewCount($user2, $postIds);
        
        // Assert
        $this->assertEquals(1, $post1->count_view);
    
        $expected = [1, 2];
        $this->assertEquals($expected, json_decode($post1->users_viewed));
      }
}
