<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Seth Shoultes
 * @ copyright	(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license			http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link				http://www.eventespresso.com
 * @ version		4.0
 *
 * ------------------------------------------------------------------------
 *
 * Ticket Selector  class
 *
 * @package		Event Espresso
 * @subpackage	includes/classes/EE_Ticket_Selector.class.php
 * @author			Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
class EED_Ticket_Selector extends  EED_Module {

	/**
	* event that ticket selector is being generated for
	*
	* @access protected
	* @var array
	*/
	protected static $_event = NULL;



	/**
	* whether the registration is being added by an admin
	*
	* @access protected
	* @var boolean
	*/
	protected $_added_by_admin = NULL;





	/**
	 * 	set_hooks - for hooking into EE Core, other modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks() {
		// routing
		EE_Config::register_route( 'process_ticket_selections', 'EED_Ticket_Selector', 'process_ticket_selections' );
		add_action( 'wp_loaded', array( 'EED_Ticket_Selector', 'set_definitions' ), 2 );
//		add_action( 'AHEE_events_list_footer', array( 'EED_Ticket_Selector', 'display_ticket_selector' ), 10, 1 );
		add_action( 'AHEE_event_details_before_post', array( 'EED_Ticket_Selector', 'ticket_selector_form_open' ), 10, 1 );
		add_action( 'AHEE_event_details_header_bottom', array( 'EED_Ticket_Selector', 'display_ticket_selector' ), 10, 1 );
		add_action( 'AHEE_event_details_header_bottom', array( 'EED_Ticket_Selector', 'display_ticket_selector_submit' ), 11, 1 );
		add_action( 'AHEE_event_details_after_post', array( 'EED_Ticket_Selector', 'ticket_selector_form_close' ), 10 );
		add_action( 'wp_enqueue_scripts', array( 'EED_Ticket_Selector', 'load_tckt_slctr_assets' ), 10 );		
	}



	/**
	 * 	set_hooks_admin - for hooking into EE Admin Core, other modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks_admin() {
	}



	/**
	 * 	set_definitions
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_definitions() {
		define( 'TICKET_SELECTOR_ASSETS_URL', plugin_dir_url( __FILE__ ) . 'assets' . DS );
		define( 'TICKET_SELECTOR_TEMPLATES_PATH', str_replace( '\\', DS, plugin_dir_path( __FILE__ )) . 'templates' . DS );
	}


	/**
	* 	gets the ball rolling
	*
	*	@access 	public
	* 	@param	object 			$WP  
	* 	@return 	void	
	*/
	public function run( $event ) {
		
	}





	/**
	* 	creates buttons for selecting number of attendees for an event
	*
	*	@access public
	* 	@param	object 		$event  
	* 	@param	boolean 		$added_by_admin  whether the registration is being added by an admin
	* 	@return 	string	
	*/
	public static function display_ticket_selector( $event = NULL, $added_by_admin = FALSE ) {
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');		

//		d( $event );
		if ( $event instanceof EE_Event ) {
			self::$_event = $event;
		} else if ( $event instanceof WP_Post && isset( $event->EE_Event ) && $event->EE_Event instanceof EE_Event ) {
			self::$_event = $event->EE_Event;
		} else if ( $event instanceof WP_Post && ( ! isset( $event->EE_Event ) || ! $event->EE_Event instanceof EE_Event )) {
			$event->EE_Event = EEM_Event::instance()->instantiate_class_from_post_object( $event );
			self::$_event = $event->EE_Event;
		} else {
			$user_msg = __( 'No Event object or an invalid Event object was supplied.', 'event_espresso' );
			$dev_msg = $user_msg . __( 'In order to generate a ticket selector, please ensure you are passing either an EE_Event object or a WP_Post object of the post type "espresso_event" to the EE_Ticket_Selector class constructor.', 'event_espresso' );
			EE_Error::add_error( $user_msg . '||' . $dev_msg, __FILE__, __FUNCTION__, __LINE__ );	
			return FALSE;
		}
		
		$template_args = array();
		
		$template_args['EVT_ID'] = self::$_event->ID();
		$template_args['event'] = self::$_event;

		if ( self::$_event->allow_multiple() ) {
			// make sure additional_limit is set and that it is at least 1
			$additional_limit = self::$_event->additional_limit() < 1 ? 1 : self::$_event->additional_limit();
			// filter the maximum qty that can appear in the Ticket Selector qty dropdowns
			$max_tickets = apply_filters( 'FHEE__EE_Ticket_Selector__display_ticket_selector__max_tickets', 16 );
			// let's make the max amount of attendees somebody can select a little more reasonable
			$template_args['max_atndz'] = $additional_limit > $max_tickets ? $max_tickets : $additional_limit;	
		} else {
			$template_args['max_atndz'] = 1;
		}
		
		// get all tickets for this event ordered by the datetime
		$template_args['tickets'] = EEM_Ticket::instance()->get_all( array(
			array( 'Datetime.EVT_ID' => self::$_event->ID() ),
			'order_by' => array( 'Datetime.DTT_EVT_start' => 'DESC', 'TKT_start_date' => 'ASC' )
		));
	
		$templates['ticket_selector'] =  TICKET_SELECTOR_TEMPLATES_PATH . 'ticket_selector_chart.template.php';
		$templates['ticket_selector'] =  apply_filters( 'FHEE__EE_Ticket_Selector__display_ticket_selector__template_path', $templates['ticket_selector'], self::$_event );

		EEH_Template::display_template($templates['ticket_selector'], $template_args);

	}




	
	/**
	* 	ticket_selector_form_open
	* 
	*	@access public
	* 	@access 		public
	* 	@return		string
	*/	
	public static function ticket_selector_form_open( $post ) {
		$checkout_url = add_query_arg( array( 'ee' => 'process_ticket_selections' ), get_permalink( $post->ID ));
		if ( ! $checkout_url ) {
			$msg = __('The URL for the Event Details page could not be retreived.', 'event_espresso' );
			EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
		}
		echo '
		<form id="" method="POST" action="' . $checkout_url . '">';
		wp_nonce_field( 'process_ticket_selections', 'process_ticket_selections_nonce' );
	}




	
	/**
	* 	display_ticket_selector_submit
	* 
	*	@access public
	* 	@access 		public
	* 	@return		string
	*/	
	public static function display_ticket_selector_submit() {
		if ( apply_filters( 'FHEE__EE_Ticket_Selector__display_ticket_selector_submit', FALSE )) {
			echo '
			<input id="" class="ee-register-button-lnk" type="submit" value="' . __('Register Now', 'event_espresso' ) . '" />
			';
		}
	}



	
	/**
	* 	ticket_selector_form_close
	* 
	*	@access public
	* 	@access 		public
	* 	@return		string
	*/	
	public static function ticket_selector_form_close() {
		echo '
		</form>';
	}





	
	/**
	* 	process_ticket_selections
	* 
	*	@access public
	* 	@access 		public
	* 	@return		array  or FALSE
	*/	
	public function process_ticket_selections() {
		// check nonce
		if ( ! EE_Registry::instance()->REQ->is_set( 'process_ticket_selections_nonce' ) || ! wp_verify_nonce( EE_Registry::instance()->REQ->get( 'process_ticket_selections_nonce' ), 'process_ticket_selections' )) {
			$error_msg = __( 'We\'re sorry but your request failed to pass a security check.<br/>Please click the back button on your browser and try again.', 'event_espresso' );
			EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );
			return;
		}	
//		d( EE_Registry::instance()->REQ );
		
		$return = FALSE;
		//we should really only have 1 registration in the works now (ie, no MER)
		//so clear any previosu items in the cart. When MER happens this will probably need to be tweaked, 
		//possibly wrappe din a conditional checking for some constant defined in MER etc.
		EE_Registry::instance()->load_core( 'Session' );
		//EE_Registry::instance()->SSN->clear_session();
		//d( EE_Registry::instance()->SSN );
		
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');
		// do we have an event id?
		if ( EE_Registry::instance()->REQ->is_set( 'tkt-slctr-event-id' )) {
		
			// validate/sanitize data
			$valid = self::_validate_post_data('add_event_to_cart');
			// d( $valid );
		
			//check total tickets oredered vs max number of attendees that can register
			if ( $valid['total_tickets'] > $valid['max_atndz'] ) {
		
				// ordering too many tickets !!!
				$singular = 'You have attempted to purchase %s ticket.';
				$plural = 'You have attempted to purchase %s tickets.';
				$limit_error_1 = sprintf( _n( $singular, $plural, $valid['total_tickets'], 'event_espresso' ), $valid['total_tickets'], $valid['total_tickets'] );
		
				$singular = 'The registration limit for this event is %s ticket per registration, therefore the total number of tickets you may purchase at a time can not exceed %s.';
				$plural = 'The registration limit for this event is %s tickets per registration, therefore the total number of tickets you may purchase at a time can not exceed %s.';
				$limit_error_2 = sprintf( _n( $singular, $plural, $valid['max_atndz'], 'event_espresso' ), $valid['max_atndz'], $valid['max_atndz'] );
				$error_msg = $limit_error_1 . '<br/>' . $limit_error_2;
				EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );
			} else {
				
				// all data appears to be valid
				$tckts_slctd = FALSE;
				$success = FALSE;
				// load cart
				EE_Registry::instance()->load_core( 'Cart' );

				// cycle thru the number of data rows sent from the event listsing
				for ( $x = 0; $x < $valid['rows']; $x++ ) {		
					// does this row actually contain a ticket quantity?
					if ( isset( $valid['qty'][$x] ) && $valid['qty'][$x] > 0 ) {		
						// YES we have a ticket quantity				
						$tckts_slctd = TRUE;
//						d( $valid['ticket_obj'][$x] );
						if ( $valid['ticket_obj'][$x] instanceof EE_Ticket ) {
							// then add ticket to cart
							if ( self::_add_ticket_to_cart( $valid['ticket_obj'][$x], $valid['qty'][$x] )) {
								$success = TRUE;
							}							
						} else {
							// nothing added to cart
							$error_msg = __( 'A valid ticket could not be retreived for the event.<br/>Please click the back button on your browser and try again.', 'event_espresso' );
							EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );
						}						
					} 
				}
				
//				d( EE_Registry::instance()->CART );

				if ( $tckts_slctd ) {
					if ( $success ) {
						if ( $return ) {
							return TRUE;
						} else {
							EE_Registry::instance()->CART->save_cart();
							EE_Registry::instance()->SSN->update();
							wp_safe_redirect( add_query_arg( array( 'ee'=>'register' ), get_permalink( $this->EE->CFG->core->reg_page_id )));
							exit();
						}
					} else {
						// nothing added to cart
						$error_msg = __( 'No tickets were added for the event.<br/>Please click the back button on your browser and try again.', 'event_espresso' );
						EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );
					}

				} else {
					// no ticket quantities were selected
					$error_msg = __( 'You need to select a ticket quantity before you can proceed.', 'event_espresso' );
					EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );
				}				
			}
