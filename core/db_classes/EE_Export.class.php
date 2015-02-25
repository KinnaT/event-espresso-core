<?php if (!defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );
/**
 * EE_Export class
 *
 * @package				Event Espresso
 * @subpackage			includes/functions
 * @author					Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
 class EE_Export {


  // instance of the EE_Export object
	private static $_instance = NULL;

  // instance of the EE_CSV object
	/**
	 *
	 * @var EE_CSV
	 */
	var $EE_CSV = NULL;

	var $_basic_header = array();
	var $_question_groups = array();
	var $_event_id = FALSE;
	var $_event_identifier = FALSE;
	var $_event_name = FALSE;
	var $_event_description = FALSE;
	var $_event_meta = FALSE;

	private $_req_data = array();



	 /**
	  *        private constructor to prevent direct creation
	  *
	  * @Constructor
	  * @access private
	  * @param array $request_data
	  */
 	private function __construct( $request_data = array() ) {
		$this->_req_data = $request_data;
		$this->today = date("Y-m-d",time());
		require_once( EE_CLASSES . 'EE_CSV.class.php' );
		$this->EE_CSV= EE_CSV::instance();
	}



	 /**
	  *        @ singleton method used to instantiate class object
	  *        @ access public
	  *
	  * @param array $request_data
	  * @return \EE_Export
	  */
	public static function instance( $request_data = array() ) {
		// check if class object is instantiated
		if ( self::$_instance === NULL  or ! is_object( self::$_instance ) or ! ( self::$_instance instanceof EE_Export )) {
			self::$_instance = new self( $request_data );
		}
		return self::$_instance;
	}


	/**
	 *			@Export Event Espresso data - routes export requests
	 *		  @access public
	 *			@return void | bool
	 */
	public function export() {

		// in case of bulk exports, the "actual" action will be in action2, but first check regular action for "export" keyword
		if ( isset( $this->_req_data['action'] ) && strpos( $this->_req_data['action'], 'export' ) === FALSE ) {
			// check if action2 has export action
			if ( isset( $this->_req_data['action2'] ) && strpos( $this->_req_data['action2'], 'export' ) !== FALSE ) {
				// whoop! there it is!
				$this->_req_data['action'] = $this->_req_data['action2'];
			}
		}

		$this->_req_data['export'] = isset( $this->_req_data['export'] ) ? $this->_req_data['export'] : '';

		switch ($this->_req_data['export']) {
			case 'report':
				switch ($this->_req_data['action']) {

					case 'everything':
						$this->export_freakin_everything();
					break;

					case "event";
					case "export_events";
					case 'all_event_data' :
						$this->export_all_event_data();
					break;

					case 'registrations_report_for_event':
						$this->report_registrations_for_event( $this->_req_data['EVT_ID'] );
					break;

					case 'attendees':
						$this->export_attendees();
					break;

					case 'categories':
						$this->export_categories();
					break;

					default:
						EE_Error::add_error(__('An error occurred! The requested export report could not be found.','event_espresso'), __FILE__, __FUNCTION__, __LINE__ ) ;
						return FALSE;
					break;

				}
			break; // end of switch export : report
			default:
			break;
		} // end of switch export

		exit;

	}



	/**
	 *		Export data for FREAKIN EVERYTHING !!!
	 *
	 *		usage: http://your-domain.tld/wp-admin/admin.php?event_espresso&export=report&action=everything&type=csv
	 *
	 *		@access public
	 *		@return void
	 */
	function export_freakin_everything() {

		$models_to_export = EE_Registry::instance()->non_abstract_db_models;

		$table_data = $this->_get_export_data_for_models( array_keys( $models_to_export ) );

		$filename = $this->generate_filename ( 'full-db-export' );

		if ( ! $this->EE_CSV->export_multiple_model_data_to_csv( $filename,$table_data )) {
			EE_Error::add_error(__("An error occurred and the Event details could not be exported from the database.", "event_espresso"), __FILE__, __FUNCTION__, __LINE__ );
		}
	}

	/**
	 * Downloads a CSV file with all the columns, but no data. This should be used for importing
	 * @return null kills execution
	 */
	function export_sample(){
		$event = EEM_Event::instance()->get_one();
		$this->_req_data['EVT_ID'] = $event->ID();
		$this->export_all_event_data();
	}




	/**
	 *			@Export data for ALL events
	 *		  @access public
	 *			@return void
	 */
	function export_all_event_data() {
		// are any Event IDs set?
		$event_query_params = array();
		$related_models_query_params = array();
		$related_through_reg_query_params = array();
		$datetime_ticket_query_params = array();
		$price_query_params = array();
		$price_type_query_params = array();
		$term_query_params  = array();
		$state_country_query_params = array();
		$question_group_query_params = array();
		$question_query_params = array();
		if ( isset( $this->_req_data['EVT_ID'] )) {
			// do we have an array of IDs ?

			if ( is_array( $this->_req_data['EVT_ID'] )) {
				$EVT_IDs =  array_map( 'sanitize_text_field', $this->_req_data['EVT_ID'] );
				$value_to_equal = array('IN',$EVT_IDs);
				$filename = 'events';
			} else {
				// generate regular where = clause
				$EVT_ID = absint( $this->_req_data['EVT_ID'] );
				$value_to_equal = $EVT_ID;
				$event = EE_Registry::instance()->load_model('Event')->get_one_by_ID($EVT_ID);

				$filename = 'event-' . ( $event instanceof EE_Event ? $event->slug() : __( 'unknown', 'event_espresso' ) );

			}
			$event_query_params[0]['EVT_ID'] =$value_to_equal;
			$related_models_query_params[0]['Event.EVT_ID'] = $value_to_equal;
			$related_through_reg_query_params[0]['Registration.EVT_ID'] = $value_to_equal;
			$datetime_ticket_query_params[0]['Datetime.EVT_ID'] = $value_to_equal;
			$price_query_params[0]['Ticket.Datetime.EVT_ID'] = $value_to_equal;
			$price_type_query_params[0]['Price.Ticket.Datetime.EVT_ID'] = $value_to_equal;
			$term_query_params[0]['Term_Taxonomy.Event.EVT_ID'] = $value_to_equal;
			$state_country_query_params[0]['Venue.Event.EVT_ID'] = $value_to_equal;
			$question_group_query_params[0]['Event.EVT_ID'] = $value_to_equal;
			$question_query_params[0]['Question_Group.Event.EVT_ID'] = $value_to_equal;

		} else {
			$filename = 'all-events';
		}


		// array in the format:  table name =>  query where clause
		$models_to_export = array(
				'Event'=>$event_query_params,
				'Datetime'=>$related_models_query_params,
				'Ticket_Template'=>$price_query_params,
				'Ticket'=>$datetime_ticket_query_params,
				'Datetime_Ticket'=>$datetime_ticket_query_params,
				'Price_Type'=>$price_type_query_params,
				'Price'=>$price_query_params,
				'Ticket_Price'=>$price_query_params,
				'Term'=>$term_query_params,
				'Term_Taxonomy'=>$related_models_query_params,
				'Term_Relationship'=>$related_models_query_params, //model has NO primary key...
				'Country'=>$state_country_query_params,
				'State'=>$state_country_query_params,
				'Venue'=>$related_models_query_params,
				'Event_Venue'=>$related_models_query_params,
				'Question_Group'=>$question_group_query_params,
				'Event_Question_Group'=>$question_group_query_params,
				'Question'=>$question_query_params,
				'Question_Group_Question'=>$question_query_params,
//				'Transaction'=>$related_through_reg_query_params,
//				'Registration'=>$related_models_query_params,
//				'Attendee'=>$related_through_reg_query_params,
//				'Line_Item'=>

			);

		//because we're using the event IDs in teh query parameters,
		//and won't get any non-events; also
		//we can forego the default query params

		$model_data = $this->_get_export_data_for_models( $models_to_export );

		$filename = $this->generate_filename ( $filename );

		if ( ! $this->EE_CSV->export_multiple_model_data_to_csv( $filename, $model_data )) {
			EE_Error::add_error(__("'An error occurred and the Event details could not be exported from the database.'", "event_espresso"), __FILE__, __FUNCTION__, __LINE__ );
		}
	}


	/**
	 *			@Export data for ALL attendees
	 *		  @access public
	 *			@return void
	 */
	function export_attendees() {

		$states_that_have_an_attendee = EEM_State::instance()->get_all(array(0=>array('Attendee.ATT_ID'=>array('IS NOT NULL'))));
		$countries_that_have_an_attendee = EEM_Country::instance()->get_all(array(0=>array('Attendee.ATT_ID'=>array('IS NOT NULL'))));
//		$states_to_export_query_params
		$models_to_export = array(
			'Country'=>array(array('CNT_ISO'=>array('IN',array_keys($countries_that_have_an_attendee)))),
			'State'=>array(array('STA_ID'=>array('IN',array_keys($states_that_have_an_attendee)))),
			'Attendee'=>array(),
		);



		$model_data = $this->_get_export_data_for_models( $models_to_export );
		$filename = $this->generate_filename ( 'all-attendees' );

		if ( ! $this->EE_CSV->export_multiple_model_data_to_csv( $filename, $model_data )) {
			EE_Error::add_error(__('An error occurred and the Attendee data could not be exported from the database.','event_espresso'), __FILE__, __FUNCTION__, __LINE__ );
		}
	}

	/**
	 * Export a custom CSV of registration info including: A bunch of the reg fields, the time of the event, the price name,
	 * and the questions associated with the registrations
	 * @param int $event_id
	 */
	function report_registrations_for_event( $event_id = NULL ){
		$reg_fields_to_include = array(
				'REG_ID',
				'REG_date',
				'REG_code',
				'REG_count',
				'REG_final_price'

		);
		$att_fields_to_include = array(
			'ATT_fname',
			'ATT_lname',
			'ATT_email',
			'ATT_address',
			'ATT_address2',
			'ATT_city',
			'STA_ID',
			'CNT_ISO',
			'ATT_zip',
			'ATT_phone',
		);

		$registrations_csv_ready_array = array();
		$reg_model = EE_Registry::instance()->load_model('Registration');
		$query_params = apply_filters(
			'FHEE__EE_Export__report_registration_for_event',
			array(
				array(
					'Transaction.STS_ID' => array( 'NOT IN', array( EEM_Transaction::failed_status_code, EEM_Transaction::abandoned_status_code ) ),
					'Ticket.TKT_deleted' => array( 'IN', array( true, false ) )
					),
				'order_by' => array('Transaction.TXN_ID'=>'asc','REG_count'=>'asc'),
				'force_join' => array( 'Transaction', 'Ticket' )
			),
			$event_id
		);
		if( $event_id ){
			$query_params[0]['EVT_ID'] =  $event_id;
		}else{
			$query_params[ 'force_join' ][] = 'Event';
		}
		$registrations = $reg_model->get_all( $query_params );

		//get all questions which relate to someone in this group
		$registration_ids = array_keys($registrations);
//		EEM_Question::instance()->show_next_x_db_queries();
		$questions_for_these_registrations = EEM_Question::instance()->get_all(array(array('Answer.REG_ID'=>array('IN',$registration_ids))));
		foreach($registrations as $registration){
			if ( $registration instanceof EE_Registration ) {
				$reg_csv_array = array();
				if( ! $event_id ){
					//get the event's name and Id
					$reg_csv_array[ __( 'Event', 'event_espresso' ) ] = $registration->event_name() . '(' . $registration->event_ID() . ')';
				}
				/*@var $registration EE_Registration */
				foreach($reg_fields_to_include as $field_name){
					$field = $reg_model->field_settings_for($field_name);
					if($field_name == 'REG_final_price'){
						$value = $registration->get_pretty($field_name,'localized_float');
					}else{
						$value = $registration->get_pretty($field->get_name());
					}
					$reg_csv_array[$this->_get_column_name_for_field($field)] = $value;
					if($field_name == 'REG_final_price'){
						//add a column named Currency after the final price
						$reg_csv_array[__("Currency", "event_espresso")] = EE_Config::instance()->currency->code;
					}
				}
				//get pretty status
				$reg_csv_array[__("Registration Status", 'event_espresso')] = $registration->pretty_status();
				//get pretty trnasaction status
				$reg_csv_array[__("Transaction Status", 'event_espresso')] = $registration->transaction()->pretty_status();
				$reg_csv_array[ __( 'Transaction Amount Due', 'event_espresso' ) ] = $registration->is_primary_registrant() ? $registration->transaction()->get_pretty('TXN_total', 'localized_float') : '0.00';
				$reg_csv_array[ __( 'Amount Paid', 'event_espresso' )] = $registration->is_primary_registrant() ? $registration->transaction()->get_pretty( 'TXN_paid', 'localized_float' ) : '0.00';
				//just get all the payment methods used for this transaction (if primary registrant and something has been paid)
				$reg_csv_array[ __( 'Payment Method', 'event_espresso' )] = $registration->is_primary_registrant() && $registration->transaction()->paid() ? implode(", ",EEM_Payment_Method::instance()->get_col(
						array(
							array(
								'Payment.TXN_ID' => $registration->transaction_ID(),
								'Payment.STS_ID' => EEM_Payment::status_id_approved
							)
						), 'PMD_admin_name')) : '' ;
				//get whether or not the user has checked in
				$reg_csv_array[__("Check-Ins", "event_espresso")] = $registration->count_checkins();
				//get ticket of registration and its price
				$ticket_model = EE_Registry::instance()->load_model('Ticket');
				if( $registration->ticket() ) {
					$ticket_name = $registration->ticket()->name();
					$datetimes_strings = array();
					foreach($registration->ticket()->datetimes() as $datetime){
						$datetimes_strings[] = $datetime->start_date_and_time();
					}

				} else {
					$ticket_name = __( 'Unknown', 'event_espresso' );
					$datetimes_strings = array( __( 'Unknown', 'event_espresso' ) );
				}
				$reg_csv_array[$ticket_model->field_settings_for('TKT_name')->get_nicename()] = $ticket_name;
				$reg_csv_array[__("Datetimes of Ticket", "event_espresso")] = implode(", ", $datetimes_strings);
				//get datetime(s) of registration

				//add attendee columns
				$attendee = $registration->attendee();
				foreach($att_fields_to_include as $att_field_name){
					if($attendee){
						if($att_field_name == 'STA_ID'){
							$state = $attendee->state_obj();
							if($state){
								$value = $state->name();
							}else{
								$value = '';
							}
						}elseif($att_field_name == 'CNT_ISO'){
							$country = $attendee->country_obj();
							if($country){
								$value = $country->name();
							}else{
								$value = '';
							}
						}else{
							$value = $attendee->get_pretty($att_field_name);
						}
					}else{
						$value = '';
					}
					$field_obj = EEM_Attendee::instance()->field_settings_for($att_field_name);
					$reg_csv_array[$this->_get_column_name_for_field($field_obj)] = $value;
				}

				//make sure each registration has the same questions in the same order
				foreach($questions_for_these_registrations as $question){
					if ( $question instanceof EE_Question ) {
						if( ! isset($reg_csv_array[$question->admin_label()])){
							$reg_csv_array[$question->admin_label()] = null;
						}
					}
				}
				//now fill out the questions THEY answered
				foreach($registration->answers() as $answer){
					/* @var $answer EE_Answer */
					if( $answer->question() instanceof EE_Question ){
						$question_label = $answer->question()->admin_label();
					}else{
						$question_label = sprintf( __( 'Question $s', 'event_espresso' ), $answer->question_ID() );
					}
					$reg_csv_array[ $question_label ] = $answer->pretty_value();
				}
				$registrations_csv_ready_array[] = $reg_csv_array;
			}
		}

		//if we couldn't export anything, we want to at least show the column headers
		if(empty($registrations_csv_ready_array)){
			$reg_csv_array = array();
			$model_and_fields_to_include = array(
				'Registration' => $reg_fields_to_include,
				'Attendee' => $att_fields_to_include
			);
			foreach($model_and_fields_to_include as $model_name => $field_list){
				$model = EE_Registry::instance()->load_model($model_name);
				foreach($field_list as $field_name){
					$field = $model->field_settings_for($field_name);
					$reg_csv_array[$this->_get_column_name_for_field($field)] = null;//$registration->get($field->get_name());
				}
			}
			$registrations_csv_ready_array [] = $reg_csv_array;
		}
		if( $event_id ){
			$event = EEM_Event::instance()->get_one_by_ID($event_id);
			$event_slug =  $event instanceof EE_Event ? $event->slug() : __( 'unknown', 'event_espresso' );
		}else{
			$event_slug = __( 'all', 'event_espresso' );
		}
		$filename = sprintf( "registrations-for-%s", $event_slug );

		$handle = $this->EE_CSV->begin_sending_csv( $filename);
		$this->EE_CSV->write_data_array_to_csv($handle, $registrations_csv_ready_array);
		$this->EE_CSV->end_sending_csv($handle);
	}

	/**
	 * Gets the 'normal' column named for fields
	 * @param EE_Model_Field_Base $field
	 * @return string
	 */
	protected function _get_column_name_for_field(EE_Model_Field_Base $field){
		return $field->get_nicename()."[".$field->get_name()."]";
	}


	/**
	 *			@Export data for ALL events
	 *		  @access public
	 *			@return void
	 */
	function export_categories() {
		// are any Event IDs set?
		$query_params = array();
		if ( isset( $this->_req_data['EVT_CAT_ID'] )) {
			// do we have an array of IDs ?
			if ( is_array( $this->_req_data['EVT_CAT_ID'] )) {
				// generate an "IN (CSV)" where clause
				$EVT_CAT_IDs = array_map( 'sanitize_text_field', $this->_req_data['EVT_CAT_ID'] );
				$filename = 'event-categories';
				$query_params[0]['term_taxonomy_id'] = array('IN',$EVT_CAT_IDs);
			} else {
				// generate regular where = clause
				$EVT_CAT_ID = absint( $this->_req_data['EVT_CAT_ID'] );
				$filename = 'event-category#' . $EVT_CAT_ID;
				$query_params[0]['term_taxonomy_id'] = $EVT_CAT_ID;
			}
		} else {
			// no IDs means we will d/l the entire table
			$filename = 'all-categories';
		}

		$tables_to_export = array(
				'Term_Taxonomy' => $query_params
			);

		$table_data = $this->_get_export_data_for_models( $tables_to_export );
		$filename = $this->generate_filename ( $filename );

		if ( ! $this->EE_CSV->export_multiple_model_data_to_csv( $filename, $table_data )) {
			EE_Error::add_error(__('An error occurred and the Category details could not be exported from the database.','event_espresso'), __FILE__, __FUNCTION__, __LINE__ );
		}
	}


	/**
	 *			@process export name to create a suitable filename
	 *		  @access private
	 *		  @param string - export_name
	 *			@return string on success, FALSE on fail
	 */
	private function generate_filename ( $export_name = '' ) {
		if ( $export_name != '' ) {
			$filename = get_bloginfo('name') . '-' . $export_name;
			$filename = sanitize_key( $filename ) . '-' . $this->today;
			return $filename;
		}	 else {
			EE_Error::add_error(__("No filename was provided", "event_espresso"), __FILE__, __FUNCTION__, __LINE__ );
		}
		return false;
	}



	/**
	 *	@recursive function for exporting table data and merging the results with the next results
	 *	@access private
	 *	@param array $models_to_export keys are model names (eg 'Event', 'Attendee', etc.) and values are arrays of query params like on EEM_Base::get_all
	 *	@return array on success, FALSE on fail
	 */
	private function _get_export_data_for_models( $models_to_export = array() ) {
		$table_data = FALSE;
		if ( is_array( $models_to_export ) ) {
			foreach ( $models_to_export as $model_name => $query_params ) {
				//check for a numerically-indexed array. in that case, $model_name is the value!!
				if(is_int($model_name)){
					$model_name = $query_params;
					$query_params = array();
				}
				if( ! isset( $query_params[ 'default_where_conditions'] ) ) {
					$query_params[ 'default_where_conditions' ] = 'minimum';
				}
				$model = EE_Registry::instance()->load_model($model_name);
				$model_objects = $model->get_all($query_params);


				$table_data[$model_name] = array();
				foreach($model_objects as $model_object){
					$model_data_array = array();
					$fields = $model->field_settings();
					foreach($fields as $field){
						$column_name = $field->get_nicename()."[".$field->get_name()."]";
						if($field instanceof EE_Datetime_Field){
//							$field->set_date_format('Y-m-d');
//							$field->set_time_format('H:i:s');
							$model_data_array[$column_name] = $model_object->get_datetime($field->get_name(),'Y-m-d','H:i:s');
						}
						else{
							$model_data_array[$column_name] = $model_object->get($field->get_name());
						}
					}
					$table_data[$model_name][] = $model_data_array;
				}

			}
		}
		return $table_data;
	}










}
/* End of file EE_Export.class.php */
/* Location: /includes/classes/EE_Export.class.php */
