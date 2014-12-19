<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * EE_Select_Display_Strategy_Test
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_Select_Display_Strategy_Test extends EE_UnitTestCase{

	public function test_display_flat_array(){
		$form = new EE_Form_Section_Proper( array(
			'name' => 'form',
			'subsections' => array(
				'input1' => new EE_Select_Input( array( 'foo' => 'Foo', 'bar' => 'Bar', "baz'em" => 'Baz' ) )
			)
		));
		$input = $form->get_input( 'input1' );
		$expected_output =
'
<select id="form-input1" name="form[input1]" class="" style="">
	<option value="foo">Foo</option>
	<option value="bar">Bar</option>
	<option value="baz&#039;em">Baz</option>
</select>';
		$this->assertEquals( $expected_output, $input->get_html_for_input() );
		//now if we set the default, does it get selected?
		$form->populate_defaults( array(
			'input1' => "baz'em"
		));
		$this->assertEquals( "baz'em", $input->normalized_value() );
		$this->assertEquals( "baz'em", $input->raw_value() );
		$expected_output2 =
'
<select id="form-input1" name="form[input1]" class="" style="">
	<option value="foo">Foo</option>
	<option value="bar">Bar</option>
	<option value="baz&#039;em" selected="selected">Baz</option>
</select>';
		$this->assertEquals( $expected_output2, $input->get_html_for_input() );

	}
	public function test_display_flat_multidimensional_array(){
		$input = new EE_Select_Input( array(
					'code_var_names' => array(
						'foo' => 'Foo',
						'bar' => 'Bar',
						'baz' => 'Baz' ),
					'monkey_types' => array(
						'chimp' => 'Chimp',
						'orang' => 'Orangutang',
						'baboob' => 'Baboon'
					)));
		$output = $input->get_html_for_input();
		$expected_output =
'
<select id="" name="" class="" style="">
	<optgroup label="code_var_names">
		<option value="foo">Foo</option>
		<option value="bar">Bar</option>
		<option value="baz">Baz</option>
	</optgroup>
	<optgroup label="monkey_types">
		<option value="chimp">Chimp</option>
		<option value="orang">Orangutang</option>
		<option value="baboob">Baboon</option>
	</optgroup>
</select>';
		$this->assertEquals( $expected_output, $output );


	}
}

// End of file EE_Select_Display_Strategy_Test.php