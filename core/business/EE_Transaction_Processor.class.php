<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }
EE_Registry::instance()->load_class( 'Processor_Base' );

/**
 * Class EE_Transaction_Processor
 *
 * This class contains business logic pertaining specifically to the interaction of EE_Transaction and EE_Registration model objects
 * Provides methods for manipulating and processing changes to an EE_Transaction and it's related EE_Registrations with regards to the checkout/registration process
 *
 * @package 			Event Espresso
 * @subpackage 	core
 * @author 				Brent Christensen
 * @since 				4.6.0
 *
 */

class EE_Transaction_Processor extends EE_Processor_Base {

	/**
	 * 	@var EE_Registration_Processor $_instance
	 * 	@access 	private
	 */
	private static $_instance = NULL;

	/**
	 * 	@var array $registration_query_params - array of query WHERE params to use when retrieving cached registrations from a transaction
	 * 	@access 	private
	 */
	private $_registration_query_params = array();



	/**
	 *@singleton method used to instantiate class object
	 *@access public
	 * @param array $registration_query_params
	 *@return EE_Transaction_Processor instance
	 */
	public static function instance( $registration_query_params = array() ) {
		// check if class object is instantiated
		if ( ! self::$_instance instanceof EE_Transaction_Processor ) {
			self::$_instance = new self( $registration_query_params );
		}
		return self::$_instance;
	}



	/**
	 * @param array $registration_query_params
	 * @return EE_Transaction_Processor
	 */
	private function __construct( $registration_query_params = array() ) {
		// make sure some query params are set for retrieving registrations
		$this->_set_registration_query_params( $registration_query_params );
	}



	/**
	 * @access private
	 * @param array $registration_query_params
	 */
	private function _set_registration_query_params( $registration_query_params ) {
		$this->_registration_query_params = ! empty( $registration_query_params ) ? $registration_query_params : array( 'order_by' => array( 'REG_count' => 'ASC' ));
	}



	/**
	 * _reg_steps_completed
	 *
	 * if $check_all is TRUE, then returns TRUE if ALL reg steps have been marked as completed,
	 * if a $reg_step_slug is provided, then this step will be skipped when testing for completion
	 *
	 * if $check_all is FALSE and a $reg_step_slug is provided, then ONLY that reg step will be tested for completion
	 *
	 * @access private
	 * @param EE_Transaction $transaction
	 * @param string $reg_step_slug
	 * @param bool   $check_all
	 * @return boolean
	 */
	private function _reg_steps_completed( EE_Transaction $transaction, $reg_step_slug = '', $check_all = TRUE ) {
		$reg_steps = $transaction->reg_steps();
		if ( ! is_array( $reg_steps ) || empty( $reg_steps )) {
			return FALSE;
		}
		// loop thru reg steps array)
		foreach ( $reg_steps as $slug => $reg_step_completed ) {
			// if NOT checking ALL steps (only checking one step)
			if ( ! $check_all ) {
				// and this is the one
				if ( $slug == $reg_step_slug ) {
					return $reg_step_completed;
				} else {
					// skip to next reg step in loop
					continue;
				}
			}
			// if any reg step is NOT completed (ignoring any specific steps), then just leave
			if( ! $reg_step_completed && $slug != $reg_step_slug ) {
				return FALSE;
			}
		}
		return TRUE;
	}



	/**
	 * all_reg_steps_completed
	 *
	 * returns TRUE if ALL reg steps have been marked as completed
	 *
	 * @param EE_Transaction $transaction
	 * @return boolean
	 */
	public function all_reg_steps_completed( EE_Transaction $transaction ) {
		return $this->_reg_steps_completed( $transaction );
	}



	/**
	 * all_reg_steps_completed_except
	 *
	 * returns TRUE if ALL reg steps, except a particular step that you wish to skip over, have been marked as completed
	 *
	 * @param EE_Transaction $transaction
	 * @param string $exception
	 * @return boolean
	 */
	public function all_reg_steps_completed_except( EE_Transaction $transaction, $exception = '' ) {
		return $this->_reg_steps_completed( $transaction, $exception );
	}



	/**
	 * all_reg_steps_completed_except
	 *
	 * returns TRUE if ALL reg steps, except the final step, have been marked as completed
	 *
	 * @param EE_Transaction $transaction
	 * @return boolean
	 */
	public function all_reg_steps_completed_except_final_step( EE_Transaction $transaction ) {
		return $this->_reg_steps_completed( $transaction, 'finalize_registration' );
	}



