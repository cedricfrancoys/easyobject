<?php

namespace dietwatch {

	class Group extends \core\Object {

		public static function getColumns() {
			return array(
				'FdGrp_Desc'		=> array('label' => 'Description', 'type' => 'string'),
				'food_ids'			=> array('type' => 'one2many', 'foreign_object' => 'dietwatch\Food', 'foreign_field' => 'FdGrp_Cd')
			);
		}

	}
}