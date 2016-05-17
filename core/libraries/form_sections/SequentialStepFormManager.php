<?php
namespace EventEspresso\core\libraries\form_sections;

use EE_Error;
use EE_Request;
use EventEspresso\Core\Exceptions\BaseException;
use EventEspresso\Core\Exceptions\InvalidClassException;
use EventEspresso\Core\Exceptions\InvalidDataTypeException;
use EventEspresso\Core\Exceptions\InvalidEntityException;
use EventEspresso\Core\Exceptions\InvalidIdentifierException;
use EventEspresso\Core\Exceptions\InvalidInterfaceException;
use EventEspresso\core\services\collections\Collection;
use EventEspresso\core\services\progress_steps\ProgressStep;
use EventEspresso\core\services\progress_steps\ProgressStepCollection;
use EventEspresso\core\services\progress_steps\ProgressStepManager;
use Exception;
use InvalidArgumentException;

if ( ! defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}



/**
 * Class SequentialStepFormManager
 * abstract parent class for managing a series of SequentialStepForm classes
 * as well as their corresponding Progress Step classes
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         4.9.0
 */
abstract class SequentialStepFormManager {

	/**
	 * a simplified URL with no form related parameters
	 * that will be used to build the form's redirect URLs
	 *
	 * @var string $base_url
	 */
	private $base_url = '';

	/**
	 * the key used for the URL param that denotes the current form step
	 * defaults to 'ee-form-step'
	 *
	 * @var string $form_step_url_key
	 */
	private $form_step_url_key = '';

	/**
	 * @var string $default_form_step
	 */
	private $default_form_step = '';

	/**
	 * @var string $form_action
	 */
	private $form_action;

	/**
	 * value of one of the string constant above
	 *
	 * @var string $form_config
	 */
	private $form_config;

	/**
	 * @var string $progress_step_style
	 */
	private $progress_step_style = '';

	/**
	 * @var EE_Request $request
	 */
	private $request;

	/**
	 * @var Collection $form_steps
	 */
	protected $form_steps;

	/**
	 * @var ProgressStepManager $progress_step_manager
	 */
	protected $progress_step_manager;



	/**
	 * @return Collection|null
	 */
	abstract protected function getFormStepsCollection();



	/**
	 * StepsManager constructor
	 *
	 * @param string     $base_url
	 * @param string     $default_form_step
	 * @param string     $form_action
	 * @param string     $form_config
	 * @param EE_Request $request
	 * @param string     $progress_step_style
	 * @throws InvalidDataTypeException
	 * @throws InvalidArgumentException
	 * @throws \EventEspresso\Core\Exceptions\BaseException
	 */
	public function __construct(
		$base_url,
		$default_form_step,
		$form_action = '',
		$form_config = Form::ADD_FORM_TAGS_AND_SUBMIT,
		$progress_step_style = 'number_bubbles',
		EE_Request $request
	) {
		$this->setBaseUrl( $base_url );
		$this->setDefaultFormStep( $default_form_step );
		$this->setFormAction( $form_action );
		$this->setFormConfig( $form_config );
		$this->setProgressStepStyle( $progress_step_style );
		$this->request = $request;
	}



	/**
	 * @return string
	 * @throws \EventEspresso\Core\Exceptions\InvalidDataTypeException
	 * @throws \EventEspresso\Core\Exceptions\BaseException
	 */
	public function baseUrl() {
		if ( strpos( $this->base_url, $this->getCurrentStep()->slug() ) === false ) {
			add_query_arg(
				array( $this->form_step_url_key => $this->getCurrentStep()->slug() ),
				$this->base_url
			);
		}
		return $this->base_url;
	}



	/**
	 * @param string $base_url
	 * @throws InvalidDataTypeException
	 * @throws InvalidArgumentException
	 */
	protected function setBaseUrl( $base_url ) {
		if ( ! is_string( $base_url ) ) {
			throw new InvalidDataTypeException( '$base_url', $base_url, 'string' );
		}
		if ( empty( $base_url ) ) {
			throw new InvalidArgumentException(
				__( 'The base URL can not be an empty string.', 'event_espresso' )
			);
		}
		$this->base_url = $base_url;
	}



	/**
	 * @return string
	 * @throws InvalidDataTypeException
	 */
	public function formStepUrlKey() {
		if ( empty( $this->form_step_url_key ) ) {
			$this->setFormStepUrlKey();
		}
		return $this->form_step_url_key;
	}



	/**
	 * @param string $form_step_url_key
	 * @throws InvalidDataTypeException
	 */
	public function setFormStepUrlKey( $form_step_url_key = 'ee-form-step' ) {
		if ( ! is_string( $form_step_url_key ) ) {
			throw new InvalidDataTypeException( '$form_step_key', $form_step_url_key, 'string' );
		}
		$this->form_step_url_key = ! empty( $form_step_url_key ) ? $form_step_url_key : 'ee-form-step';
	}



	/**
	 * @return string
	 */
	public function defaultFormStep() {
		return $this->default_form_step;
	}