	/**
	 * reg_step_completed
	 *
	 * returns TRUE if a specific reg step has been marked as completed
	 *
	 * @param EE_Transaction $transaction
	 * @param string $reg_step_slug
	 * @return boolean
	 */
	public function reg_step_completed( EE_Transaction $transaction, $reg_step_slug ) {
		return $this->_reg_steps_completed( $transaction, $reg_step_slug, FALSE );
	}



	/**
	 * completed_final_reg_step
	 *
	 * returns TRUE if the finalize_registration reg step has been marked as completed
	 *
	 * @param EE_Transaction $transaction
	 * @return boolean
	 */
	public function final_reg_step_completed( EE_Transaction $transaction ) {
		return $this->_reg_steps_completed( $transaction, 'finalize_registration', FALSE );
	}



	/**
	 * set_reg_step_initiated
	 * given a valid TXN_reg_step, this sets it's value to a unix timestamp
	 *
	 * @access public
	 * @param \EE_Transaction $transaction
	 * @param string          $reg_step_slug
	 * @return boolean
	 */
	public function set_reg_step_initiated( EE_Transaction $transaction, $reg_step_slug ) {
		return $this->_set_reg_step_completed_status( $transaction, $reg_step_slug, current_time( 'timestamp' ));
	}



	/**
	 * set_reg_step_completed
	 * given a valid TXN_reg_step, this sets the step as completed
	 *
	 * @access public
	 * @param \EE_Transaction $transaction
	 * @param string          $reg_step_slug
	 * @return boolean
	 */
	public function set_reg_step_completed( EE_Transaction $transaction, $reg_step_slug ) {
		return $this->_set_reg_step_completed_status( $transaction, $reg_step_slug, TRUE );
	}



	/**
	 * set_reg_step_completed
	 * given a valid TXN_reg_step slug, this sets the step as NOT completed
	 *
	 * @access public
	 * @param \EE_Transaction $transaction
	 * @param string          $reg_step_slug
	 * @return boolean
	 */
	public function set_reg_step_not_completed( EE_Transaction $transaction, $reg_step_slug ) {
		return $this->_set_reg_step_completed_status( $transaction, $reg_step_slug, FALSE );
	}



	/**
	 * set_reg_step_completed
	 * given a valid reg step slug, this sets the TXN_reg_step completed status which is either:
	 *
	 *
	 * @access private
	 * @param \EE_Transaction $transaction
	 * @param string          $reg_step_slug
	 * @param boolean | int $status
	 * @return boolean
	 */
	private function _set_reg_step_completed_status( EE_Transaction $transaction, $reg_step_slug, $status ) {
		// validate status
		$status = is_bool( $status ) || is_numeric( $status ) ? $status : FALSE;
		// get reg steps array
		$txn_reg_steps = $transaction->reg_steps();
		// if reg step does NOT exist
		if ( ! isset( $txn_reg_steps[ $reg_step_slug ] )) {
			return FALSE;
		}
		// if  we're trying to complete a step that is already completed
		if ( $txn_reg_steps[ $reg_step_slug ] === TRUE ) {
			return FALSE;
		}
		// if  we're trying to complete a step that hasn't even started
		if ( $status === TRUE && $txn_reg_steps[ $reg_step_slug ] === FALSE ) {
			return FALSE;
		}
		// if current status value matches the incoming value (no change)
		if ( $txn_reg_steps[ $reg_step_slug ] === $status ) {
			// this will happen in cases where multiple AJAX requests occur during the same step
			return FALSE;
		}
		// if we're trying to set a start time
		if ( is_numeric( $status )) {
			// for an already completed step
			if ( $txn_reg_steps[ $reg_step_slug ] === TRUE ) {
				return FALSE;
			}
			// but if the step has already been initialized...
			if ( is_numeric( $txn_reg_steps[ $reg_step_slug ] )) {
				// skip the update below, but don't return FALSE so that errors won't be displayed
				return TRUE;
			}
		}
		// update completed status
		$txn_reg_steps[ $reg_step_slug ] = $status;
		$transaction->set_reg_steps( $txn_reg_steps );
		return TRUE;
	}



