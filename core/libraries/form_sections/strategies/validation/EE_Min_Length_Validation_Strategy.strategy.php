<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }
/**
 * EE_Min_Length_Validation_Strategy
 *
 * Validates that the normalized value is at least the specified length
 *
 * @package			Event Espresso
 * @subpackage	Expression package is undefined on line 19, column 19 in Templates/Scripting/PHPClass.php.
 * @author				Mike Nelson
 */
class EE_Min_Length_Validation_Strategy extends EE_Validation_Strategy_Base{


	/*
	 * indicates whether or not this validation strategy is general enough that it can be applied to any/most input
	 * a validation strategy that only applies to one,or very few, input type(s) would set this value to false
	 *
	 *  @var boolean $_generally_applicable
	 */
	protected static $_generally_applicable = true;

	protected $_min_length;



	/**
	 * EE_Min_Length_Validation_Strategy constructor.
	 *
	 * @param null $validation_error_message
	 * @param int  $min_length
	 */
	public function __construct( $validation_error_message = NULL, $min_length = 0 ) {
		$this->_min_length = absint( $min_length );
		parent::__construct( $validation_error_message );
	}



	/**
	 * @param mixed $normalized_value
	 * @throws \EE_Validation_Error
	 */
	public function validate($normalized_value) {
		if( $this->_min_length > 0 &&
				$normalized_value &&
				is_string( $normalized_value ) &&
				strlen( $normalized_value ) < $this->_min_length){
			throw new EE_Validation_Error( $this->get_validation_error_message(), 'minlength' );
		}
	}

	/**
	 * @return array
	 */
	function get_jquery_validation_rule_array(){
		return array( 'minlength'=> $this->_min_length, 'messages' => array( 'minlength' => $this->get_validation_error_message() ) );
	}
}

// End of file EE_FUll_HTML_Validation_Strategy.strategy.php