	/**
	 * @param $default_form_step
	 * @throws InvalidDataTypeException
	 */
	protected function setDefaultFormStep( $default_form_step ) {
		if ( ! is_string( $default_form_step ) ) {
			throw new InvalidDataTypeException( '$default_form_step', $default_form_step, 'string' );
		}
		$this->default_form_step = $default_form_step;
	}



	/**
	 * @return void
	 * @throws InvalidDataTypeException
	 * @throws BaseException
	 */
	protected function setCurrentStepFromRequest() {
		$current_step_slug = $this->request()->get( $this->formStepUrlKey(), $this->defaultFormStep() );
		if ( ! $this->form_steps->setCurrent( $current_step_slug ) ) {
			throw new BaseException( 'Form Step could not be set' );
		}
	}



	/**
	 * @return SequentialStepFormInterface
	 * @throws \EventEspresso\Core\Exceptions\InvalidDataTypeException
	 * @throws \EventEspresso\Core\Exceptions\BaseException
	 */
	public function getCurrentStep() {
		return $this->form_steps->current();
	}



	/**
	 * @return string
	 * @throws \EventEspresso\Core\Exceptions\InvalidDataTypeException
	 * @throws \EventEspresso\Core\Exceptions\BaseException
	 */
	public function formAction() {
		if ( ! is_string( $this->form_action ) || empty( $this->form_action ) ) {
			$this->form_action = $this->baseUrl();
		}
		return $this->form_action;
	}



	/**
	 * @param string $form_action
	 * @throws InvalidDataTypeException
	 */
	public function setFormAction( $form_action ) {
		if ( ! is_string( $form_action ) ) {
			throw new InvalidDataTypeException( '$form_action', $form_action, 'string' );
		}
		$this->form_action = $form_action;
	}



	/**
	 * @param array $form_action_args
	 * @throws InvalidDataTypeException
	 * @throws \EventEspresso\Core\Exceptions\BaseException
	 */
	public function addFormActionArgs( $form_action_args = array() ) {
		if ( ! is_array( $form_action_args ) ) {
			throw new InvalidDataTypeException( '$form_action_args', $form_action_args, 'array' );
		}
		$form_action_args = ! empty( $form_action_args )
			? $form_action_args
			: array( $this->formStepUrlKey() => $this->form_steps->current()->slug() );
		$this->getCurrentStep()->setFormAction(
			add_query_arg( $form_action_args, $this->formAction() )
		);
		$this->form_action = $this->getCurrentStep()->formAction();
	}



	/**
	 * @return string
	 */
	public function formConfig() {
		return $this->form_config;
	}



	/**
	 * @param string $form_config
	 */
	public function setFormConfig( $form_config ) {
		$this->form_config = $form_config;
	}



	/**
	 * @return string
	 */
	public function progressStepStyle() {
		return $this->progress_step_style;
	}



	/**
	 * @param string $progress_step_style
	 */
	public function setProgressStepStyle( $progress_step_style ) {
		$this->progress_step_style = $progress_step_style;
	}


	/**
	 * @return EE_Request
	 */
	public function request() {
		return $this->request;
	}



	/**
	 * @return Collection|null
	 * @throws InvalidInterfaceException
	 */
	protected function getProgressStepsCollection() {
		static $collection = null;
		if ( ! $collection instanceof ProgressStepCollection ) {
			$collection = new ProgressStepCollection();
		}
		return $collection;
	}



	/**
	 * @param Collection $progress_steps_collection
	 * @return ProgressStepManager
	 * @throws \EventEspresso\Core\Exceptions\BaseException
	 * @throws InvalidEntityException
	 * @throws InvalidDataTypeException
	 * @throws InvalidClassException
	 * @throws InvalidInterfaceException
	 */
	protected function GenerateProgressSteps( Collection $progress_steps_collection ) {
		$current_step = $this->getCurrentStep();
		/** @var SequentialStepForm $form_step */
		foreach ( $this->form_steps as $form_step ) {
			// is this step active ?
			if ( ! $form_step->initialize() ) {
				continue;
			}
			$progress_steps_collection->add(
				new ProgressStep(
					$form_step->order(),
					$form_step->slug(),
					$form_step->slug(),
					$form_step->formName()
				),
				$form_step->slug()
			);
		}
		// set collection pointer back to current step
		$this->form_steps->setCurrentUsingObject( $current_step );
		return new ProgressStepManager(
			$this->progressStepStyle(),
			$this->defaultFormStep(),
			$this->formStepUrlKey(),
			$progress_steps_collection
		);
	}



	/**
	 * @throws BaseException
	 * @throws InvalidClassException
	 * @throws InvalidDataTypeException
	 * @throws InvalidEntityException
	 * @throws InvalidIdentifierException
	 * @throws InvalidInterfaceException
	 * @throws InvalidArgumentException
	 */
	public function buildForm() {
		$this->buildCurrentStepFormForDisplay();
	}



