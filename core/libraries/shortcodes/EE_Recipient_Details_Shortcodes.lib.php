<?php

if (!defined('EVENT_ESPRESSO_VERSION') )
	exit('NO direct script access allowed');

/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package			Event Espresso
 * @ author				Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license			http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link				http://www.eventespresso.com
 * @ version		 	4.0
 *
 * ------------------------------------------------------------------------
 *
 * EE_Recipient_Details_Shortcodes
 * 
 * this is a child class for the EE_Shortcodes library.  The EE_Recipient_Details_Shortcodes lists all shortcodes related to recipient specific info.  Meaning, that when this is parsed, we're parsing for WHO is receiving the message.  This only parses for Registrants and Primary Registrants as recipients.
 *
 * NOTE: if a method doesn't have any phpdoc commenting the details can be found in the comments in EE_Shortcodes parent class.
 * 
 * @package		Event Espresso
 * @subpackage	libraries/shortcodes/EE_Recipient_Details_Shortcodes.lib.php
 * @author		Darren Ethier
 *
 * ------------------------------------------------------------------------
 */
class EE_Recipient_Details_Shortcodes extends EE_Shortcodes {

	public function __construct() {
		parent::__construct();
	}


	protected function _init_props() {
		$this->label = __('Recipient Details Shortcodes', 'event_espresso');
		$this->description = __('All shortcodes specific to registrant and primary registrant recipients data', 'event_espresso');
		$this->_shortcodes = array(
			//'[PRIMARY_REGISTRATION_ID]' => __('This will be replaced with the Registration ID for the primary attendee of an event', 'event_espresso'),
			//'[ATTENDEE_REGISTRATION_ID]' => __('this will be replaced with the attendee registration id for the Attendee of an event', 'event_espresso')
			);
	}



	protected function _parser( $shortcode ) {
		return '';
	}

	
} // end EE_Registration_Shortcodes class