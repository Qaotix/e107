<?php

if(!defined('e107_INIT'))
{
	exit;
}

// v2.x Standard 
class forum_rss // plugin-folder + '_rss'
{
	private $rssQuery;

	/**
	 * Admin RSS Configuration 
	 */		
	function config() 
	{
		$config = array();
	

		$config[] = array(
			'name' => "Forum / threads",
			'url' => '6',
			'topic_id' => '',
			'path' => 'forum|threads',
			'text' => 'this is the rss feed for the forum_threads entries',
			'class' => '1',
			'limit' => '9',
		);

		//forum threads (new url)
		$config[] = array(
			'name' => "Forum / threads",
			'url' => 'forumthreads',
			'topic_id' => '',
		//	'path' => 'forum|threads',
			'text' => 'this is the rss feed for the forum_threads entries',
			'class' => '0',
			'limit' => '9',
		);

		//forum posts (old url)
		$config[] = array(
			'name' => "Forum / posts",
			'url' => '7',
			'topic_id' => '',
		//	'path' => 'forum|posts',
			'text' => 'this is the rss feed for the forum_posts entries',
			'class' => '1',
			'limit' => '9',
		);

		//forum posts (new url)
		$config[] = array(
			'name' => "Forum / posts",
			'url' => 'forumposts',
			'topic_id' => '',
		//	'path' => 'forum|posts',
			'text' => 'this is the rss feed for the forum_posts entries',
			'class' => '0',
			'limit' => '9',
		);

		//forum topic (old url)
		$config[] = array(
			'name' => "Forum / topic",
			'url' => '8',
			'topic_id' => '*',
		//	'path' => 'forum|topic',
			'text' => 'this is the rss feed for the forum_topic entries',
			'class' => '1',
			'limit' => '9',
		);

		//forum topic (new url)
		$config[] = array(
			'name' => "Forum / topic",
			'url' => 'forumtopic',
			'topic_id' => '*',
		//	'path' => 'forum|topic',
			'text' => 'this is the rss feed for the forum_topic entries',
			'class' => '0',
			'limit' => '9',
		);

		//forum name (old url)
		$config[] = array(
			'name' => "Forum / name",
			'url' => '11',
			'topic_id' => '*',
		//	'path' => 'forum|name',
			'text' => 'this is the rss feed for the forum_name entries',
			'class' => '1',
			'limit' => '9',
		);

		//forum name (new url)
		$config[] = array(
			'name' => "Forum / name",
			'url' => 'forumname',
			'topic_id' => '*',
		//	'path' => 'forum|name',
			'text' => 'this is the rss feed for the forum_name entries',
			'class' => '0',
			'limit' => '9',
		);
		
		return $config;
	}
	

