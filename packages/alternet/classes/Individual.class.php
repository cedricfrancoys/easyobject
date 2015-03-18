<?php

namespace alternet {

	class Individual extends \core\Object {

		public static function getColumns() {
			return array(
				'firstname'			=> array('type' => 'string'),
				'lastname'			=> array('type' => 'string'),
				'description'		=> array('type' => 'short_text', 'help' => "courte présentation (vision, formation, profession, ouvrages, ...)"),
				'url'				=> array('type' => 'string'),
				'email'				=> array('type' => 'string'),
				'address'			=> array('type' => 'short_text'),
				'zip'				=> array('type' => 'string', 'help' => "code postal officiel"),
				'country'			=> array('type' => 'selection', 'selection'	=> array('BELGIQUE' => 'Belgique', 'CANADA' => 'Canada', 'ESPAGNE' => 'Espagne', 'FRANCE' => 'France', 'LUXEMBOURG' => 'Luxembourg', 'SUISSE' => 'Suisse')),
				'associations_ids'	=> array('type' => 'many2many', 'label' => 'Associations', 'foreign_object' => 'alternet\Association', 'foreign_field' => 'individuals_ids', 'rel_table' => 'alternet_rel_association_individual', 'rel_foreign_key' => 'association_id', 'rel_local_key' => 'individual_id'),
				'labels_ids'		=> array('type' => 'many2many', 'label' => 'Labels', 'foreign_object' => 'alternet\Label', 'foreign_field' => 'individuals_ids', 'rel_table' => 'alternet_rel_individual_label', 'rel_foreign_key' => 'label_id', 'rel_local_key' => 'individual_id'),
			);
		}

	}
}