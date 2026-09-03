<?php

$composer_autoload = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! file_exists( $composer_autoload ) ) {
	fwrite( STDERR, "Composer dependencies are missing. Run `composer install` before running PHPUnit.\n" );
	exit( 1 );
}

require_once $composer_autoload;

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

$wp_tests_functions = $_tests_dir . '/includes/functions.php';
if ( ! file_exists( $wp_tests_functions ) ) {
	fwrite( STDERR, "The WordPress test library was not found. Start wp-env before running PHPUnit.\n" );
	exit( 1 );
}

require_once $wp_tests_functions;

function frontend_uploader_manually_load_plugin() {
	require_once ABSPATH . '/wp-admin/includes/class-wp-list-table.php';
	require dirname( __DIR__ ) . '/frontend-uploader.php';
}
tests_add_filter( 'muplugins_loaded', 'frontend_uploader_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

require_once __DIR__ . '/includes/class-frontend-uploader-test-case.php';
