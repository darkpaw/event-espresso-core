<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }
 /**
 *
 * Class EE_SPCO_Line_Item_Display_Strategy
 *
 * Description
 *
 * @package         Event Espresso
 * @subpackage    core
 * @author				Brent Christensen
 * @since		 	   $VID:$
 *
 */

class EE_SPCO_Line_Item_Display_Strategy implements EEI_Line_Item_Display {

	private $_show_taxes = FALSE;

	/**
	 * @param EE_Line_Item $line_item
	 * @param array        $options
	 * @return mixed
	 */
	public function display_line_item( EE_Line_Item $line_item, $options = array() ) {

		EE_Registry::instance()->load_helper( 'Template' );
		EE_Registry::instance()->load_helper( 'HTML' );

		$html = '';
		// set some default options and merge with incoming
		$default_options = array(
			'show_desc' => TRUE,  // 	TRUE 		FALSE
			'odd' => FALSE
		);
		$options = array_merge( $default_options, (array)$options );

		switch( $line_item->type() ) {

			case EEM_Line_Item::type_line_item:
				// item row
				$html .= $this->_item_row( $line_item, $options );
				// got any kids?
				foreach( $line_item->children() as $child_line_item ) {
					$this->display_line_item( $child_line_item, $options );
				}
				break;

			case EEM_Line_Item::type_sub_line_item:
				$html .= $this->_sub_item_row( $line_item, $options );
				break;

			case EEM_Line_Item::type_sub_total:
				static $sub_total = 0;
				$child_line_items = $line_item->children();
				// loop thru children
				foreach( $child_line_items as $child_line_item ) {
					// recursively feed children back into this method
					$html .= $this->display_line_item( $child_line_item, $options );
				}
				if ( $line_item->total() != $sub_total && count( $child_line_items ) > 1 ) {
					$html .= $this->_sub_total_row( $line_item, __('Sub-Total', 'event_espresso'), $options );
					$sub_total = $line_item->total();
				}
				break;

			case EEM_Line_Item::type_tax:
				if ( $this->_show_taxes ) {
					$html .= $this->_tax_row( $line_item, $options );
				}
				break;

			case EEM_Line_Item::type_tax_sub_total:
				if ( $this->_show_taxes ) {
					// loop thru children
					foreach( $line_item->children() as $child_line_item ) {
						// recursively feed children back into this method
						$html .= $this->display_line_item( $child_line_item, $options );
					}
					$html .= $this->_total_row( $line_item, __('Tax Total', 'event_espresso'), $options );
				}
				break;

			case EEM_Line_Item::type_total:
				// loop thru children
				foreach( $line_item->children() as $child_line_item ) {
					// recursively feed children back into this method
					$html .= $this->display_line_item( $child_line_item, $options );
				}
				$html .= $this->_total_row( $line_item, __('Total', 'event_espresso'), $options );
				$html .= $this->_payments_and_amount_owing_rows( $line_item );
				break;

		}

		return $html;
	}



	/**
	 * 	_total_row
	 *
	 * @param EE_Line_Item $line_item
	 * @param array        $options
	 * @return mixed
	 */
	private function _item_row( EE_Line_Item $line_item, $options = array() ) {
		// start of row
		$row_class = $options['odd'] ? 'item odd' : 'item';
		$html = EEH_HTML::tr( '', '', $row_class );
		// name && desc
		$name_and_desc = apply_filters(
			'FHEE__EE_SPCO_Line_Item_Display_Strategy__item_row__name',
			$line_item->name(),
			$line_item
		);
		$name_and_desc .= apply_filters(
			'FHEE__EE_SPCO_Line_Item_Display_Strategy__item_row__desc',
			( $options['show_desc'] ? ' : ' . $line_item->desc() : '' ),
			$line_item,
			$options
		);
		// name td
		$html .= EEH_HTML::td( $name_and_desc, '',  'item_l' );
		// quantity td
		$html .= EEH_HTML::td( $line_item->quantity(), '',  'item_l jst-rght' );
		// price td
		$html .= EEH_HTML::td( $line_item->unit_price_no_code(), '',  'item_c jst-rght' );
		// total td
		$total = EEH_Template::format_currency( $line_item->total(), false, false );
		$total .= $line_item->is_taxable() ? '*' : '';
		$this->_show_taxes = $line_item->is_taxable() ? TRUE : $this->_show_taxes;
		$html .= EEH_HTML::td( $total, '',  'item_r jst-rght' );
		// end of row
		$html .= EEH_HTML::trx();
		return $html;
	}



