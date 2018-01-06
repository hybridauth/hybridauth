<?php
/**
 * Build a configuration array to pass to `Hybridauth\Hybridauth`
 */

$config = [
  'callback' => 'http://hybridauth.docksal/examples/example_06/callback.php',
  'providers' => [
    'Twitter' => [
      'enabled' => true,
      'keys' => [
        'key' => '...',
        'secret' => '...',
      ],
    ],
    'LinkedIn' => [
      'enabled' => true,
      'keys' => [
        'id' => '...',
        'secret' => '...',
      ],
    ],
    'Facebook' => [
      'enabled' => true,
      'keys' => [
        'id' => '...',
        'secret' => '...',
      ],
    ],
  ],
];
