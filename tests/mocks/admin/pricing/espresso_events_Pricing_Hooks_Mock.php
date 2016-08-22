<?php

if (!defined('EVENT_ESPRESSO_VERSION'))
	exit('No direct script access allowed');


require_once( EE_CAFF_PATH . 'admin/new/pricing/espresso_events_Pricing_Hooks.class.php' );



/**
 *
 * espresso_events_Pricing_Hooks_mock
 *
 * @package			Event Espresso
 * @subpackage		mocks
 * @author			Darren
 * @since  4.6
 *
 */
class espresso_events_Pricing_Hooks_Mock extends espresso_events_Pricing_Hooks {

	/**
	 * constructor
	 * @param EE_Admin_Page $admin_page the calling admin_page_object
	 */
	public function __construct( EE_Admin_Page $admin_page = null ) {
		$admin_page = ! $admin_page instanceof EE_Admin_Page ? new Admin_Mock_Valid_Admin_Page() : $admin_page;
		parent::__construct( $admin_page );
	}



	/**
	 * Used to overload the default _date_format_strings for testing with.
	 *
	 * @see _date_format_strings property in espresso_events_Pricing_Hooks for more info.
	 *
	 * @param array $format_strings
	 */
	public function set_date_format_strings( $format_strings ) {
		$this->_date_format_strings = $format_strings;
	}



	/**
	 * @param $evt_obj
	 * @param $data
	 * @return \EE_Datetime[]
	 */
	public function update_dtts( $evt_obj, $data ) {
		return $this->_update_dtts( $evt_obj, $data );
	}



	/**
	 * @param $evtobj
	 * @param $saved_dtts
	 * @param $data
	 * @return \EE_Ticket[]
	 */
	public function update_tkts( $evtobj, $saved_dtts, $data ) {
		return $this->_update_tkts( $evtobj, $saved_dtts, $data );
	}


} //end espresso_events_Pricing_Hooks_mock