//die();
			if ( EE_Registry::instance()->REQ->is_set( 'tkt-slctr-return-url-'.$valid['id'] )) {
				EE_Error::get_notices( FALSE, TRUE );
				wp_safe_redirect( EE_Registry::instance()->REQ->get( 'tkt-slctr-return-url-'.$valid['id'] ) );
				exit();
			} elseif ( isset( $event_to_add['id'] )) {
				EE_Error::get_notices( FALSE, TRUE );
				wp_safe_redirect( get_permalink( $event_to_add['id'] ));
				exit(); 
			} else {
				echo EE_Error::get_notices();			
			}
			
			
		} else {
			// $_POST['tkt-slctr-event-id'] was not set ?!?!?!?
			$error_msg = __( 'An event id was not provided or was not received.<br/>Please click the back button on your browser and try again.', 'event_espresso' );
			EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );			
		}	

//		if ( isset( $_SERVER['HTTP_REFERER'] )) {
//			$return_url = add_query_arg(  EE_Error::get_notices( FALSE, TRUE ), $_SERVER['HTTP_REFERER'] );
//			wp_safe_redirect( $return_url );
//			exit();
//		}	

	}






	/**
	* 	validate_post_data
	* 
	* 	@access 		private
	*  	@param 		string 		$stage - where we are in the registration process
	* 	@return		array  or FALSE
	*/
	private static function _validate_post_data() {
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');
		
		// start with an empty array()
		$valid_data = array();

		//if event id is valid
		if ( $id = absint( EE_Registry::instance()->REQ->get( 'tkt-slctr-event-id' ))) {

			$valid_data['id'] = $id;
			// grab and sanatize return-url
			$return_url = esc_url_raw( EE_Registry::instance()->REQ->get( 'tkt-slctr-return-url-' . $id ));
			// array of other form names
			$inputs_to_clean = array(
				'event' => 'tkt-slctr-event-',
				'max_atndz' => 'tkt-slctr-max-atndz-',
				'rows' => 'tkt-slctr-rows-',
				'qty' => 'tkt-slctr-qty-',
				'ticket_id' => 'tkt-slctr-ticket-id-',
				'ticket_obj' => 'tkt-slctr-ticket-obj-',
			);
			// let's track the total number of tickets ordered.'
			$valid_data['total_tickets'] = 0;
			// cycle through $inputs_to_clean array
			foreach ($inputs_to_clean as $what => $input_to_clean) {

				if ( EE_Registry::instance()->REQ->is_set( $input_to_clean . $id )) {
					// grab value
					$input_value = EE_Registry::instance()->REQ->get( $input_to_clean . $id );							
					switch ($what) {

						// integers
						case 'rows':
						case 'max_atndz':
							$valid_data[$what] = absint( $input_value );
							break;

						// arrays of integers
						case 'qty':
							$row_qty = $input_value;
							// if qty is coming from a radio button input, then we need to assemble an array of rows
							if( ! is_array( $row_qty )) {
								// get number of rows
								$rows = EE_Registry::instance()->REQ->is_set( 'tkt-slctr-rows-' . $id ) ? absint( EE_Registry::instance()->REQ->get( 'tkt-slctr-rows-' . $id )) : 1;
								//d( $rows );
								// explode ints by the dash
								$row_qty = explode( '-', $row_qty );
								$row = isset( $row_qty[0] ) ? ( absint( $row_qty[0] )) : 1;
								$qty = isset( $row_qty[1] ) ? absint( $row_qty[1] ) : 0;
								$row_qty = array( $row => $qty );
								 //d( $row_qty );
								for( $x = 1; $x <= $rows; $x++ ) {
									if ( ! isset( $row_qty[$x] )) {
										$row_qty[$x] = 0;
									}
								}
							}
							ksort( $row_qty );
							// d( $row_qty );
							// cycle thru values
							foreach ( $row_qty as $qty ) {
								$qty = absint( $qty );
								// sanitize as integers
								$valid_data[$what][] = $qty;
								$valid_data['total_tickets'] += $qty;
							}
							break;

						// array of serialized and encoded objects
						case 'ticket_id':
							$value_array = array();
							// cycle thru values
							foreach ( $input_value as $key=>$value ) {
								// allow only numbers, letters,  spaces, commas and dashes
								$value_array[$key] = wp_strip_all_tags($value);
							}
							$valid_data[$what] = $value_array;
							break;
							
						case 'event':
							// grab the array
							// allow only numbers, letters,  spaces, commas and dashes
							$valid_data[$what] = unserialize( base64_decode( $input_value ));
							break;
							
						case 'ticket_obj':
							// ensure that $input_value is an array
							$input_value = is_array( $input_value ) ? $input_value : array( $input_value );
							// cycle thru values
							foreach ( $input_value as $key=>$value ) {
								// decode and unserialize the ticket object
								$ticket_obj = unserialize( base64_decode( $value ));
								// vat is dis? i ask for TICKET !!!
								if ( ! $ticket_obj instanceof EE_Ticket ) {
									// get ticket via the ticket id we put in the form
									$ticket_obj = EE_Registry::instance()->load_model( 'Ticket' )->get_one_by_ID( $valid['ticket_id'][$x] );
								}
								$valid_data[$what][] = $ticket_obj;
							}
							break;
							
						case 'return-url' :
							break;
							
					} 	// end switch $what					
				}
			} 	// end foreach $inputs_to_clean 
			
		} else {
			$error_msg = 'The event id provided was not valid';
			EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );
			return FALSE;
		}

		//d( $valid_data );
		return $valid_data;
	}





	/**
	* 	adds a ticket to the cart
	* 	@access private
	* 	@param string - which_cart
	* 	@param array - items
	* 	@return TRUE on success, FALSE on fail
	*/
	private static function _add_ticket_to_cart( EE_Ticket $ticket = NULL, $qty = 1 ) {
	
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');
		// get the number of spaces left for this event
		$available_spaces = self::get_available_spaces( $ticket );
		// compare availalbe spaces against the number of tickets being purchased
		if ( $available_spaces >= $qty ) {
			// add event to cart
			return EE_Registry::instance()->CART->add_ticket_to_cart( $ticket, $qty ) ? TRUE : FALSE;
		} else {
			// event is full
			if ( $available_spaces > 0 ) {
				// add error messaging - we're using the _n function that will generate the appropriate singular or plural message based on the number of $available_spaces
				$error_msg = sprintf(
					_n( 
						'We\'re sorry, but there is only %s available space left for this event. Please go back and select a different number of tickets.', 
						 'We\'re sorry, but there are only %s available spaces left for this event. Please go back and select a different number of tickets.', 
						$available_spaces, 
						'event_espresso'
					), 
					$available_spaces
				);
				EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );
			} else {
				$error_msg = __('We\'re sorry, but there are no available spaces left for this event', 'event_espresso');
				EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );
			}
			return FALSE;
		}
	}




	
	/**
	* 	get number of available spaces for event
	*
	*	@access 		public
	*	@param 		EE_Ticket 	$ticket
	* 	@return 		int
	*/
	public static function get_available_spaces( EE_Ticket $ticket ) {
		
		// one meeeeeeellion spaces
		$available_spaces = 1000000;
		// get all datetimes associated with this ticket, then loop thru them 
		if ( $datetimes = $ticket->get_many_related( 'Datetime' )) {
			foreach ( $datetimes as $datetime ) {
				// if a reg limit has been set for this datetime
				if ( $datetime->reg_limit() > 0 ) {
					// calculate the spaces left, then compare that with the previous results (or the initial), and take the lesser value
					$available_spaces = min( $datetime->reg_limit() - $datetime->sold(), $available_spaces );
				}
			}
		}
		// final result should be the lowest number of available spaces for any of the datetimes
		return $available_spaces;
	}





	/**
	* 	load js
	*
	*	@access 		public
	* 	@access 		public
	* 	@return 		void
	*/
	public static function load_tckt_slctr_assets() {
		// add some style
		wp_register_style('ticket_selector', TICKET_SELECTOR_ASSETS_URL . 'ticket_selector.css');
		wp_enqueue_style('ticket_selector');
		// make it dance
//		wp_register_script('ticket_selector', TICKET_SELECTOR_ASSETS_URL . 'ticket_selector.js', array('jquery'), '', TRUE);
//		wp_enqueue_script('ticket_selector');
		// loco grande 
		wp_localize_script( 'ticket_selector', 'eei18n', EE_Registry::$i18n_js_strings );
	}





}



// End of file EE_Ticket_Selector.class.php
// Location: /includes/classes/EE_Ticket_Selector.class.php