<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }
/**
 * Class EE_Middleware
 *
 * Parent class for EE_Middleware Request decorators.
 * Accepts an instance of another EE_Middleware class,
 * and handles the passing of EE_Request and EE_Response objects to and from it
 * EE_Middleware classes are for functionality that needs to run on nearly EVERY request.
 * They can perform their logic either before or after the core application has run:
 * 	(see documentation for the handle() method below)
 * EE_Middleware classes should NOT depend on core functionality,
 * because there is no guarantee that the core application has run
 *
 * @package 	Event Espresso
 * @subpackage 	core
 * @author 		Brent Christensen
 * @since       4.8.20
 *
 */
abstract class EE_Middleware implements EEI_Request_Decorator {

	/**
	 * @access 	protected
	 * @type    EEI_Request_Decorator $request_stack
	 */
	protected $request_stack = null;

	/**
	 * @access 	protected
	 * @type 	EE_Request $request
	 */
	protected $request;

	/**
	 * @access 	protected
	 * @type 	EE_Response $response
	 */
	protected $response;



	/**
	 * @access 	public
	 * @param 	\EEI_Request_Decorator $request_stack
	 */
	public function __construct( EEI_Request_Decorator $request_stack ) {
		$this->request_stack = $request_stack;
	}



	/**
	 * process_request_stack
	 *
	 * @access 	protected
	 * @param 	EE_Request  $request
	 * @param 	EE_Response $response
	 * @return 	EE_Response
	 */
	protected function process_request_stack( EE_Request $request, EE_Response $response ) {
		$this->request = $request;
		$this->response = $response;
		if ( ! $this->response->request_terminated() ) {
			$this->response = $this->request_stack->handle_request( $this->request, $this->response );
		} else {
			espresso_deactivate_plugin( EE_PLUGIN_BASENAME );
		}
		return $this->response;
	}



}



// End of file EE_Middleware.core.php
// Location: /core/middleware/EE_Middleware.core.php