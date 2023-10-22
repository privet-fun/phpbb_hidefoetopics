<?php

/**
 *
 * Hide Foe Topics extension
 *
 * @copyright (c) 2023, privet.fun, https://privet.fun
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace privet\hidefoetopics\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener
 */
class listener implements EventSubscriberInterface
{
	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\db\driver\driver */
	protected $db;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\language\language */
	protected $language;
	/**
	 * Constructor
	 *
	 * @param \phpbb\request\request				$request
	 * @param \phpbb\template\template			$template
	 * @param \phpbb\user						$user
	 * @param \phpbb\db\driver\driver			$db
	 * @param \phpbb\config\config				$config
	 */
	public function __construct(
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\config\config $config,
		\phpbb\language\language $language,
	) {
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->db = $db;
		$this->config = $config;
		$this->language = $language;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup_after'					=>	'user_setup_after',
			'core.ucp_prefs_personal_data'			=>	'ucp_prefs_personal_data',
			'core.ucp_prefs_personal_update_data'	=>	'ucp_prefs_personal_update_data',
			'core.viewforum_get_topic_ids_data'	 	=>	'viewforum_get_topic_ids_data',
			'core.get_unread_topics_modify_sql'		=>	'get_unread_topics_modify_sql',
		);
	}

	/* Add the lang vars to the users language
	*
	* @param $event			event object
	* @param return null
	* @access public
	*/
	public function user_setup_after($event)
	{
		$this->language->add_lang('common', 'privet/hidefoetopics');
	}

	/**
	 * Get user's option and display it in UCP Prefs View page
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function ucp_prefs_personal_data($event)
	{
		$event['data'] = array_merge($event['data'], [
			'privet_hidefoetopics'	=> $this->request->variable('privet_hidefoetopics', (int) $this->user->data['user_privet_hidefoetopics']),
		]);

		if (!$event['submit']) {
			$this->template->assign_vars([
				'S_UCP_PRIVET_HIDEFOETOPICS' => $event['data']['privet_hidefoetopics']
			]);
		}
	}

	/**
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function ucp_prefs_personal_update_data($event)
	{
		$event['sql_ary'] = array_merge($event['sql_ary'], [
			'user_privet_hidefoetopics' => $event['data']['privet_hidefoetopics'],
		]);
	}

	public function viewforum_get_topic_ids_data($event)
	{
		// echo var_dump($event['sql_ary']);

		if ($this->user->data['user_privet_hidefoetopics']) {
			$sql_ary = $event['sql_ary'];
			$sql_ary['LEFT_JOIN'][] = array(
				'FROM'	=> array(ZEBRA_TABLE => 'z'),
				'ON'	=> 'z.user_id = ' . $this->user->data['user_id'] . ' AND z.zebra_id = t.topic_poster'
			);
			$sql_ary['WHERE'] .= ' AND (z.foe IS NULL OR z.foe = 0)';
			$event['sql_ary'] = $sql_ary;
		}

		// echo var_dump($event['sql_ary']);
	}

	public function get_unread_topics_modify_sql($event)
	{
		// echo var_dump($event['sql_array']);

		if ($this->user->data['user_privet_hidefoetopics']) {
			$sql_ary = $event['sql_array'];
			$sql_ary['LEFT_JOIN'][] = array(
				'FROM'	=> array(ZEBRA_TABLE => 'z'),
				'ON'	=> 'z.user_id = ' . $this->user->data['user_id'] . ' AND z.zebra_id = t.topic_poster'
			);
			$sql_ary['WHERE'] = '(z.foe IS NULL OR z.foe = 0) AND ' . $sql_ary['WHERE'];
			$event['sql_array'] = $sql_ary;
		}

		// echo var_dump($event['sql_extra']);
	}	
}
