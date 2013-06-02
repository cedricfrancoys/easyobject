<?php

namespace dietwatch {

	class Food extends \core\Object {

		public static function getColumns() {
			return array(
				'Long_Desc'			=> array('label' => 'Description', 'type' => 'string', 'multilang'=>true),
				'FdGrp_Cd' 			=> array('label' => 'Group', 'type' => 'many2one', 'foreign_object' => 'dietwatch\Group'),
				'values_ids'		=> array('label' => 'Nutrients', 'type' => 'one2many', 'foreign_object' => 'dietwatch\Value', 'foreign_field' => 'NDB_No')
			);
		}

	}
}