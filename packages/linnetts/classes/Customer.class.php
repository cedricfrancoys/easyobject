<?php

namespace linnetts {

	class Customer extends \core\Object {

		public static function getColumns() {
			return array(
				'name'			=> array('type' => 'string'),
				'address'		=> array('type' => 'short_text'),				
				'vat'			=> array('type' => 'string'),
				'jobs_ids'		=> array('type' => 'one2many', 'foreign_object' => 'linnetts\Job', 'foreign_field' => 'customer_id'),
				'invoices_ids'	=> array('type' => 'one2many', 'foreign_object' => 'linnetts\Invoice', 'foreign_field' => 'customer_id'),
			);
		}

	}
}