	/**
	 * Compile RSS Data
	 * @param $parms array	url, limit, id
	 * @return array|bool
	 */
	function data($parms=null)
	{
		$sqlrss = e107::getDb();

		$rss 		= array();
		$limit 		= $parms['limit'];
		$topicid 	= $parms['id'];

		switch($parms['url'])
		{
			// list of all forum topics, including content of first post. Does not list replies. 
			case 'forumthreads':
			case 6:
				$rssQuery =
					"SELECT 
						t.thread_id, t.thread_name, t.thread_datestamp, t.thread_user, t.thread_views, t.thread_lastpost, t.thread_lastuser, t.thread_total_replies, p.post_entry, u.user_name, u.user_email, f.forum_sef 
					FROM 
						#forum_thread AS t
					LEFT JOIN
						#forum_post as p
						ON p.post_thread = t.thread_id 
						AND p.post_id in 
						(
							SELECT MIN(post_id) 
							FROM #forum_post 
							GROUP BY post_thread
						)
					LEFT JOIN 
						#user AS u 
						ON t.thread_user = u.user_id
					LEFT JOIN 
						#forum AS f 
						ON f.forum_id = t.thread_forum_id
					WHERE 
						f.forum_class IN (".USERCLASS_LIST.") 
					ORDER BY 
						t.thread_datestamp DESC 
					LIMIT 0," . $limit;

				$sqlrss->gen($rssQuery);
				$tmp 	= $sqlrss->db_getList();

				$rss 	= array();
				$i 		= 0;

				foreach($tmp as $value)
				{
					$topic_link = 
					e107::url(
						'forum', 
						'topic', 
						array
							(
								'forum_sef' 	=> $value['forum_sef'],
								'thread_id' 	=> $value['thread_id'], 
								'thread_sef' 	=> eHelper::title2sef($value['thread_name']), 
							),
						array('mode' => 'full')
						);
				
					$rss[$i]['author'] 			= $value['user_name'];
					$rss[$i]['author_email'] 	= $value['user_email'];  // must include an email address to be valid		
					$rss[$i]['title'] 			= $value['thread_name'];
					//$rss[$i]['link'] 			= SITEURLBASE . e_PLUGIN_ABS . "forum/forum_viewtopic.php?" . $value['thread_id'];
					$rss[$i]['link'] 			= $topic_link;
					$rss[$i]['description'] 	= $value['post_entry'];
					$rss[$i]['datestamp'] 		= $value['thread_datestamp'];

					$i++;
				}
				break;

			// List of all forum posts (first post and replies)
			case 'forumposts':
			case 7:
				$rssQuery = "
				SELECT
				    t.thread_id, t.thread_name, t.thread_datestamp, t.thread_user, t.thread_views, t.thread_lastpost, t.thread_lastuser, t.thread_total_replies, f.forum_id, f.forum_name, f.forum_class, f.forum_sef, p.post_entry, u.user_name, u.user_email
				FROM
				    #forum_thread AS t
				LEFT JOIN 
					#forum_post as p 
				    ON p.post_thread = t.thread_id
				LEFT JOIN 
					#user AS u
					ON t.thread_user = u.user_id
				LEFT JOIN 
					#forum AS f
					ON f.forum_id = t.thread_forum_id
				WHERE
				    f.forum_class IN(".USERCLASS_LIST.")
				ORDER BY
				    t.thread_datestamp
				DESC
				LIMIT 0," . $limit;

				$sqlrss->gen($rssQuery);
				$tmp 	= $sqlrss->db_getList();
				
				$rss 	= array();
				$i 		= 0;

				foreach($tmp as $value)
				{
					
					$topic_link = 
					e107::url(
						'forum', 
						'topic', 
						array
							(
								'forum_sef' 	=> $value['forum_sef'],
								'thread_id' 	=> $value['thread_id'], 
								'thread_sef' 	=> eHelper::title2sef($value['thread_name']), 
							),
						array('mode' => 'full')
						);

					$rss[$i]['author'] = $value['user_name'];
					$rss[$i]['author_email'] = $value['user_email'];  // must include an email address to be valid.

					// FIXME - reply or topic start? If reply add "RE:" to title 
					/*if($value['parent_name'])
					{
						$rss[$i]['title'] 	= "Re: " . $value['parent_name'];
						$rss[$i]['link'] 	= SITEURLBASE . e_PLUGIN_ABS . "forum/forum_viewtopic.php?" . $value['thread_parent'];
					}
					else
					{
						$rss[$i]['title'] 	= $value['thread_name'];
						$rss[$i]['link'] 	= SITEURLBASE . e_PLUGIN_ABS . "forum/forum_viewtopic.php?" . $value['thread_id'];
					}*/

					$rss[$i]['title'] 		= $value['thread_name'];
					$rss[$i]['link'] 		= $topic_link;
					$rss[$i]['description'] = $value['post_entry'];
					$rss[$i]['datestamp'] 	= $value['thread_datestamp'];

					$i++;
				}
				break;

			// Lists all posts in a specific forum topic 
			case 'forumtopic':
			case 8:
				if(!$topicid)
				{
					return false;
				}

				/* get thread ...  */
				$this->rssQuery = "SELECT t.thread_name, t.thread_thread, t.thread_id, t.thread_name, t.thread_datestamp, t.thread_parent, t.thread_user, t.thread_views, t.thread_lastpost, f.forum_id, f.forum_name, f.forum_class, u.user_name
				FROM #forum_t AS t
				LEFT JOIN #user AS u ON SUBSTRING_INDEX(t.thread_user,'.',1) = u.user_id
				LEFT JOIN #forum AS f ON f.forum_id = t.thread_forum_id
				WHERE f.forum_class  IN (" . USERCLASS_LIST . ") AND t.thread_id=" . intval($topicid);

				$sqlrss->gen($this->rssQuery);
				$topic = $sqlrss->fetch();

				/* get replies ...  */
				$this->rssQuery = "SELECT t.thread_name, t.thread_thread, t.thread_id, t.thread_name, t.thread_datestamp, t.thread_parent, t.thread_user, t.thread_views, t.thread_lastpost, f.forum_id, f.forum_name, f.forum_class, u.user_name, u.user_email
				FROM #forum_t AS t
				LEFT JOIN #user AS u ON SUBSTRING_INDEX(t.thread_user,'.',1) = u.user_id
				LEFT JOIN #forum AS f ON f.forum_id = t.thread_forum_id
				WHERE f.forum_class  IN (" . USERCLASS_LIST . ") AND t.thread_parent=" . intval($topicid);

				$sqlrss->gen($this->rssQuery);
				$replies = $sqlrss->db_getList();

				$rss = array();
				$i = 0;

				// FIXME
/*
				if($value['user_name'])
				{
					$rss[$i]['author'] = $value['user_name'] . " ( " . SITEURLBASE . "user.php?id." . intval($value['thread_user']) . " )";
				}
				else
				{
					$tmp = explode(".", $value['thread_user'], 2);
					list($rss[$i]['author'], $ip) = explode(chr(1), $tmp[1]);
					unset($ip);
				}*/

				$rss[$i]['title'] = $topic['thread_name'];
				$rss[$i]['link'] = SITEURLBASE . e_PLUGIN_ABS . "forum/forum_viewtopic.php?" . $topic['thread_id'];
				$rss[$i]['description'] = $topic['thread_thread'];
				$rss[$i]['datestamp'] = $topic['thread_datestamp'];
				$i++;

				foreach($replies as $value)
				{
					if($value['user_name'])
					{
						$rss[$i]['author'] = $value['user_name'];
						$rss[$i]['author_email'] = $value['user_email'];  // must include an email address to be valid.
					}
					else
					{
						$tmp = explode(".", $value['thread_user'], 2);
						list($rss[$i]['author'], $ip) = explode(chr(1), $tmp[1]);
						unset($ip);
					}
					$rss[$i]['title'] = "Re: " . $topic['thread_name'];
					$rss[$i]['link'] = SITEURLBASE . e_PLUGIN_ABS . "forum/forum_viewtopic.php?" . $topicid;
					$rss[$i]['description'] = $value['thread_thread'];
					$rss[$i]['datestamp'] = $value['thread_datestamp'];
					$i++;
				}
				break;

			// Lists all topics in a specific forum 
			case 'forumname':
			case 11:
				if(empty($parm['id']))
				{
					return false;
				}

				$this->rssQuery = "
			SELECT f.forum_id, f.forum_name, f.forum_class, tp.thread_name AS parent_name, t.*, u.user_name, u.user_email
			FROM #forum_t as t
			LEFT JOIN #user AS u ON SUBSTRING_INDEX(t.thread_user,'.',1) = u.user_id
			LEFT JOIN #forum_t AS tp ON t.thread_parent = tp.thread_id
			LEFT JOIN #forum AS f ON f.forum_id = t.thread_forum_id
			WHERE t.thread_forum_id = " . intval($topicid) . " AND f.forum_class IN (0, 251, 255)
			ORDER BY t.thread_datestamp DESC LIMIT 0," . $limit;

				$sqlrss->gen($this->rssQuery);
				$tmp = $sqlrss->db_getList();
			//	$this->contentType = $this->contentType . " : " . $tmp[1]['forum_name'];
				$rss = array();
				$i = 0;

				foreach($tmp as $value)
				{
					if($value['user_name'])
					{
						$rss[$i]['author'] = $value['user_name'];
						$rss[$i]['author_email'] = $value['user_email'];
					}
					else
					{
						$tmp = explode(".", $value['thread_user'], 2);
						list($rss[$i]['author'], $ip) = explode(chr(1), $tmp[1]);
						unset($ip);
					}

					if($value['parent_name'])
					{
						$rss[$i]['title'] = "Re: " . $value['parent_name'];
						$rss[$i]['link'] = SITEURLBASE . e_PLUGIN_ABS . "forum/forum_viewtopic.php?" . $value['thread_id'] . ".post";
					}
					else
					{
						$rss[$i]['title'] = $value['thread_name'];
						$rss[$i]['link'] = SITEURLBASE . e_PLUGIN_ABS . "forum/forum_viewtopic.php?" . $value['thread_id'];
					}
					$rss[$i]['description'] = $value['thread_thread'];
					$rss[$i]['datestamp'] = $value['thread_datestamp'];
					$i++;
				}
				break;
		}

		return $rss;

	}
}




