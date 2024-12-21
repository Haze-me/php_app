<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Nette\Utils\Random;
use Tests\TestCase;

class RandomPasswordGeneratorTest extends TestCase
{
    /**
     * Test the random password generator.
     */
    public function testRandomPasswordGenerator(): void
    {
        $lowerCaseChars = range('a', 'z');
        $upperCaseChars = range('A', 'Z');
        $digitChars = range('0', '9');
        $symbolChars = '!@#$%^&*()_+-=[]{};:,.<>?';

        $password = '';

        while (strlen($password) < 8) {
            $password .= $lowerCaseChars[rand(0, count($lowerCaseChars) - 1)];
            $password .= $upperCaseChars[rand(0, count($upperCaseChars) - 1)];
            $password .= $digitChars[random_int(0, count($digitChars) - 1)];
            $password .= $symbolChars[rand(0, strlen($symbolChars) - 1)];
        }

        // Add your assertions to validate the generated password
        $this->assertTrue(strlen($password) >= 8);
        $this->assertMatchesRegularExpression('/[a-z]/', $password);
        $this->assertMatchesRegularExpression('/[A-Z]/', $password);
        $this->assertMatchesRegularExpression('/[0-9]/', $password);
        // You can add more assertions as needed.
    }
}