	/**
	 * 	_sub_item_row
	 *
	 * @param EE_Line_Item $line_item
	 * @param array        $options
	 * @return mixed
	 */
	private function _sub_item_row( EE_Line_Item $line_item, $options = array() ) {
		// start of row
		$html = EEH_HTML::tr( '', 'item sub-item-row' );
		// name && desc
		$name_and_desc = $line_item->name();
		$name_and_desc .= $options['show_desc'] ? ' : ' . $line_item->desc() : '';
		// name td
		$html .= EEH_HTML::td( $name_and_desc, '',  'item_l sub-item' );
		// discount/surcharge td
		if ( $line_item->is_percent() ) {
			$html .= EEH_HTML::td( $line_item->percent() . '%', '',  'item_c' );
		} else {
			$html .= EEH_HTML::td( $line_item->unit_price_no_code(), '',  'item_c jst-rght' );
		}
		// total td
		$html .= EEH_HTML::td( EEH_Template::format_currency( $line_item->total(), false, false ), '',  'item_r jst-rght' );
		// end of row
		$html .= EEH_HTML::trx();
		return $html;
	}



	/**
	 * 	_tax_row
	 *
	 * @param EE_Line_Item $line_item
	 * @param array        $options
	 * @return mixed
	 */
	private function _tax_row( EE_Line_Item $line_item, $options = array() ) {
		// start of row
		$html = EEH_HTML::tr( '', 'item sub-item tax-total' );
		// name && desc
		$name_and_desc = $line_item->name();
		$name_and_desc .= $options['show_desc'] ? ' : ' . $line_item->desc() : '';
		// name td
		$html .= EEH_HTML::td( $name_and_desc, '',  'item_l sub-item', '', ' colspan="2"' );
		// percent td
		$html .= EEH_HTML::td( $line_item->percent() . '%', '',  'item_c', '' );
		// total td
		$html .= EEH_HTML::td( EEH_Template::format_currency( $line_item->total(), false, false ), '',  'item_r jst-rght' );
		// end of row
		$html .= EEH_HTML::trx();
		return $html;
	}



	/**
	 * 	_total_row
	 *
	 * @param EE_Line_Item $line_item
	 * @param string       $text
	 * @param array        $options
	 * @return mixed
	 */
	private function _sub_total_row( EE_Line_Item $line_item, $text = '', $options = array() ) {
		if ( $line_item->total() ) {
			return $this->_total_row( $line_item, $text, $options);
		}
		return '';
	}



	/**
	 * 	_total_row
	 *
	 * @param EE_Line_Item $line_item
	 * @param string       $text
	 * @param array        $options
	 * @return mixed
	 */
	private function _total_row( EE_Line_Item $line_item, $text = '', $options = array() ) {
		$html = '';
		if ( $line_item->total() ) {
			// start of row
			$html = EEH_HTML::tr( '', '', 'total_tr odd' );
			// total td
			$html .= EEH_HTML::td( $text, '',  'total_currency total jst-rght',  '',  ' colspan="3"' );
			// total td
			$html .= EEH_HTML::td( EEH_Template::format_currency( $line_item->total(), false, false ), '',  'total jst-rght' );
			// end of row
			$html .= EEH_HTML::trx();
		}
		return $html;
	}



	/**
	 * 	_payments_and_amount_owing_rows
	 *
	 * @param EE_Line_Item $line_item
	 * @return mixed
	 */
	private function _payments_and_amount_owing_rows( EE_Line_Item $line_item ) {
		$html = '';
		$transaction = EEM_Transaction::instance()->get_one_by_ID( $line_item->TXN_ID() );
		if ( $transaction instanceof EE_Transaction ) {
			$payments = $transaction->approved_payments();
			if ( ! empty( $payments )) {
				$owing = $line_item->total();
				foreach ( $payments as $payment ) {
					if ( $payment instanceof EE_Payment ) {
						$owing = $owing - $payment->amount();
						$payment_desc = sprintf(
							__('Payment%1$s Received: %2$s', 'event_espresso'),
							$payment->txn_id_chq_nmbr() != '' ? ' ' . $payment->txn_id_chq_nmbr() : '',
							$payment->timestamp()
						);
						// start of row
						$html .= EEH_HTML::tr( '', '', 'total_tr odd' );
						// payment desc
						$html .= EEH_HTML::td( $payment_desc, '',  '',  '',  ' colspan="3"' );
						// total td
						$html .= EEH_HTML::td( EEH_Template::format_currency( $payment->amount(), false, false ), '',  'total jst-rght' );
						// end of row
						$html .= EEH_HTML::trx();
					}
				}
				if ( $line_item->total() ) {
					// start of row
					$html .= EEH_HTML::tr( '', '', 'total_tr odd' );
					// total td
					$html .= EEH_HTML::td( __('Amount Owing', 'event_espresso'), '',  'total_currency total jst-rght',  '',  ' colspan="3"' );
					// total td
					$html .= EEH_HTML::td( EEH_Template::format_currency( $owing, false, false ), '',  'total jst-rght' );
					// end of row
					$html .= EEH_HTML::trx();
				}
			}
		}
		return $html;
	}



	/**
	 * 	_separator_row
	 *
	 * @param array        $options
	 * @return mixed
	 */
	private function _separator_row( $options = array() ) {
		// start of row
		$html = EEH_HTML::tr( EEH_HTML::td( '<hr>', '',  '',  '',  ' colspan="4"' ));
		return $html;
	}


}
// End of file EE_SPCO_Line_Item_Display_Strategy.strategy.php
// Location: /EE_SPCO_Line_Item_Display_Strategy.strategy.php