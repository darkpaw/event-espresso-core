<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EEM_Line_Item_Test
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EEM_Line_Item_Test extends EE_UnitTestCase {
	/**
	 * @group 7239
	 */
	public function test_get_all_non_ticket_line_items_for_transaction(){
		$txn = $this->new_model_obj_with_dependencies('Transaction');
		$ticket = $this->new_model_obj_with_dependencies( 'Ticket' );
		$tax = $this->new_model_obj_with_dependencies( 'Price' );
		$line_item_for_ticket = $this->new_model_obj_with_dependencies( 'Line_Item',
				array(
					'TXN_ID' => $txn->ID(),
					'LIN_type' => EEM_Line_Item::type_line_item,
					'OBJ_type' => 'Ticket',
					'OBJ_ID' => $ticket->ID()
					) );
		$line_item_for_tax = $this->new_model_obj_with_dependencies( 'Line_Item',
				array(
					'TXN_ID' => $txn->ID(),
					'LIN_type' => EEM_Line_Item::type_tax,
					'OBJ_type' => 'Price',
					'OBJ_ID' => $tax->ID()
				));
		$line_item_for_nothing = $this->new_model_obj_with_dependencies( 'Line_Item',
				array(
					'TXN_ID' => $txn->ID(),
					'LIN_type' => EEM_Line_Item::type_line_item,
					'OBJ_type' => NULL,
					'OBJ_ID' => 0
				)
				);
		$line_item_for_venue = $this->new_model_obj_with_dependencies( 'Line_Item',
				array(
					'TXN_ID' => $txn->ID(),
					'LIN_type' => EEM_Line_Item::type_line_item,
					'OBJ_type' => 'Venue',
					'OBJ_ID' => 0
				)
				);
		$non_ticket_line_items = EEM_Line_Item::instance()->get_all_non_ticket_line_items_for_transaction( $txn );
		$this->assertEquals( 2, count( $non_ticket_line_items ) );
		$this->assertTrue( in_array( $line_item_for_nothing, $non_ticket_line_items ) );
		$this->assertTrue( in_array( $line_item_for_venue, $non_ticket_line_items ) );
	}

	/**
	 * @group 7965
	 */
	function test_delete_registrations_with_no_transaction(){
		$deletable_count = 5;
		$safe_count = 8;
		for( $i = 0; $i < $deletable_count; $i++ ) {
			$this->new_model_obj_with_dependencies( 'Line_Item', array( 'TXN_ID' => 0 ) );
		}
		for( $i=0;$i< $safe_count; $i++ ){
			$this->new_model_obj_with_dependencies( 'Line_Item' );
		}
		$deleted = EEM_Line_Item::instance()->delete_line_items_with_no_transaction();
		$this->assertEquals( $deletable_count, $deleted );
	}
}

// End of file EEM_Line_Item_Test.php