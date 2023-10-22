# Hide Foe Topics v1.0.0

Hide posts made by users on your "Foes" list.

Burger menu > `Unread posts` : "Foes" posts hidden  
Burger menu > `New posts`, `Unaswered topics` and `Active topics` : "Foes" posts *NOT* hidden, see [Notes](#notes)

# Table of Contents
1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Notes](#notes)
4. [Changelog](#changelog)
5. [License](#license)

## Requirements
* PHP >= 7.4
* phpBB >= 3.2

## Installation

Download [here](https://github.com/privet-fun/phpbb_hidefoetopics) and copy `/privet/hidefoetopics` to the `phpbb/ext` folder.

If you have a previous version of this extension installed, you will need to disable it and then enable it again after the new version has been copied over.

Go to `ACP` > `Customise` > `Manage extensions` and enable the "Hide Foe Topics" extension.

Finally, go to `ACP` > `User Control Panel` > `Board preferences` > `Edit global settings` and adjust `Hide foe-created topics` to your liking.

![](hidefoetopics.png)

## Notes

To hide "Foes" posts from burger menu > `New posts`, `Unaswered topics` and `Active topics` modify manually file `phpbb/search.php`, make sure to make backup copy.

After source line `#365` (version **3.3.x** source code shown)
```php
	extract($phpbb_dispatcher->trigger_event('core.search_modify_param_before', compact($vars)));
```

Insert / update code as shown below:

```php
	// privet.fun https://github.com/privet-fun/phpbb_hidefoetopics
	$foe_join_topics = '';
	$foe_join_posts = '';
	$for_where = '';

	if ($user->data['user_privet_hidefoetopics']) {
		$foe_join_topics = " LEFT JOIN " . ZEBRA_TABLE . " z ON z.user_id = " . $user->data['user_id'] . " AND z.zebra_id = t.topic_poster ";
		$foe_join_posts = " LEFT JOIN " . ZEBRA_TABLE . " z ON z.user_id = " . $user->data['user_id'] . " AND z.zebra_id = p.poster_id ";
		$for_where = ' (z.foe is NULL or z.foe =0) AND ';
	}

	// pre-made searches
	$sql = $field = $l_search_title = '';
	if ($search_id)
	{
		switch ($search_id)
		{
			// Oh holy Bob, bring us some activity...
			case 'active_topics':
				$l_search_title = $user->lang['SEARCH_ACTIVE_TOPICS'];
				$show_results = 'topics';
				$sort_key = 't';
				$sort_dir = 'd';
				$sort_days = $request->variable('st', 7);
				$sort_by_sql['t'] = 't.topic_last_post_time';

				gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);
				$s_sort_key = $s_sort_dir = '';

				$last_post_time_sql = ($sort_days) ? ' AND t.topic_last_post_time > ' . (time() - ($sort_days * 24 * 3600)) : '';

				// privet.fun https://github.com/privet-fun/phpbb_hidefoetopics
				$sql = 'SELECT t.topic_last_post_time, t.topic_id
					FROM ' . TOPICS_TABLE . " t 
						$foe_join_topics
					WHERE 
						$for_where 
						t.topic_moved_id = 0
						$last_post_time_sql
						AND " . $m_approve_topics_fid_sql . '
						' . ((count($ex_fid_ary)) ? ' AND ' . $db->sql_in_set('t.forum_id', $ex_fid_ary, true) : '') . '
					ORDER BY t.topic_last_post_time DESC';
				$field = 'topic_id';
			break;

			case 'unanswered':
				$l_search_title = $user->lang['SEARCH_UNANSWERED'];
				$show_results = $request->variable('sr', 'topics');
				$show_results = ($show_results == 'posts') ? 'posts' : 'topics';
				$sort_by_sql['t'] = ($show_results == 'posts') ? 'p.post_time' : 't.topic_last_post_time';
				$sort_by_sql['s'] = ($show_results == 'posts') ? 'p.post_subject' : 't.topic_title';
				$sql_sort = 'ORDER BY ' . $sort_by_sql[$sort_key] . (($sort_dir == 'a') ? ' ASC' : ' DESC');

				$sort_join = ($sort_key == 'f') ? FORUMS_TABLE . ' f, ' : '';
				$sql_sort = ($sort_key == 'f') ? ' AND f.forum_id = p.forum_id ' . $sql_sort : $sql_sort;

				if ($sort_days)
				{
					$last_post_time = 'AND p.post_time > ' . (time() - ($sort_days * 24 * 3600));
				}
				else
				{
					$last_post_time = '';
				}

				if ($sort_key == 'a')
				{
					$sort_join = USERS_TABLE . ' u, ';
					$sql_sort = ' AND u.user_id = p.poster_id ' . $sql_sort;
				}
				if ($show_results == 'posts')
				{
					// privet.fun https://github.com/privet-fun/phpbb_hidefoetopics
					$sql = "SELECT p.post_id
						FROM $sort_join" . POSTS_TABLE . ' p, ' . TOPICS_TABLE . " t
							$foe_join_topics
						WHERE 
							$for_where
							t.topic_posts_approved = 1
							AND p.topic_id = t.topic_id
							$last_post_time
							AND $m_approve_posts_fid_sql
							" . ((count($ex_fid_ary)) ? ' AND ' . $db->sql_in_set('p.forum_id', $ex_fid_ary, true) : '') . "
							$sql_sort";
					$field = 'post_id';
				}
				else
				{
					// privet.fun https://github.com/privet-fun/phpbb_hidefoetopics
					$sql = 'SELECT DISTINCT ' . $sort_by_sql[$sort_key] . ", p.topic_id
						FROM $sort_join" . POSTS_TABLE . ' p, ' . TOPICS_TABLE . " t
							$foe_join_topics
						WHERE 
							$for_where
							t.topic_posts_approved = 1
							AND t.topic_moved_id = 0
							AND p.topic_id = t.topic_id
							$last_post_time
							AND $m_approve_topics_fid_sql
							" . ((count($ex_fid_ary)) ? ' AND ' . $db->sql_in_set('p.forum_id', $ex_fid_ary, true) : '') . "
						$sql_sort";
					$field = 'topic_id';
				}
			break;

			case 'unreadposts':
				$l_search_title = $user->lang['SEARCH_UNREAD'];
				// force sorting
				$show_results = 'topics';
				$sort_key = 't';
				$sort_by_sql['t'] = 't.topic_last_post_time';
				$sql_sort = 'ORDER BY ' . $sort_by_sql[$sort_key] . (($sort_dir == 'a') ? ' ASC' : ' DESC');

				$sql_where = 'AND t.topic_moved_id = 0
					AND ' . $m_approve_topics_fid_sql . '
					' . ((count($ex_fid_ary)) ? 'AND ' . $db->sql_in_set('t.forum_id', $ex_fid_ary, true) : '');

				gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);
				$s_sort_key = $s_sort_dir = $u_sort_param = $s_limit_days = '';

				$template->assign_var('U_MARK_ALL_READ', ($user->data['is_registered'] || $config['load_anon_lastread']) ? append_sid("{$phpbb_root_path}index.$phpEx", 'hash=' . generate_link_hash('global') . '&amp;mark=forums&amp;mark_time=' . time()) : '');
			break;

			case 'newposts':
				$l_search_title = $user->lang['SEARCH_NEW'];
				// force sorting
				$show_results = ($request->variable('sr', 'topics') == 'posts') ? 'posts' : 'topics';
				$sort_key = 't';
				$sort_dir = 'd';
				$sort_by_sql['t'] = ($show_results == 'posts') ? 'p.post_time' : 't.topic_last_post_time';
				$sql_sort = 'ORDER BY ' . $sort_by_sql[$sort_key] . (($sort_dir == 'a') ? ' ASC' : ' DESC');

				gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);
				$s_sort_key = $s_sort_dir = $u_sort_param = $s_limit_days = '';

				if ($show_results == 'posts')
				{
					// privet.fun https://github.com/privet-fun/phpbb_hidefoetopics
					$sql = 'SELECT p.post_id FROM ' . POSTS_TABLE . " p
							$foe_join_posts 
						WHERE 
							$for_where 
							p.post_time > " . $user->data['user_lastvisit'] . "
							AND " . $m_approve_posts_fid_sql . "
							" . ((count($ex_fid_ary)) ? ' AND ' . $db->sql_in_set('p.forum_id', $ex_fid_ary, true) : '') . "
						$sql_sort";
					$field = 'post_id';
				}
				else
				{
					// privet.fun https://github.com/privet-fun/phpbb_hidefoetopics
					$sql = 'SELECT t.topic_id
						FROM ' . TOPICS_TABLE . " t
							$foe_join_topics
						WHERE 
							$for_where
							t.topic_last_post_time > " . $user->data['user_lastvisit'] . "
							AND t.topic_moved_id = 0
							AND " . $m_approve_topics_fid_sql . "
							" . ((count($ex_fid_ary)) ? 'AND ' . $db->sql_in_set('t.forum_id', $ex_fid_ary, true) : '') . "
						$sql_sort";

          $field = 'topic_id';
				}
			break;

			case 'egosearch':
				$l_search_title = $user->lang['SEARCH_SELF'];
			break;
		}

		$template->assign_block_vars('navlinks', array(
			'BREADCRUMB_NAME'	=> $l_search_title,
			'U_BREADCRUMB'		=> append_sid("{$phpbb_root_path}search.$phpEx", "search_id=$search_id"),
		));
	}
```

## Support and Suggestions

This extension is currently actively developed. For communication, please use [our GitHub Issues](https://github.com/privet-fun/phpbb_hidefoetopics/issues).

## Changelog

* Version 1.0.0 - October 21, 2023
  - Public release

## License

Licensed under [GPLv2](hidefoetopics/license.txt).