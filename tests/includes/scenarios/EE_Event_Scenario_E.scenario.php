<?php
if (!defined('EVENT_ESPRESSO_VERSION'))
	exit('No direct script access allowed');

/**
 * This scenario creates an event that has:
 * - Four Datetimes
 *      - D1 - reg limit 55
 *      - D2 - reg limit 20
 *      - D3 - reg limit 12
 *      - D4 - reg limit 30
 * - Five Tickets
 *      - TA - qty 12 (D1, D2, D3)
 *      - TB - qty 20 (D1,D2,D4)
 *      - TC - qty 30 (D1, D4)
 *      - TD - qty 12 (D1, D3, D4)
 *      - TE - qty 30 (D4)
 *
 * @package    Event Espresso
 * @subpackage tests/scenarios
 * @author     Darren Ethier
 */
class EE_Event_Scenario_E extends EE_Test_Scenario {

	public function __construct( EE_UnitTestCase $eetest ) {
		$this->type = 'event';
		$this->name = 'Event Scenario E';
		parent::__construct( $eetest );
	}

	protected function _set_up_expected(){
		$this->_expected_values = array(
			'total_available_spaces' => 42,
			'total_remaining_spaces' => 42
		);
	}


	protected function _set_up_scenario(){
		$event = $this->generate_objects_for_scenario(
			array(
				'Event' => array(
					'EVT_name'   => 'Test Scenario EVT E',
					'Datetime'   => array(
						'DTT_name'      => 'Datetime 1',
						'DTT_reg_limit' => 55,
						'Ticket'        => array(
							'TKT_name' => 'Ticket A',
							'TKT_qty'  => 12,
						),
						'Ticket*'       => array(
							'TKT_name' => 'Ticket B',
							'TKT_qty'  => 20,
						),
						'Ticket**'      => array(
							'TKT_name' => 'Ticket C',
							'TKT_qty'  => 30,
						),
						'Ticket***'      => array(
							'TKT_name' => 'Ticket D',
							'TKT_qty'  => 12,
						),
					),
					'Datetime*'  => array(
						'DTT_name'      => 'Datetime 2',
						'DTT_reg_limit' => 20,
						'Ticket'        => array(
							'TKT_name' => 'Ticket A',
							'TKT_qty'  => 12,
						),
						'Ticket*'       => array(
							'TKT_name' => 'Ticket B',
							'TKT_qty'  => 20,
						),
					),
					'Datetime**' => array(
						'DTT_name'      => 'Datetime 3',
						'DTT_reg_limit' => 12,
						'Ticket'        => array(
							'TKT_name' => 'Ticket A',
							'TKT_qty'  => 12,
						),
						'Ticket*'       => array(
							'TKT_name' => 'Ticket D',
							'TKT_qty'  => 12,
						),
						'Ticket**'      => array(
							'TKT_name' => 'Ticket E',
							'TKT_qty'  => 30,
						),
					),
					'Datetime***' => array(
						'DTT_name'      => 'Datetime 4',
						'DTT_reg_limit' => 30,
						'Ticket'       => array(
							'TKT_name' => 'Ticket B',
							'TKT_qty'  => 20,
						),
						'Ticket*'      => array(
							'TKT_name' => 'Ticket C',
							'TKT_qty'  => 30,
						),
						'Ticket**'      => array(
							'TKT_name' => 'Ticket D',
							'TKT_qty'  => 12,
						),
					),
				),
			)
		);
		//assign the event object as the scenario object
		$this->_scenario_object = reset( $event );
	}



	protected function _get_scenario_object(){
		return $this->_scenario_object;
	}
}