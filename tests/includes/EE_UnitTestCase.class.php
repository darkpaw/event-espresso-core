<?php
/**
 * EE's extension of WP_UnitTestCase for writing all EE_Tests
 *
 * @since 		4.3.0
 * @package 		Event Espresso
 * @subpackage 	tests
 */

require_once EE_TESTS_DIR . 'includes/factory.php';


/**
 * This is used to override any existing WP_UnitTestCase methods that need specific handling in EE.  We
 * can also add additional methods in here for EE tests (that are used frequently)
 *
 * @since 		4.3.0
 * @package 		Event Espresso
 * @subpackage 	tests
 */
class EE_UnitTestCase extends WP_UnitTestCase {
	protected $_cached_SERVER_NAME = NULL;

	public function setUp() {
		parent::setUp();

		// Fake WP mail globals, to avoid errors
		add_filter( 'wp_mail', array( $this, 'setUp_wp_mail' ) );
		add_filter( 'wp_mail_from', array( $this, 'tearDown_wp_mail' ) );

		//factor
		$this->factory = new EE_UnitTest_Factory;

	}


	/**
	 *  Use this to clean up any global scope singletons etc that we may have being used by EE so
	 *  that they are fresh between tests.
	 *
	 * @todo this of course means we need an easy way to reset our singletons...
	 * @see parent::cleanup_global_scope();
	 */
	function clean_up_global_scope() {
		parent::clean_up_global_scope();
	}


	/**
	 * Set up globals necessary to avoid errors when using wp_mail()
	 */
	public function setUp_wp_mail( $args ) {
		if ( isset( $_SERVER['SERVER_NAME'] ) ) {
			$this->_cached_SERVER_NAME = $_SERVER['SERVER_NAME'];
		}

		$_SERVER['SERVER_NAME'] = 'example.com';

		// passthrough
		return $args;
	}



	/**
	 * Tear down globals set up in setUp_wp_mail()
	 */
	public function tearDown_wp_mail( $args ) {
		if ( ! empty( $this->_cached_SERVER_NAME ) ) {
			$_SERVER['SERVER_NAME'] = $this->_cached_SERVER_NAME;
			unset( $this->_cached_SERVER_NAME );
		} else {
			unset( $_SERVER['SERVER_NAME'] );
		}

		// passthrough
		return $args;
	}

}