// ALL CODE BELOW IS OLD v1 CODE. CAN BE DELETED WHEN THE ABOVE (v2) CODE SEEMS TO BE WORKING FINE. 

//##### create feed for admin, return array $eplug_rss_feed --------------------------------

/*
	$feed = get_forum_rss();
	foreach($feed as $k => $v)
	{
		$eplug_rss_feed[] = $v;
	}

	function get_forum_rss()
	{

		$rss = array();

		//forum threads (old url)
		$feed['name'] = "Forum / threads";
		$feed['url'] = '6';
		$feed['topic_id'] = '';
		$feed['path'] = 'forum|threads';
		$feed['text'] = 'this is the rss feed for the forum_threads entries';
		$feed['class'] = '1';
		$feed['limit'] = '9';
		$rss[] = $feed;

		//forum threads (new url)
		$feed['name'] = "Forum / threads";
		$feed['url'] = 'forumthreads';
		$feed['topic_id'] = '';
		$feed['path'] = 'forum|threads';
		$feed['text'] = 'this is the rss feed for the forum_threads entries';
		$feed['class'] = '0';
		$feed['limit'] = '9';
		$rss[] = $feed;

		//forum posts (old url)
		$feed['name'] = "Forum / posts";
		$feed['url'] = '7';
		$feed['topic_id'] = '';
		$feed['path'] = 'forum|posts';
		$feed['text'] = 'this is the rss feed for the forum_posts entries';
		$feed['class'] = '1';
		$feed['limit'] = '9';
		$rss[] = $feed;

		//forum posts (new url)
		$feed['name'] = "Forum / posts";
		$feed['url'] = 'forumposts';
		$feed['topic_id'] = '';
		$feed['path'] = 'forum|posts';
		$feed['text'] = 'this is the rss feed for the forum_posts entries';
		$feed['class'] = '0';
		$feed['limit'] = '9';
		$rss[] = $feed;

		//forum topic (old url)
		$feed['name'] = "Forum / topic";
		$feed['url'] = '8';
		$feed['topic_id'] = '*';
		$feed['path'] = 'forum|topic';
		$feed['text'] = 'this is the rss feed for the forum_topic entries';
		$feed['class'] = '1';
		$feed['limit'] = '9';
		$rss[] = $feed;

		//forum topic (new url)
		$feed['name'] = "Forum / topic";
		$feed['url'] = 'forumtopic';
		$feed['topic_id'] = '*';
		$feed['path'] = 'forum|topic';
		$feed['text'] = 'this is the rss feed for the forum_topic entries';
		$feed['class'] = '0';
		$feed['limit'] = '9';
		$rss[] = $feed;

		//forum name (old url)
		$feed['name'] = "Forum / name";
		$feed['url'] = '11';
		$feed['topic_id'] = '*';
		$feed['path'] = 'forum|name';
		$feed['text'] = 'this is the rss feed for the forum_name entries';
		$feed['class'] = '1';
		$feed['limit'] = '9';
		$rss[] = $feed;

		//forum name (new url)
		$feed['name'] = "Forum / name";
		$feed['url'] = 'forumname';
		$feed['topic_id'] = '*';
		$feed['path'] = 'forum|name';
		$feed['text'] = 'this is the rss feed for the forum_name entries';
		$feed['class'] = '0';
		$feed['limit'] = '9';
		$rss[] = $feed;

		return $rss;
	}

//##### ------------------------------------------------------------------------------------


//##### create rss data, return as array $eplug_rss_data -----------------------------------
	$sqlrss = new db;


	switch($this->parm)
	{

		case 'threads':
		case 6:
			$this->rssQuery =
				"SELECT t.thread_thread, t.thread_id, t.thread_name, t.thread_datestamp, t.thread_parent, t.thread_user, t.thread_views, t.thread_lastpost, t.thread_lastuser, t.thread_total_replies, u.user_name, u.user_email FROM #forum_t AS t
			LEFT JOIN #user AS u ON SUBSTRING_INDEX(t.thread_user,'.',1) = u.user_id
			LEFT JOIN #forum AS f ON f.forum_id = t.thread_forum_id
			WHERE f.forum_class IN (" . USERCLASS_LIST . ") AND t.thread_parent=0
			ORDER BY t.thread_datestamp DESC LIMIT 0," . $this->limit;

			$sqlrss->gen($this->rssQuery);
			$tmp = $sqlrss->db_getList();

			$rss = array();
			$i = 0;
			foreach($tmp as $value)
			{

				if($value['user_name'])
				{
					$rss[$i]['author'] = $value['user_name'];
					$rss[$i]['author_email'] = $value['user_email'];  // must include an email address to be valid.
				}
				else
				{
					$tmp = explode(".", $value['thread_user'], 2);
					list($rss[$i]['author'], $ip) = explode(chr(1), $tmp[1]);
				}

				$rss[$i]['title'] = $value['thread_name'];
				$rss[$i]['link'] = SITEURLBASE . e_PLUGIN_ABS . "forum/forum_viewtopic.php?" . $value['thread_id'];
				$rss[$i]['description'] = $value['thread_thread'];
				$rss[$i]['datestamp'] = $value['thread_datestamp'];

				$i++;
			}
			break;

		case 'posts':
		case 7:
			$this->rssQuery = "SELECT tp.thread_name AS parent_name, t.thread_thread, t.thread_id, t.thread_name, t.thread_datestamp, t.thread_parent, t.thread_user, t.thread_views, t.thread_lastpost, t.thread_lastuser, t.thread_total_replies, f.forum_id, f.forum_name, f.forum_class, u.user_name, u.user_email FROM #forum_t AS t
			LEFT JOIN #user AS u ON SUBSTRING_INDEX(t.thread_user,'.',1) = u.user_id
			LEFT JOIN #forum_t AS tp ON t.thread_parent = tp.thread_id
			LEFT JOIN #forum AS f ON f.forum_id = t.thread_forum_id
			WHERE f.forum_class  IN (" . USERCLASS_LIST . ")
			ORDER BY t.thread_datestamp DESC LIMIT 0," . $this->limit;

			$sqlrss->gen($this->rssQuery);
			$tmp = $sqlrss->db_getList();
			$rss = array();
			$i = 0;
			foreach($tmp as $value)
			{

				if($value['user_name'])
				{
					$rss[$i]['author'] = $value['user_name'];
					$rss[$i]['author_email'] = $value['user_email'];  // must include an email address to be valid.
				}
				else
				{
					$tmp = explode(".", $value['thread_user'], 2);
					list($rss[$i]['author'], $ip) = explode(chr(1), $tmp[1]);
				}

				if($value['parent_name'])
				{
					$rss[$i]['title'] = "Re: " . $value['parent_name'];
					$rss[$i]['link'] = $e107->base_path . $PLUGINS_DIRECTORY . "forum/forum_viewtopic.php?" . $value['thread_parent'];
				}
				else
				{
					$rss[$i]['title'] = $value['thread_name'];
					$rss[$i]['link'] = $e107->base_path . $PLUGINS_DIRECTORY . "forum/forum_viewtopic.php?" . $value['thread_id'];
				}

				$rss[$i]['description'] = $value['thread_thread'];
				$rss[$i]['datestamp'] = $value['thread_datestamp'];

				$i++;
			}
			break;

		case 'topic':
		case 8:
			if(!$this->topicid)
			{
				return false;
			}

			// get thread ...
			$this->rssQuery = "SELECT t.thread_name, t.thread_thread, t.thread_id, t.thread_name, t.thread_datestamp, t.thread_parent, t.thread_user, t.thread_views, t.thread_lastpost, f.forum_id, f.forum_name, f.forum_class, u.user_name
			FROM #forum_t AS t
			LEFT JOIN #user AS u ON SUBSTRING_INDEX(t.thread_user,'.',1) = u.user_id
			LEFT JOIN #forum AS f ON f.forum_id = t.thread_forum_id
			WHERE f.forum_class  IN (" . USERCLASS_LIST . ") AND t.thread_id=" . intval($this->topicid);

			$sqlrss->gen($this->rssQuery);
			$topic = $sqlrss->db_Fetch();

			//  get replies ...
			$this->rssQuery = "SELECT t.thread_name, t.thread_thread, t.thread_id, t.thread_name, t.thread_datestamp, t.thread_parent, t.thread_user, t.thread_views, t.thread_lastpost, f.forum_id, f.forum_name, f.forum_class, u.user_name, u.user_email
			FROM #forum_t AS t
			LEFT JOIN #user AS u ON SUBSTRING_INDEX(t.thread_user,'.',1) = u.user_id
			LEFT JOIN #forum AS f ON f.forum_id = t.thread_forum_id
			WHERE f.forum_class  IN (" . USERCLASS_LIST . ") AND t.thread_parent=" . intval($this->topicid);

			$sqlrss->gen($this->rssQuery);
			$replies = $sqlrss->db_getList();

			$rss = array();
			$i = 0;

			if($value['user_name'])
			{
				$rss[$i]['author'] = $value['user_name'] . " ( " . $e107->base_path . "user.php?id." . intval($value['thread_user']) . " )";
			}
			else
			{
				$tmp = explode(".", $value['thread_user'], 2);
				list($rss[$i]['author'], $ip) = explode(chr(1), $tmp[1]);
			}

			$rss[$i]['title'] = $topic['thread_name'];
			$rss[$i]['link'] = $e107->base_path . $PLUGINS_DIRECTORY . "forum/forum_viewtopic.php?" . $topic['thread_id'];
			$rss[$i]['description'] = $topic['thread_thread'];
			$rss[$i]['datestamp'] = $topic['thread_datestamp'];
			$i++;

			foreach($replies as $value)
			{
				if($value['user_name'])
				{
					$rss[$i]['author'] = $value['user_name'];
					$rss[$i]['author_email'] = $value['user_email'];  // must include an email address to be valid.
				}
				else
				{
					$tmp = explode(".", $value['thread_user'], 2);
					list($rss[$i]['author'], $ip) = explode(chr(1), $tmp[1]);
				}
				$rss[$i]['title'] = "Re: " . $topic['thread_name'];
				$rss[$i]['link'] = $e107->base_path . $PLUGINS_DIRECTORY . "forum/forum_viewtopic.php?" . $this->topicid;
				$rss[$i]['description'] = $value['thread_thread'];
				$rss[$i]['datestamp'] = $value['thread_datestamp'];
				$i++;
			}
			break;

		case 'name':
		case 11:
			if(!$this->topicid)
			{
				return false;
			}

			$this->rssQuery = "
		SELECT f.forum_id, f.forum_name, f.forum_class, tp.thread_name AS parent_name, t.*, u.user_name, u.user_email
		FROM #forum_t as t
		LEFT JOIN #user AS u ON SUBSTRING_INDEX(t.thread_user,'.',1) = u.user_id
		LEFT JOIN #forum_t AS tp ON t.thread_parent = tp.thread_id
		LEFT JOIN #forum AS f ON f.forum_id = t.thread_forum_id
		WHERE t.thread_forum_id = " . intval($this->topicid) . " AND f.forum_class IN (0, 251, 255)
		ORDER BY t.thread_datestamp DESC LIMIT 0," . $this->limit;

			$sqlrss->db_Select_gen($this->rssQuery);
			$tmp = $sqlrss->db_getList();
			$this->contentType = $this->contentType . " : " . $tmp[1]['forum_name'];
			$rss = array();
			$i = 0;

			foreach($tmp as $value)
			{
				if($value['user_name'])
				{
					$rss[$i]['author'] = $value['user_name'];
					$rss[$i]['author_email'] = $value['user_email'];
				}
				else
				{
					$tmp = explode(".", $value['thread_user'], 2);
					list($rss[$i]['author'], $ip) = explode(chr(1), $tmp[1]);
				}

				if($value['parent_name'])
				{
					$rss[$i]['title'] = "Re: " . $value['parent_name'];
					$rss[$i]['link'] = $e107->base_path . $PLUGINS_DIRECTORY . "forum/forum_viewtopic.php?" . $value['thread_id'] . ".post";
				}
				else
				{
					$rss[$i]['title'] = $value['thread_name'];
					$rss[$i]['link'] = $e107->base_path . $PLUGINS_DIRECTORY . "forum/forum_viewtopic.php?" . $value['thread_id'];
				}
				$rss[$i]['description'] = $value['thread_thread'];
				$rss[$i]['datestamp'] = $value['thread_datestamp'];
				$i++;
			}
			break;
	}

	$eplug_rss_data[] = $rss;

*/
