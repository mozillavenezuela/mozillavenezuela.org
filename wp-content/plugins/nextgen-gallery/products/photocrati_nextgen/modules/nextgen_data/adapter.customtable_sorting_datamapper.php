<?php

/**
 * Modifies a custom table datamapper sorting to use natural sorting
 */
class A_CustomTable_Sorting_DataMapper extends Mixin
{
#	function initialize()
#	{
#		$this->object->get_wrapped_instance()->add_post_hook(
#			'order_by',
#			'Natural Sorting',
#			'Hook_CustomTable_Natural_Sorting'
#		);
#	}
	
	function order_by($order_by, $direction='ASC')
	{
		// We treat the rand() function as an exception
		if (!preg_match("/rand\(\s*\)/", $order_by)) {
			$order_by_col	= $this->object->_clean_column($order_by);

			// If the order by clause is a column, then it should be backticked
			if ($this->object->has_column($order_by_col)) $order_by_col = "ABS(`{$order_by_col}`)";

			$direction	= $this->object->_clean_column($direction);
			$order		= "{$order_by_col} {$direction}";

			$this->object->_order_clauses[] = $order;
		}
		
		return $this->call_parent('order_by', $order_by, $direction);
	}
}

class Hook_CustomTable_Natural_Sorting extends Hook
{
	function order_by($order_by, $direction='ASC')
	{
	}
}

