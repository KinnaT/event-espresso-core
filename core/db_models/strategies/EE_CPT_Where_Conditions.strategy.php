<?php

/*
 * Strategy specifically for adding where conditions specific to CPT models.
 */
class EE_CPT_Where_Conditions extends EE_Default_Where_Conditions{

	protected $_post_type;
	protected $_meta_field;
	function __construct($post_type, $meta_field_to_chk){
		$this->_post_type = $post_type;
		$this->_meta_field = $meta_field_to_chk;
	}
	/**
	 * Gets the field with the specified column. Note, this function might not work
	 * properly if two fields refer to columns with the same name on different tables
	 * @param string $column column name
	 * @return EE_Model_Field_Base
	 */
	protected function _get_field_on_column($column){
		$all_fields = $this->_model->field_settings(true);
		foreach($all_fields as $field_name => $field_obj){
			if($column == $field_obj->get_table_column()){
				return $field_obj;
			}
		}
	}
	/**
	 * Gets the where default where conditions for a custom post type model
	 * @param string $model_relation_path. Eg, from Event to Payment, this should be "Registration.Transaction.Payment"
	 * @return array like EEM_Base::get_all's $query_params's index [0] (where conditions)
	 */
	function get_default_where_conditions($model_relation_chain = null){
		//make sure there's a period at the end of $model_relation_chain
		$model_relation_chain = $this->_ensure_model_relation_chain_ends_in_period( $model_relation_chain );

		$status_field = $this->_get_field_on_column('post_status');
		$status_field_name = $model_relation_chain.$status_field->get_name();
		return array_replace_recursive(
				$this->get_minimum_where_conditions( $model_relation_chain ),
				array(	$status_field_name => array('NOT IN',array('auto-draft','trash') ) )
		);
	}

	/**
	 * At a minimum, we pretty well ALWAYS want to include the post type where querying
	 * CPT models, otherwise we could get rows which aren't of this post type
	 * @param string $model_relation_chain
	 * @return array
	 */
	function get_minimum_where_conditions( $model_relation_chain = null ){
		$model_relation_chain = $this->_ensure_model_relation_chain_ends_in_period( $model_relation_chain );
		//find post_type field
		$post_type_field = $this->_get_field_on_column('post_type');

		$post_type_field_name = $model_relation_chain.$post_type_field->get_name();
		return array(
			$post_type_field_name => $this->_post_type,
		);
	}
}