	/**
	 * @param array $form_data
	 * @throws BaseException
	 * @throws InvalidClassException
	 * @throws InvalidDataTypeException
	 * @throws InvalidEntityException
	 * @throws InvalidIdentifierException
	 * @throws InvalidInterfaceException
	 * @throws InvalidArgumentException
	 */
	public function processForm( $form_data = array() ) {
		$this->buildCurrentStepFormForProcessing();
		$this->processCurrentStepForm( $form_data );
	}



	/**
	 * @throws InvalidClassException
	 * @throws InvalidDataTypeException
	 * @throws InvalidEntityException
	 * @throws InvalidInterfaceException
	 * @throws InvalidIdentifierException
	 * @throws BaseException
	 * @throws InvalidArgumentException
	 */
	public function buildCurrentStepFormForDisplay() {
		$form_step = $this->buildCurrentStepForm();
		// no displayable content ? then skip straight to processing
		if ( ! $form_step->displayable() ) {
			$this->addFormActionArgs();
			$form_step->setFormAction( $this->formAction() );
			wp_safe_redirect( $form_step->formAction() );
		}
	}



	/**
	 * @throws InvalidClassException
	 * @throws InvalidDataTypeException
	 * @throws InvalidEntityException
	 * @throws InvalidInterfaceException
	 * @throws InvalidIdentifierException
	 * @throws BaseException
	 * @throws InvalidArgumentException
	 */
	public function buildCurrentStepFormForProcessing() {
		$this->buildCurrentStepForm( false );
	}



	/**
	 * @param bool $for_display
	 * @return \EventEspresso\core\libraries\form_sections\SequentialStepFormInterface
	 * @throws BaseException
	 * @throws InvalidIdentifierException
	 * @throws InvalidClassException
	 * @throws InvalidDataTypeException
	 * @throws InvalidEntityException
	 * @throws InvalidInterfaceException
	 * @throws InvalidArgumentException
	 */
	private function buildCurrentStepForm( $for_display = true ) {
		$this->form_steps = $this->getFormStepsCollection();
		$this->setCurrentStepFromRequest();
		$form_step = $this->getCurrentStep();
		if ( $for_display && $form_step->displayable() ) {
			$this->progress_step_manager = $this->GenerateProgressSteps(
				$this->getProgressStepsCollection()
			);
			$this->progress_step_manager->setCurrentStep(
				$form_step->slug()
			);
			$this->progress_step_manager->enqueueStylesAndScripts();
			$this->addFormActionArgs();
			$form_step->setFormAction( $this->formAction() );

		} else {
			$form_step->setRedirectUrl( $this->baseUrl() );
			$form_step->addRedirectArgs(
				array( $this->formStepUrlKey() => $this->form_steps->current()->slug() )
			);
		}
		$form_step->generate();
		if ( $for_display ) {
			$form_step->enqueueStylesAndScripts();
		}
		return $form_step;
	}



	/**
	 * @param bool $return_as_string
	 * @return string
	 */
	public function displayProgressSteps( $return_as_string = true ) {
		if ( $return_as_string ) {
			return $this->progress_step_manager->displaySteps();
		}
		echo $this->progress_step_manager->displaySteps();
		return '';
	}



	/**
	 * @param array $form_data
	 * @return bool
	 * @throws \EventEspresso\Core\Exceptions\BaseException
	 * @throws InvalidArgumentException
	 * @throws InvalidDataTypeException
	 */
	public function processCurrentStepForm( $form_data = array() ) {
		try {
			if ( $this->getCurrentStep()->process( $form_data ) ) {
				$current_step = $this->getCurrentStep();
				// otay, we are advancing to the next step
				$this->form_steps->next();
				// but only if it exists
				if ( $this->form_steps->valid() ) {
					$current_step->addRedirectArgs(
						array( $this->formStepUrlKey() => $this->getCurrentStep()->slug() )
					);
				}
				wp_safe_redirect( $current_step->redirectUrl() );
				exit();
			}
		} catch ( Exception $e ) {
			EE_Error::add_error( $e->getMessage(), __FILE__, __FUNCTION__, __LINE__ );
		}
		EE_Error::get_notices( false, true );
		/** @var SequentialStepFormInterface $previous_step */
		$previous_step = $this->form_steps->previous();
		$previous_step->setRedirectUrl( $this->baseUrl() );
		$previous_step->addRedirectArgs(
			array( $this->formStepUrlKey() => $previous_step->slug() )
		);
		wp_safe_redirect( $previous_step->redirectUrl() );
		exit();
	}



	/**
	 * @param bool $return_as_string
	 * @return string
	 * @throws \EventEspresso\Core\Exceptions\InvalidDataTypeException
	 * @throws \EventEspresso\Core\Exceptions\BaseException
	 */
	public function displayCurrentStepForm( $return_as_string = true ) {
		if ( $return_as_string ) {
			return $this->getCurrentStep()->display();
		}
		echo $this->getCurrentStep()->display();
		return '';
	}


}
// End of file SequentialStepFormManager.php
// Location: /SequentialStepFormManager.php