	/**
	 * 	toggle_failed_transaction_status
	 * upgrades a TXNs status from failed to abandoned,
	 * meaning that contact information has been captured for at least one registrant
	 *
	 * 	@access public
	 * @param EE_Transaction $transaction
	 * 	@return 	boolean
	 */
	public function toggle_failed_transaction_status( EE_Transaction $transaction ) {
		// if TXN status is still set as "failed"...
		if ( $transaction->status_ID() == EEM_Transaction::failed_status_code ) {
			$transaction->set_status( EEM_Transaction::abandoned_status_code );
			return TRUE;
		}
		return FALSE;
	}



	/**
	 * toggle_abandoned_transaction_status
	 * upgrades a TXNs status from failed or abandoned to incomplete

	 * 	@access public
	 * @param EE_Transaction $transaction
	 * 	@return 	boolean
	 */
	public function toggle_abandoned_transaction_status( EE_Transaction $transaction ) {
		// if TXN status has not been updated already due to a payment, and is still set as "failed" or "abandoned"...
		if ( $transaction->status_ID() == EEM_Transaction::failed_status_code || $transaction->status_ID() == EEM_Transaction::abandoned_status_code ) {
			$transaction->set_status( EEM_Transaction::incomplete_status_code );
			return TRUE;
		}
		return FALSE;
	}



	/**
	 * 	manually_update_registration_statuses
	 *
	 * 	@access public
	 * @param EE_Transaction $transaction
	 * @param string  	$new_reg_status
	 * @param array 	$registration_query_params - array of query WHERE params to use when retrieving cached registrations from a transaction
	 * 	@return 	boolean
	 */
	public function manually_update_registration_statuses( EE_Transaction $transaction, $new_reg_status = '', $registration_query_params = array() ) {
		$status_updates = $this->_call_method_on_registrations_via_Registration_Processor( 'manually_update_registration_status', $transaction, $registration_query_params, $new_reg_status );
		do_action( 'AHEE__EE_Transaction_Processor__manually_update_registration_statuses', $transaction, $status_updates );
		return $status_updates;
	}



	/**
	 * 	toggle_registration_statuses_for_default_approved_events
	 *
	 * 	@access public
	 * @param EE_Transaction $transaction
	 * @param array 	$registration_query_params - array of query WHERE params to use when retrieving cached registrations from a transaction
	 * 	@return 	boolean
	 */
	public function toggle_registration_statuses_for_default_approved_events( EE_Transaction $transaction, $registration_query_params = array() ) {
		$status_updates = $this->_call_method_on_registrations_via_Registration_Processor( 'toggle_registration_status_for_default_approved_events', $transaction, $registration_query_params );
		do_action( 'AHEE__EE_Transaction_Processor__toggle_registration_statuses_for_default_approved_events', $transaction, $status_updates );
		return $status_updates;
	}



	/**
	 * 	toggle_registration_statuses_if_no_monies_owing
	 *
	 * 	@access public
	 * @param EE_Transaction $transaction
	 * @param array 	$registration_query_params - array of query WHERE params to use when retrieving cached registrations from a transaction
	 * 	@return 	boolean
	 */
	public function toggle_registration_statuses_if_no_monies_owing( EE_Transaction $transaction, $registration_query_params = array() ) {
		$status_updates = $this->_call_method_on_registrations_via_Registration_Processor( 'toggle_registration_status_if_no_monies_owing', $transaction, $registration_query_params );
		do_action( 'AHEE__EE_Transaction_Processor__toggle_registration_statuses_if_no_monies_owing', $transaction, $status_updates );
		return $status_updates;
	}



