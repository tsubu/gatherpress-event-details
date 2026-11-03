<?php
/**
 * PHPUnit bootstrap for GatherPress Event Details.
 *
 * Runs inside wp-env's tests environment, which sets `WP_TESTS_DIR`. Falls
 * back to the `wp-phpunit/wp-phpunit` package for other setups.
 *
 * @package GatherPress_Event_Details
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Bootstrap scope.

$tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $tests_dir ) {
	$tests_dir = getenv( 'WP_PHPUNIT__DIR' );
}

if ( ! $tests_dir || ! file_exists( $tests_dir . '/includes/functions.php' ) ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI-only message.
	fwrite( STDERR, "WordPress test library not found. Run the tests with `npm run test:unit:php`.\n" );
	exit( 1 );
}

require_once dirname( __DIR__, 3 ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
require_once $tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require_once WP_PLUGIN_DIR . '/gatherpress/gatherpress.php';
		require_once dirname( __DIR__, 3 ) . '/gatherpress-event-details.php';
	}
);

require $tests_dir . '/includes/bootstrap.php';
