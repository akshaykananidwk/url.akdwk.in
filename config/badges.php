<?php

/*
|--------------------------------------------------------------------------
| Gamification badges
|--------------------------------------------------------------------------
|
| Each badge is keyed by its stable badge_key (stored in user_badges).
| BadgeService evaluates every definition against a user's current stats
| and awards those whose threshold is met.
|
| metric:
|   links   -> total number of links the user owns
|   clicks  -> total clicks across all of the user's links
|   streak  -> current daily-activity streak (streak_days)
|   member  -> membership-based; threshold is account age in days,
|              except the special 'pro' key (any paid plan).
|
*/

return [

    'first_link' => [
        'name' => 'First steps',
        'description' => 'Create your first short link.',
        'icon' => 'sparkles',
        'metric' => 'links',
        'threshold' => 1,
    ],

    'links_10' => [
        'name' => 'Link builder',
        'description' => 'Create 10 short links.',
        'icon' => 'link',
        'metric' => 'links',
        'threshold' => 10,
    ],

    'links_100' => [
        'name' => 'Link machine',
        'description' => 'Create 100 short links.',
        'icon' => 'target',
        'metric' => 'links',
        'threshold' => 100,
    ],

    'clicks_1k' => [
        'name' => 'Getting traction',
        'description' => 'Reach 1,000 total clicks.',
        'icon' => 'chart',
        'metric' => 'clicks',
        'threshold' => 1000,
    ],

    'clicks_10k' => [
        'name' => 'Traffic magnet',
        'description' => 'Reach 10,000 total clicks.',
        'icon' => 'chart',
        'metric' => 'clicks',
        'threshold' => 10000,
    ],

    'clicks_100k' => [
        'name' => 'Viral',
        'description' => 'Reach 100,000 total clicks.',
        'icon' => 'bolt',
        'metric' => 'clicks',
        'threshold' => 100000,
    ],

    'streak_7' => [
        'name' => 'On a roll',
        'description' => 'Stay active 7 days in a row.',
        'icon' => 'bolt',
        'metric' => 'streak',
        'threshold' => 7,
    ],

    'streak_30' => [
        'name' => 'Unstoppable',
        'description' => 'Stay active 30 days in a row.',
        'icon' => 'target',
        'metric' => 'streak',
        'threshold' => 30,
    ],

    'veteran' => [
        'name' => 'Veteran',
        'description' => 'Be a member for a full year.',
        'icon' => 'clock',
        'metric' => 'member',
        'threshold' => 365,
    ],

    'pro' => [
        'name' => 'Pro member',
        'description' => 'Upgrade to a paid plan.',
        'icon' => 'shield',
        'metric' => 'member',
        'threshold' => 0,
    ],

];