	/**
	 * update_transaction_and_registrations_after_checkout_or_payment
	 * cycles thru related registrations and calls update_registration_after_checkout_or_payment() on each
	 *
	 * @param EE_Transaction $transaction
	 * @param \EE_Payment | NULL    $payment
	 * @param array          $registration_query_params - array of query WHERE params to use when retrieving cached registrations from a transaction
	 * @throws \EE_Error
	 * @return array
	 */
	public function update_transaction_and_registrations_after_checkout_or_payment( EE_Transaction $transaction, $payment = NULL, $registration_query_params = array() ) {
		// get final reg step status
		$finalized = $this->final_reg_step_completed( $transaction );
		// array of details to aid in decision making by systems
		$update_params = array(
			'old_txn_status' 			=> $transaction->status_ID(),
			'reg_steps' 					=> $transaction->reg_steps(),
			'finalized' 					=> $finalized,
			'revisit' 						=> $this->_revisit,
			'payment_updates' 	=> $payment instanceof EE_Payment ? TRUE : FALSE,
			'last_payment'			=> $payment
		);
//		update_option(
//			'ee_cron_finalize_abandoned_transactions',
//			$update_params
//		);
		// now update the registrations and add the results to our $update_params
		$update_params['status_updates'] = $this->_call_method_on_registrations_via_Registration_Processor(
			'update_registration_after_checkout_or_payment',
			$transaction,
			$registration_query_params,
			$update_params
		);
//		update_option(
//			'ee_cron_finalize_abandoned_transactions',
//			serialize(get_option( 'ee_cron_finalize_abandoned_transactions'	))
//			. $update_params['status_updates']
//		);

		do_action( 'AHEE__EE_Transaction_Processor__update_transaction_and_registrations_after_checkout_or_payment', $transaction, $update_params );
		// if the 'finalize_registration' step has been initiated (has a timestamp) but has not yet been fully completed (TRUE)
		if ( is_numeric( $finalized ) && $finalized !== TRUE ) {
			$this->set_reg_step_completed( $transaction, 'finalize_registration' );
		}
//		update_option(
//			'ee_cron_finalize_abandoned_transactions',
//			serialize(get_option( 'ee_cron_finalize_abandoned_transactions'	))
//			. '$transaction->ID() = ' . $transaction->ID()
//			. ' && $transaction->reg_steps(
//			update_transaction_and_registrations_after_checkout_or_payment ) = '
//			. serialize( $transaction->reg_steps() )
//		);
		$transaction->save();
		return $update_params;
	}



	/**
	 * _call_method_on_registrations_via_Registration_Processor
	 * cycles thru related registrations and calls the requested method on each
	 *
	 * @access private
	 * @param string 	$method_name
	 * @param EE_Transaction $transaction
	 * @param array 	$registration_query_params - array of query WHERE params to use when retrieving cached registrations from a transaction
	 * @param string 	$additional_param
	 * @throws \EE_Error
	 * @return boolean
	 */
	private function _call_method_on_registrations_via_Registration_Processor( $method_name,  EE_Transaction $transaction, $registration_query_params = array(), $additional_param = NULL ) {
		$response = FALSE;
		/** @type EE_Registration_Processor $registration_processor */
		$registration_processor = EE_Registry::instance()->load_class( 'Registration_Processor' );
		// check that method exists
		if ( ! method_exists( $registration_processor, $method_name )) {
//			update_option(
//				'ee_cron_finalize_abandoned_transactions',
//				'! method_exists EE_Registration_Processor::' . $method_name . '()'
//			);
			throw new EE_Error( __( 'Method does not exist.', 'event_espresso' ));
		}
		// make sure some query params are set for retrieving registrations
		$this->_set_registration_query_params( $registration_query_params );
		// loop through cached registrations
		foreach ( $transaction->registrations( $this->_registration_query_params ) as $registration ) {
			if ( $registration instanceof EE_Registration ) {
				if ( $additional_param ) {
//					update_option(
//						'ee_cron_finalize_abandoned_transactions',
//						serialize(get_option( 'ee_cron_finalize_abandoned_transactions'	))
//						. serialize( array( __LINE__, $registration->ID(),
//											   $additional_param ))
//					);
					$response = $registration_processor->$method_name( $registration, $additional_param ) ? TRUE : $response;
				} else {
//					update_option(
//						'ee_cron_finalize_abandoned_transactions',
//						serialize(get_option( 'ee_cron_finalize_abandoned_transactions'	)) . serialize( array( __LINE__, $registration->ID(),
//											  $additional_param ) )
//					);
					$response = $registration_processor->$method_name( $registration ) ? TRUE : $response;
				}
//				update_option(
//					'ee_cron_finalize_abandoned_transactions',
//					serialize(get_option( 'ee_cron_finalize_abandoned_transactions'	)) . '$registration->ID() = ' . $registration->ID() . '&&
//					$response = ' . $response
//				);
			}
		}
		return $response;
	}


}



// End of file EE_Transaction_Processor.class.php
// Location: /EE_Transaction_Processor.class.php