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
        'key' => '5nWax6AsxN5RWe52Q0JtZcC3o',
        'secret' => 'CDeAaw0vAO0sZdlVIGi88AlJeQKMYsXSzUulebQGVhmCUXWWqJ',
      ],
    ],
    'LinkedIn' => [
      'enabled' => true,
      'keys' => [
        'id' => '77j97cjw8hsxd8',
        'secret' => 'zl8X0W68VB67lLRj',
      ],
    ],
    'Facebook' => [
      'enabled' => true,
      'keys' => [
        'id' => '670386846478453',
        'secret' => '4095fc18aa7f98d312df509863f4c521',
      ],
    ],
  ],
];
