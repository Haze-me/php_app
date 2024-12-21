<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TopicController extends Controller
{
    private function generateRandomString($length = 5) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
    
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
    
        return $randomString;
    }
    
    public function generateRandomTopic() {
        $randomText1 = $this->generateRandomString();
        $randomText2 = $this->generateRandomString();
    
        return "{$randomText1}-{$randomText2}";
    }
    
}
