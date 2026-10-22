<?php

// Timber 2 comes from the Bedrock root vendor/, not from a plugin.
require_once __DIR__ . '/../../../../../vendor/autoload.php';

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

require $_tests_dir . '/includes/bootstrap.php';
