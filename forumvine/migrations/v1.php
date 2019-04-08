<?php
/**
*
* @package Topic description
* @copyright (c) 2016 Rich McGirr (RMcGirr83)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace hictooth\forumvine\migrations;

class v1 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\dev');
	}

	public function update_schema()
	{
		return array(
			'add_columns'	=> array(
				$this->table_prefix . 'topics'	=> array(
					'topic_desc'	=> array('VCHAR_UNI', ''),
				),
				$this->table_prefix . 'users'	=> array(
					'user_number'	=> array('UINT', 0),
					'import_status'	=> array('UINT', 0),
					'tt_username'	=> array('VCHAR_UNI', ''),
					'tt_password'	=> array('VCHAR_UNI', ''),
					'badges_binary'	=> array('UINT', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'topics'	=> array(
					'topic_desc',
				),
				$this->table_prefix . 'users'	=> array(
					'user_number',
					'import_status',
					'tt_username',
					'tt_password',
					'badges_binary'
				),
			),
		);
	}

	public function update_data()
	{
		return array(
			// Add permission
			array('permission.add', array('f_topic_desc', false)),
			// Set permissions,
			array('permission.permission_set',array('ROLE_FORUM_FULL','f_topic_desc','role')),
			array('permission.permission_set',array('ROLE_FORUM_STANDARD','f_topic_desc','role')),
			array('permission.permission_set',array('ROLE_FORUM_POLLS','f_topic_desc','role')),
		);
	}
}
