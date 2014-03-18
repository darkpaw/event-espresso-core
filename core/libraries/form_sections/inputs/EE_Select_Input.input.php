<?php

class EE_Select_Input extends EE_Form_Input_Base{
	
	function __construct($select_options, $options = array()){
		$this->_set_display_strategy(new EE_Select_Display_Strategy($select_options));
		//get teh first item in teh select options. Depending on what it is, use a different normalization strategy
		$select_options = $this->_flatten_select_options($select_options);
		$select_option_keys = array_keys($select_options);
		$first_key = reset($select_option_keys);
		if(is_int($first_key)){
			$normalization = new EE_Int_Normalization();
		}elseif(is_string($first_key)){
			$normalization = new EE_Text_Normalization();
		}
		$this->_set_normalization_strategy($normalization);
		$this->_add_validation_strategy(new EE_Enum_Validation_Strategy($select_options));
		parent::__construct($options);
	}
	/**
	 * Makes sure $arr is a flat array, not a multidiemnsional one
	 * @param array $arr
	 * @return array
	 */
	protected function _flatten_select_options($arr){
		EE_Registry::instance()->load_helper('Array');
		if(EEH_Array::is_multi_dimensional_array($arr)){
			$flat_array = array();
			foreach($arr as $subarray){
				foreach($subarray as $key => $value){
					$flat_array[$key] = $value;
				}
			}
			return $flat_array;
		}else{
			return $arr;
		}
	}
}