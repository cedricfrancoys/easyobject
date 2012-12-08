<?php

namespace alternet {

	class Association extends \core\Object {

		public static function getColumns() {
			return array(
				'name'				=> array('type' => 'string', 'help' => "D�nomination compl�te, avec raison sociale le cas �ch�ant"),
				'description'		=> array('type' => 'short_text', 'help' => "courte pr�sentation (mission, objectifs, moyens, public, vision, ...)"),
				'url'				=> array('type' => 'string'),
				'email'				=> array('type' => 'string'),
				'address'			=> array('type' => 'short_text'),
				'zip'				=> array('type' => 'string', 'help' => "code postal officiel"),
				'country'			=> array('type' => 'selection', 'selection'	=> array('BELGIQUE' => 'Belgique', 'CANADA' => 'Canada', 'ESPAGNE' => 'Espagne', 'FRANCE' => 'France', 'LUXEMBOURG' => 'Luxembourg', 'SUISSE' => 'Suisse')),
				'individuals_ids'	=> array('type' => 'many2many', 'label' =>  'Individuals', 'foreign_object' => 'alternet\Individual', 'foreign_field' => 'associations_ids', 'rel_table' => 'alternet_rel_association_individual', 'rel_foreign_key' => 'individual_id', 'rel_local_key' => 'association_id'),
				'labels_ids'		=> array('type' => 'many2many', 'label' =>  'Labels', 'foreign_object' => 'alternet\Label', 'foreign_field' => 'associations_ids', 'rel_table' => 'alternet_rel_association_label', 'rel_foreign_key' => 'label_id', 'rel_local_key' => 'association_id'),
			);
		}

	}
}