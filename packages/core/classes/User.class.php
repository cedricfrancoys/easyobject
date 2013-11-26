<?php
namespace core {

	class User extends \core\Object {

		public static function getColumns() {
			return array(
				'firstname'		=> array('type' => 'string'),
				'lastname'		=> array('type' => 'string'),
				'start'			=> array('type' => 'string'),				
				'birthdate'		=> array('type' => 'date'),
				'login'			=> array('type' => 'string', 'label' => 'Username', 'help' => 'Your username is your email address.'),
				'password'		=> array('type' => 'string', 'label' => 'Password'),
				'language'		=> array('type' => 'string'),
				'validated'		=> array('type' => 'boolean'),
				'groups_ids'	=> array('type' => 'many2many', 'foreign_object' => 'core\Group', 'foreign_field' => 'users_ids', 'rel_table' => 'core_rel_group_user', 'rel_foreign_key' => 'group_id', 'rel_local_key' => 'user_id'),
			);
		}

		public static function getConstraints() {
			return array(
				'login'			=> array(
									'error_message_id' => 'invalid_login',
									'function' => function ($login) {
											return (bool) (preg_match('/^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/', $login, $matches));
										}
									),
				'password'		=> array(
									'error_message_id' => 'invalid_password',
									'function' => function ($password) {
											return (bool) (preg_match('/^[0-9|a-z]{32}$/', $password, $matches));
										}
									),
			);
		}
	}
}