<?php

namespace App\Policies;

use App\Models\User;

class SpamPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    // List of spammy keywords to check
    //Avoid spam words that make exaggerated claims and promises
    protected $exaggeratedClaimsKeywords = [
        '#1',
        '100% more',
        '100% free',
        '100% satisfied',
        'Additional income',
        'Be your own boss',
        'Best price',
        'Big bucks',
        'Billion',
        'Cash bonus',
        'Cents on the dollar',
        'Consolidate debt',
        'Double your cash',
        'Double your income',
        'Earn extra cash',
        'Earn money',
        'Eliminate bad credit',
        'Extra cash',
        'Extra income',
        'Expect to earn',
        'Fast cash',
        'Financial freedom',
        'Free access',
        'Free consultation',
        'Free gift',
        'Free hosting',
        'Free info',
        'Free investment',
        'Free membership',
        'Free money',
        'Free preview',
        'Free quote',
        'Free trial',
        'Full refund',
        'Get out of debt',
        'Get paid',
        'Giveaway',
        'Guaranteed',
        'Increase sales',
        'Increase traffic',
        'Incredible deal',
        'Lower rates',
        'Lowest price',
        'Make money',
        'Million dollars',
        'Miracle',
        'Money back',
        'Once in a lifetime',
        'One time',
        'Pennies a day',
        'Potential earnings',
        'Prize',
        'Promise',
        'Pure profit',
        'Risk-free',
        'Satisfaction guaranteed',
        'Save big money',
        'Save up to',
        'Special promotion',
    ];

    public function isCleanContent($message)
    {
        // Convert the message to lowercase for case-insensitive matching
        $message = strtolower($message);

        // Check if any spammy keywords are present in the message
        foreach ($this->exaggeratedClaimsKeywords as $keyword) {
            if (strpos($message, strtolower($keyword)) !== false) {
                return false; // Message contains a spammy keyword
            }
        }

        return true; // Message is clean
    }
}
