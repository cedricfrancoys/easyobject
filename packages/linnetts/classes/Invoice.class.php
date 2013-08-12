<?php

namespace linnetts {

	class Invoice extends \core\Object {

		public static function getColumns() {
			return array(
				'code'			=> array('type' => 'string'),
				'date'			=> array('type' => 'date'),
				'description'	=> array('type' => 'text'),
				'price'			=> array('type' => 'float'),
				'customer_id'	=> array('type' => 'many2one', 'foreign_object' => 'linnetts\Customer'),
				'jobs_ids'		=> array('type' => 'one2many', 'foreign_object' => 'linnetts\Job', 'foreign_field' => 'invoice_id'),				
				'customer_name'	=> array('type' => 'related', 'result_type' => 'string', 'foreign_object' => 'linnetts\Customer', 'path' => array('customer_id', 'name'))
			);
		}

	}
}