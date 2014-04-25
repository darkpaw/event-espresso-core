<?php
/**
 * Text_Fields is a base class for any fields which are have float value. (Exception: foreign and private key fields. Wish PHP had multiple-inheritance for this...)
 */
class EE_Float_Field extends EE_Model_Field_Base{
	function get_wpdb_data_type(){
		return '%f';
	}
	/**
	 * If provided a string, strips out number-related formatting, like commas, periods, spaces, other junk, etc. 
	 * However, treats commas and periods as thousand-seperators ro decimal marks, as indicate by the config's currency.
	 * Returns a float
	 * @param type $value_inputted_for_field_on_model_object
	 * @return float
	 */
	function prepare_for_set($value_inputted_for_field_on_model_object) {
//		echo __LINE__."$value_inputted_for_field_on_model_object<br>";
		//remove whitespaces and thousands seperators
		if(is_string($value_inputted_for_field_on_model_object)){
			$value_inputted_for_field_on_model_object = str_replace(array(" ",EE_Config::instance()->currency->thsnds),"",$value_inputted_for_field_on_model_object);
//echo __LINE__."$value_inputted_for_field_on_model_object<br>";			
//normalize it so periods are decimal marks (we don't care where you're from: we're talking PHP now)
			$value_inputted_for_field_on_model_object = str_replace( EE_Config::instance()->currency->dec_mrk, ".", $value_inputted_for_field_on_model_object) ;
//echo __LINE__."$value_inputted_for_field_on_model_object<br>";			
//double-check there's absolutely nothing left on this string besides numbers
			$value_inputted_for_field_on_model_object = preg_replace( "/[^0-9,.]/", "", $value_inputted_for_field_on_model_object);
		}
//		echo __LINE__."$value_inputted_for_field_on_model_object<br>";
		return floatval( $value_inputted_for_field_on_model_object );
	}
	/**
	 * Returns the number formatted according to local custom (set by the country of the blog).
	 * @param float $value_on_field_to_be_outputted
	 * @return string
	 */
	function prepare_for_pretty_echoing($value_on_field_to_be_outputted,$schema = null){
		$EE = EE_Registry::instance();
		return number_format( $value_on_field_to_be_outputted, $EE->CFG->currency->dec_plc, $EE->CFG->currency->dec_mrk, $EE->CFG->currency->thsnds) ;
	}
	
	function prepare_for_set_from_db($value_found_in_db_for_model_object) {
//		echo "prepare for set from db of ";d($value_found_in_db_for_model_object);
		return floatval($value_found_in_db_for_model_object);
	}
	
}