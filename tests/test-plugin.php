<?php
/**
 * Test Plugin Functionality
 *
 * @package Simply_Disable_Comments
 */

/**
 * Test plugin functions.
 */
class Plugin_Test extends WP_UnitTestCase {

	/**
	 * Test that comments are closed via filter
	 */
	public function test_comments_open_returns_false() {
		$post_id = $this->factory->post->create();
		$this->assertFalse( comments_open( $post_id ) );
	}

	/**
	 * Test that pings are closed via filter
	 */
	public function test_pings_open_returns_false() {
		$post_id = $this->factory->post->create();
		$this->assertFalse( pings_open( $post_id ) );
	}

	/**
	 * Test that comments array returns empty
	 */
	public function test_comments_array_returns_empty() {
		$post_id = $this->factory->post->create();
		$comments = get_comments( array( 'post_id' => $post_id ) );
		$this->assertEmpty( $comments );
	}

	/**
	 * Test that XML-RPC comment method is disabled
	 */
	public function test_xmlrpc_comment_method_disabled() {
		$methods = array(
			'wp.newComment' => 'test',
			'wp.getPost'    => 'test',
		);
		$filtered = \SimplyDisableComments\disable_xmlrpc( $methods );
		$this->assertArrayNotHasKey( 'wp.newComment', $filtered );
		$this->assertArrayHasKey( 'wp.getPost', $filtered );
	}

	/**
	 * Test that REST API comments endpoint is removed
	 */
	public function test_rest_api_comments_endpoint_removed() {
		$endpoints = array(
			'comments' => array(),
			'posts'    => array(),
		);
		$filtered = \SimplyDisableComments\remove_rest_api_endpoints( $endpoints );
		$this->assertArrayNotHasKey( 'comments', $filtered );
		$this->assertArrayHasKey( 'posts', $filtered );
	}

	/**
	 * Test that REST API comment insertion returns error
	 */
	public function test_disable_rest_api_comments_returns_error() {
		$prepared_comment = array();
		$request = new WP_REST_Request();
		$result = \SimplyDisableComments\disable_rest_api_comments( $prepared_comment, $request );
		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'rest_comments_disabled', $result->get_error_code() );
	}

	/**
	 * Test that post types don't support comments
	 */
	public function test_post_types_dont_support_comments() {
		// Manually call the function
		\SimplyDisableComments\remove_from_post_type_support();
		
		// Test that post type doesn't support comments
		$this->assertFalse( post_type_supports( 'post', 'comments' ) );
		$this->assertFalse( post_type_supports( 'post', 'trackbacks' ) );
	}

	/**
	 * Test plugin constants are defined
	 */
	public function test_plugin_constants_defined() {
		$this->assertTrue( defined( 'SIMPLY_DISABLE_COMMENTS_VERSION' ) );
		$this->assertTrue( defined( 'SIMPLY_DISABLE_COMMENTS_PLUGIN_FILE' ) );
		$this->assertTrue( defined( 'SIMPLY_DISABLE_COMMENTS_URL' ) );
		$this->assertTrue( defined( 'SIMPLY_DISABLE_COMMENTS_PATH' ) );
	}

	/**
	 * Test setup function exists
	 */
	public function test_setup_function_exists() {
		$this->assertTrue( function_exists( 'SimplyDisableComments\setup' ) );
	}
}
