<?php
/**
 * Tests for plugin bootstrap compatibility.
 *
 * @package GatherPress_Event_Details
 */

namespace GatherPress_Event_Details\Tests;

use WP_UnitTestCase;

/**
 * Class Test_Plugin.
 *
 * @coversNothing
 */
final class Test_Plugin extends WP_UnitTestCase {

	/**
	 * Enforces the documented minimum GatherPress version.
	 *
	 * @return void
	 */
	public function test_gatherpress_version_compatibility(): void {
		$this->assertFalse( \gatherpress_event_details_is_gatherpress_compatible( '0.36.0' ) );
		$this->assertTrue( \gatherpress_event_details_is_gatherpress_compatible( '0.36.1' ) );
		$this->assertTrue( \gatherpress_event_details_is_gatherpress_compatible( '0.37.0-alpha' ) );
		$this->assertTrue( \gatherpress_event_details_is_gatherpress_compatible() );
	}
}
