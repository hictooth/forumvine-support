<?php
/**
*
* @package Topic description
* @copyright (c) 2018 Rich McGirr (RMcGirr83)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace rmcgirr83\topicdescription\migrations;

class v2 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\rmcgirr83\topicdescription\migrations\v1');
	}

	public function update_schema()
	{
		return array(
			'change_columns'    => array(
				$this->table_prefix . 'topics'        => array(
					'topic_desc'	=> array('TEXT_UNI', null),
				),
				$this->table_prefix . 'users'        => array(
					'user_number'	=> array('UINT', 0),
					'import_status'	=> array('UINT', 0),
					'tt_username'	=> array('TEXT_UNI', null),
					'tt_password'	=> array('TEXT_UNI', null),
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
				),
			),
		);
	}
}