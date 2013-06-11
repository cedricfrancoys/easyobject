<?php

namespace alternet {

	class Label extends \core\Object {

		public static function getColumns() {
			return array(
				'name'				=> array('type' => 'string'),
				'associations_ids'	=> array('type' => 'many2many',
											'foreign_object'	=> 'alternet\Association',
											'foreign_field'		=> 'labels_ids',
											'rel_table'			=> 'alternet_rel_association_label',
											'rel_foreign_key'	=> 'association_id',
											'rel_local_key'		=> 'label_id'),
				'individuals_ids'	=> array('type' => 'many2many',
											'foreign_object'	=> 'alternet\Individual',
											'foreign_field'		=> 'labels_ids',
											'rel_table'			=> 'alternet_rel_individual_label',
											'rel_foreign_key'	=> 'individual_id',
											'rel_local_key'		=> 'label_id'),
				'initiatives_ids'	=> array('type' => 'many2many',
											'foreign_object'	=> 'alternet\Initiative',
											'foreign_field'		=> 'labels_ids',
											'rel_table'			=> 'alternet_rel_initiative_label',
											'rel_foreign_key'	=> 'initiative_id',
											'rel_local_key'		=> 'label_id'),

			);
		}
	}
}