<?php
/**
*
* @package Topic description
* @copyright (c) 2016 RMcGirr83
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*
*/

namespace rmcgirr83\topicdescription\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	private $topic_desc = '';

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/**
	* Constructor
	*
	* @param \phpbb\auth\auth					$auth				Auth object
	* @param \phpbb\request\request				$request			Request object
	* @param \phpbb\template\template           $template       	Template object
	* @param \phpbb\user                        $user           	User object
	* @access public
	*/
	public function __construct(
			\phpbb\auth\auth $auth,
			\phpbb\request\request $request,
			\phpbb\template\template $template,
			\phpbb\user $user)
	{
		$this->auth = $auth;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
	}

	/**
	* Assign functions defined in this class to event listeners in the core
	*
	* @return array
	* @static
	* @access public
	*/
	static public function getSubscribedEvents()
	{
		return array(
			'core.permissions'						=> 'add_permission',
			'core.posting_modify_template_vars'		=> 'topic_data_topic_desc',
			'core.posting_modify_submission_errors'		=> 'topic_desc_add_to_post_data',
			'core.posting_modify_submit_post_before'		=> 'topic_desc_add',
			'core.posting_modify_message_text'		=> 'modify_message_text',
			'core.submit_post_modify_sql_data'		=> 'submit_post_modify_sql_data',
			'core.viewtopic_modify_page_title'		=> 'topic_desc_add_viewtopic',
			'core.viewforum_modify_topicrow'		=> 'modify_topicrow',
			'core.search_modify_tpl_ary'			=> 'search_modify_tpl_ary',
			'core.mcp_view_forum_modify_topicrow'	=> 'modify_topicrow',
			'core.viewtopic_cache_user_data'		=> 'cache_user_data',
			'core.viewtopic_modify_post_row'		=> 'modify_post_row',
			'core.memberlist_view_profile'			=> 'view_profile'
		);
	}

	/**
	* Add administrative permissions to manage forums
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function add_permission($event)
	{
		$permissions = $event['permissions'];
		$permissions['f_topic_desc'] = array('lang' => 'ACL_F_TOPIC_DESC', 'cat' => 'post');
		$event['permissions'] = $permissions;
	}

	public function topic_data_topic_desc($event)
	{
		$mode = $event['mode'];
		$post_data = $event['post_data'];
		$page_data = $event['page_data'];
		$post_data['topic_desc'] = (!empty($post_data['topic_desc'])) ? $post_data['topic_desc'] : '';
		if ($this->auth->acl_get('f_topic_desc', $event['forum_id']) && ($mode == 'post' || ($mode == 'edit' && $post_data['topic_first_post_id'] == $post_data['post_id'])))
		{
			$this->user->add_lang_ext('rmcgirr83/topicdescription', 'common');
			$page_data['TOPIC_DESC'] = $this->request->variable('topic_desc', $post_data['topic_desc'], true);
			$page_data['S_DESC_TOPIC'] = true;
		}

		$event['page_data']	= $page_data;
	}

	public function topic_desc_add_to_post_data($event)
	{
		if ($this->auth->acl_get('f_topic_desc', $event['forum_id']))
		{
			$event['post_data'] = array_merge($event['post_data'], array(
				'topic_desc'	=> $this->request->variable('topic_desc', '', true),
			));
		}
	}

	public function topic_desc_add($event)
	{
		$event['data'] = array_merge($event['data'], array(
			'topic_desc'	=> $event['post_data']['topic_desc'],
		));
	}

	public function modify_message_text($event)
	{
		$event['post_data'] = array_merge($event['post_data'], array(
			'topic_desc'	=> $this->request->variable('topic_desc', $event['post_data']['topic_desc'], true),
		));
	}

	public function submit_post_modify_sql_data($event)
	{
		$mode = $event['post_mode'];
		$topic_desc = $event['data']['topic_desc'];
		$data_sql = $event['sql_data'];
		if (in_array($mode, array('post', 'edit_topic', 'edit_first_post')))
		{
			$data_sql[TOPICS_TABLE]['sql']['topic_desc'] = $topic_desc;
		}
		$event['sql_data'] = $data_sql;
	}

	public function topic_desc_add_viewtopic($event)
	{
		$topic_data = $event['topic_data'];
		$this->template->assign_var('TOPIC_DESC',censor_text($topic_data['topic_desc']));
	}

	public function modify_topicrow($event)
	{
		$row = $event['row'];
		if (!empty($row['topic_desc']))
		{
			$topic_row = $event['topic_row'];
			$topic_row['TOPIC_DESC'] = censor_text($row['topic_desc']);
			$event['topic_row'] = $topic_row;
		}
	}

	public function search_modify_tpl_ary($event)
	{
		$row = $event['row'];
		if ($event['show_results'] == 'topics' && !empty($row['topic_desc']))
		{
			$tpl_array = $event['tpl_ary'];
			$tpl_array['TOPIC_DESC'] = censor_text($row['topic_desc']);
			$event['tpl_ary'] = $tpl_array;
		}
	}

	public function cache_user_data($event) {
		global $db;
		global $phpbb_container;

		// get the user number
		$row_data = $event['row'];
		$user_number = $row_data['user_number'];

		// get the group name of this user
		$group_name = getGroupName($row_data['group_id']);

		// add these into the user data
		$event['user_cache_data'] = array_merge($event['user_cache_data'], array(
			'number' => $user_number,
			'group_name' => $group_name,
		));
	}

	public function modify_post_row($event) {
		$user_poster_data = $event['user_poster_data'];
		$post_row = $event['post_row'];
		$cp_row = $event['cp_row'];

		// add in user number
		$event['post_row'] = array_merge($event['post_row'], array(
			'POSTER_NUMBER' => $user_poster_data['number'],
			'GROUP_NAME' => $user_poster_data['group_name'],
		));

		// add in title, if applicable
		if ($cp_row['row'] && array_key_exists('S_PROFILE_PHPBB_TITLE', $cp_row['row'])) {
			$event['post_row'] = array_merge($event['post_row'], array(
				'MEMBER_TITLE' => $cp_row['row']['PROFILE_PHPBB_TITLE_VALUE']
			));
		}

		//print_r($user_poster_data);
	}

	public function view_profile($event) {
		$custom = array();

		// member number
		$custom_field = array('HIDDEN_FIELD' => 1, 'PROFILE_FIELD_NAME' => 'MEMBER_NUMBER', 'PROFILE_FIELD_VALUE' => $event['member']['user_number']);
		array_push($custom, $custom_field);

		// group name
		$group_name = getGroupName($event['member']['group_id']);
		$custom_field = array('HIDDEN_FIELD' => 1, 'PROFILE_FIELD_NAME' => 'GROUP_NAME', 'PROFILE_FIELD_VALUE' => $group_name);
		array_push($custom, $custom_field);

		// birthday
		if ($event['member']['user_birthday'] != '') {
			// yay, weird custom date formatting!
			$date = $event['member']['user_birthday'];
			$date = $date . "..";
		    $date = str_replace(" 0..", "0000", $date);
		    $date = str_replace("..", "", $date);
		    $date = str_replace(" ", "0", $date);

		    $date = strptime($date, '%d-%m-%Y');

		    $day = $date['tm_mday'];
		    $month = $date['tm_mon'] + 1;
		    $year = $date['tm_year'] + 1900;

		    $monthName = date('F', mktime(0, 0, 0, $month, 10));

		    if ($year == 0) {
		        // don't show year
		        $dateString = $monthName . " " . $day . ordinal_suffix($day);
		    } else {
		        // show year
		        $dateString = $monthName . " " . $day . ordinal_suffix($day) . ", " . $year;
		    }

			$custom_field = array('HIDDEN_FIELD' => 0, 'PROFILE_FIELD_NAME' => 'Birthday', 'PROFILE_FIELD_VALUE' => $dateString);
			array_push($custom, $custom_field);
		}

		$event['profile_fields'] = array_merge($event['profile_fields'], array('blockrow' => $custom));
	}
}


function ordinal_suffix($num) {
    $num = $num % 100; // protect against large numbers
    if ($num < 11 || $num > 13) {
         switch($num % 10){
            case 1: return 'st';
            case 2: return 'nd';
            case 3: return 'rd';
        }
    }
    return 'th';
}

function getGroupName($group_id) {
	global $db;
	global $phpbb_container;

	$sql = "SELECT * FROM " . GROUPS_TABLE . " WHERE group_id = " . $db->sql_escape($group_id);
	$result = $db->sql_query($sql);
	$group_row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	$group_helper = $phpbb_container->get('group_helper');
	$group_name = $group_helper->get_name($group_row['group_name']);
	return $group_name;
}
