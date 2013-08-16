<?php
/**
 * Provides validation for datamapper entities within an MVC controller
 */
class A_MVC_Validation extends Mixin
{
	function show_errors_for($entity, $return=FALSE)
	{
		$retval = '';

		if ($entity->is_invalid()) {
			$retval = $this->object->render_partial('photocrati-nextgen_admin#entity_errors', array(
				'entity'	=>	$entity
			), $return);
		}

		return $retval;
	}

	function show_success_for($entity, $message, $return=FALSE)
	{
		$retval = '';

		if ($entity->is_valid()) {
			$retval = $this->object->render_partial('photocrati-nextgen_admin#entity_saved', array(
				'entity'	=>	$entity,
				'message'	=>	$message
			));
		}

		return $retval;
	}
}