<?php
/**
 *
 * Hide Foe Topics extension
 *
 * @copyright (c) 2023, privet.fun, https://privet.fun
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace privet\hidefoetopics\migrations\v1x;

class release_1_0_0_schema extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v320\v320');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('privet_hidefoetopics_version', '1.0.0')),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('privet_hidefoetopics_version')),
		);
	}

	public function update_schema()
	{
		return array(
			'add_columns'	=> array(
				$this->table_prefix . 'users'	=> array(
					'user_privet_hidefoetopics'	=> array('BOOL', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns'	=> array(
				$this->table_prefix . 'users'	=> array(
					'user_privet_hidefoetopics',
				),
			),
		);
	}
}
