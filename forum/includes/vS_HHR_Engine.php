<?php
/*======================================================================*\
|| #################################################################### ||
|| # vS-Hide Hack Resurrection (Expanded Edition) for vBulletin 3.5.x - 3.8.x by Anton Kanevsky
|| #################################################################### ||
|| # Copyright ©2006-2009 Anton Kanevsky (ankan925@gmail.com) aka @kan. All Rights Reserved.
|| # This file may not be redistributed.
|| #################################################################### ||
\*======================================================================*/

class vS_HHR_Engine
{
	/**
	* The vBulletin registry object
	*
	* @var	vB_Registry
	*/
	var $registry = null;

	/**
	* Engine Tags
	*
	* @var	array
	*/
	var $tags = array(
		0 => 'HIDE-POSTS',
		/* ## */
		1 => 'HIDE-REPLY',
		2 => 'HIDE-THANKS',
		3 => 'HIDE-REPLY-THANKS',
		/* ## */
		4 => 'SHOWTOGROUPS',
		/* ## */
		5 => 'STU'
	);
	
	/**
	* Engine Settings
	*
	* @var	array
	*/
	var $settings = array(
		'banned' => false,
		'dohtml' => false,
	);
	
	/**
	* Engine Variables
	*
	* @var	array
	*/
	var $vars = array();
	
	/**
	* Thanks Cache for This Post
	*
	* @var	array
	*/	
	/* ## */
	var $thankscache = array();
	/* ## */
	
	/**
	* Constructor
	*/
	function vS_HHR_Engine(&$registry, $forumid = 0)
	{
		// globalize variables
		global $show, $vbphrase;

		// verify vbulletin presence
		if (is_object($registry))
		{
			$this->registry =& $registry;
		}
		else
		{
			trigger_error("vB_Database::Registry object is not an object", E_USER_ERROR);
		}
		
		/* ## */
		// define whether to show the thank you system
		if (!$forumid || !isset($this->registry->forumcache[$forumid]))
		{
			$show['hidetag_thankyou_system'] = $this->registry->options['hidetag_thankyou_system'];
		}
		else
		{
			$show['hidetag_thankyou_system'] = ($this->registry->options['hidetag_thankyou_system'] & $this->registry->forumcache[$forumid]['hidetag_thankyou_system']);
		}
		
		// and if not, disable buttons
		if (!$show['hidetag_thankyou_system'])
		{
			$this->registry->options['enable_htnx_tag'] = false;
			$this->registry->options['enable_hrplytnx_tag'] = false;
		}
		/* ## */
		
		// define whether the viewer cannot be view anything by definition
		if (substr($this->registry->options['templateversion'], 0, 3) == '3.5')
		{
			$is_banned = (($this->registry->usergroupcache[$this->registry->userinfo['usergroupid']]['genericoptions'] & $this->registry->bf_ugp_genericoptions['isbannedgroup']) ? true : false);
		}
		else
		{
			$is_banned = (!($this->registry->usergroupcache[$this->registry->userinfo['usergroupid']]['genericoptions'] & $this->registry->bf_ugp_genericoptions['isnotbannedgroup']) ? true : false);			
		}
		
		if (is_member_of($this->registry->userinfo, 3, 4) OR $is_banned OR THIS_SCRIPT == 'misc')
		{
			$this->settings['banned'] = true;
		}
		else
		{
			$this->settings['banned'] = false;
		}
		
		// define whether to use html version of replacements
		if (in_array(THIS_SCRIPT, array('ajax', 'editpost', 'misc', 'newthread', 'newreply', 'printthread', 'showpost', 'showthread')))
		{
			$this->settings['dohtml'] = true;
		}
		else
		{
			$this->settings['dohtml'] = false;
		}
		
		// apply makeup to $this->tags
		foreach ($this->tags as $key => $tag)
		{
			$this->tags["$tag"] =& $this->tags["$key"];
		}
		
		if (isset($this->tags[$this->registry->options['hidetag_shortcut']]))
		{
			$this->tags[$this->registry->options['hidetag_shortcut']] .= '|HIDE';
		}
	
		// define whether to show the buttons
		if (in_array(THIS_SCRIPT, array('editpost', 'newthread', 'newreply')) OR (THIS_SCRIPT == 'ajax' AND $_POST['do'] == 'quickedit') OR (THIS_SCRIPT == 'showthread' AND $this->registry->options['quickreply']))
		{
			global $foruminfo;
			
			$show['toolbar_hposts_button'] = 
			(
				$this->registry->options['enable_hposts_tag'] &&
				$foruminfo['enable_hposts_tag'] &&
				($this->registry->userinfo['permissions']['hhroptions'] & $this->registry->bf_ugp['hhroptions']['enable_hposts_tag'])
			);
			/* ## */
			$show['toolbar_hrply_button'] = 
			(
				$this->registry->options['enable_hrply_tag'] &&
				$foruminfo['enable_hrply_tag'] && 
				($this->registry->userinfo['permissions']['hhroptions'] & $this->registry->bf_ugp['hhroptions']['enable_hrply_tag'])
			);
			$show['toolbar_htnx_button'] = 
			(
				$this->registry->options['enable_htnx_tag'] &&
				$foruminfo['enable_htnx_tag'] &&
				($this->registry->userinfo['permissions']['hhroptions'] & $this->registry->bf_ugp['hhroptions']['enable_htnx_tag'])
			);
			$show['toolbar_hrplytnx_button'] = 
			(
				$this->registry->options['enable_hrplytnx_tag'] && 
				$foruminfo['enable_hrplytnx_tag'] && 
				($this->registry->userinfo['permissions']['hhroptions'] & $this->registry->bf_ugp['hhroptions']['enable_hrplytnx_tag'])
			);
			/* ## */
			$show['toolbar_showtogroups_button'] = (
				$this->registry->options['enable_showtogroups_tag'] && 
				$foruminfo['enable_showtogroups_tag'] && 
				($this->registry->userinfo['permissions']['hhroptions'] & $this->registry->bf_ugp['hhroptions']['enable_showtogroups_tag'])
			);
			/* ## */
			$show['toolbar_stu_button'] = (
				$this->registry->options['enable_stu_tag'] && 
				$foruminfo['enable_stu_tag'] && 
				($this->registry->userinfo['permissions']['hhroptions'] & $this->registry->bf_ugp['hhroptions']['enable_stu_tag'])
			);
			
			if ($show['toolbar_hposts_button'] /* ## */ OR $show['toolbar_htnx_button'] OR $show['toolbar_hrply_button'] OR $show['toolbar_hrplytnx_button'] /* ## */ OR $show['toolbar_showtogroups_button']  /* ## */ OR $show['toolbar_stu_button']  /* ## */)
			{
				$show['toolbar_hhr_separator'] = true;
				switch ($this->registry->options['hidetag_shortcut'])
				{
					case 0:
						$vbphrase['hidetag_explanation_shortcut'] = $vbphrase['hidetag_explanation_hposts'];
						$show['toolbar_shortcut_button'] = $show['toolbar_hposts_button'];
						break;
					/* ## */
					case 1:
						$vbphrase['hidetag_explanation_shortcut'] = $vbphrase['hidetag_explanation_hrply'];
						$show['toolbar_shortcut_button'] = $show['toolbar_hrply_button'];
						break;
					case 2:
						$vbphrase['hidetag_explanation_shortcut'] = $vbphrase['hidetag_explanation_htnx'];
						$show['toolbar_shortcut_button'] = $show['toolbar_htnx_button'];
						break;
					case 3:
						$vbphrase['hidetag_explanation_shortcut'] = $vbphrase['hidetag_explanation_hrplytnx'];
						$show['toolbar_shortcut_button'] = $show['toolbar_hrplytnx_button'];
						break;
					/* ## */
					case 4:
						$vbphrase['hidetag_explanation_shortcut'] = $vbphrase['hidetag_explanation_showtogroups'];
						$show['toolbar_shortcut_button'] = $show['toolbar_showtogroups_button'];
						break;
					/* ## */
					case 5:
						$vbphrase['hidetag_explanation_shortcut'] = $vbphrase['hidetag_explanation_stu'];
						$show['toolbar_shortcut_button'] = $show['toolbar_stu_button'];
						break;
					default:
						$vbphrase['hidetag_explanation_shortcut'] = '';
						$show['toolbar_shortcut_button'] = false;
				}
			}
			else
			{
				$show['toolbar_hhr_separator'] = false;
				$show['toolbar_shortcut_button'] = false;
			}
		}
	}

	###########################################################################################################################
	# vS-Hide Hack Resurrection - vB Code Parser
	###########################################################################################################################
	
	/**
	* Main function that is used to parse the tags.
	*
	* @var		string	Message (Raw)
	*
	* @return 	string	Message (Parsed)
	*/
	function parse_bbcode($message, $forumid, $threadid, $postid, $userid)
	{
		// globalize variables
		global $vbphrase, $stylevar, $show;
		
		// fetch variables
		$this->vars = array(
			'forumid' => intval($forumid),
			'threadid' => intval($threadid),
			'postid' => intval($postid),
			'userid' => intval($userid)
		);
		
		// parse message
		$message = preg_replace("/\[(" . $this->tags['HIDE-POSTS'] . ")\]/siU", '[\\1=' . $this->registry->options['hidetag_defaultposts'] . ']', $message);
		$message = preg_replace("/\[(" . $this->tags['HIDE-POSTS'] . ")=(&quot;|\"|'|)([0-9]+)\\2\](.*)\[\/\\1\]/esiU", "\$this->parse_bbcode_hposts('\\4', \\3)", $message);
		$message = preg_replace("/\[(" . $this->tags['HIDE-POSTS'] . ")=" . $this->registry->options['hidetag_defaultposts'] . "\]/siU", '[\\1]', $message);		
		
		/* ## */
		$message = preg_replace("/\[(" . $this->tags['HIDE-REPLY'] . ")\](.*)\[\/\\1\]/esiU", "\$this->parse_bbcode_rt('\\2', 'reply')", $message);
		$message = preg_replace("/\[(" . $this->tags['HIDE-THANKS'] . ")\](.*)\[\/\\1\]/esiU", "\$this->parse_bbcode_rt('\\2', 'thanks')", $message);
		$message = preg_replace("/\[(" . $this->tags['HIDE-REPLY-THANKS'] . ")\](.*)\[\/\\1\]/esiU", "\$this->parse_bbcode_rt('\\2', 'either')", $message);		
		/* ## */
		
		$message = preg_replace("/\[(" . $this->tags['SHOWTOGROUPS'] . ")=(&quot;|\"|'|)([0-9,]+)\\2\](.*)\[\/\\1\]/esiU", "\$this->parse_bbcode_showtogroups('\\4', '\\3')", $message);
		
		/* ## */
		$message = preg_replace("/\[(" . $this->tags['STU'] . ")=(&quot;|\"|'|)([0-9,]+)\\2\](.*)\[\/\\1\]/esiU", "\$this->parse_bbcode_stu('\\4', '\\3')", $message);
		/* ## */
		
		// come to papa
		return $message;
	}
	
	/**
	* Helper function used to parse HIDE-POSTS
	*
	* @var		string	Message (Raw)
	* @var		int		The number of required posts.
	*
	* @return 	string	Message (Parsed)
	*/
	function parse_bbcode_hposts($message, $posts_required)
	{
		// globalize variables
		global $vbphrase, $stylevar, $show;
		
		// intialize field counter, for xhtml purposes
		static $hidefieldid = 0;
		$hidefieldid++;
		
		// correct the escapements
		$message = str_replace('\"', '"', $message);

		// decide whether the person can view
		$canview = false;
		if (!$this->settings['banned'])
		{
			if ($this->can_override())
			{
				$canview = true;
			}
			else
			{
				if ($this->registry->userinfo['userid'] AND $this->registry->userinfo['posts'] >= $posts_required)
				{
					$canview = true;
				}
			}
		}
		
		// construct caption
		if ($this->registry->userinfo['userid'])
		{
			$caption = construct_phrase($vbphrase['hidetag_caption_hposts_registered'], $posts_required, $this->registry->userinfo['posts']);
		}
		else if ($posts_required)
		{
			$caption = construct_phrase($vbphrase['hidetag_caption_hposts_guest_x'], $posts_required);
		}
		else
		{
			$caption = construct_phrase($vbphrase['hidetag_caption_hposts_guest']);
		}
		
		// construct replacement
		if ($this->settings['dohtml'])
		{
			eval('$message = "' . fetch_template('bbcode_hposts') . '";');
		}
		else
		{
			$message = ($canview ? $message : construct_phrase($vbphrase['hidetag_message_nohtml'], $caption));
		}
		
		// come to papa
		return $message;
	}
	
	/**
	* Helper function used to parse HIDE-REPLY, HIDE-THANKS, HIDE-REPLY-THANKS
	*
	* @var		string	Message (Raw)
	* @var		string	Method of Parsing ('reply', 'thanks', 'either')
	*
	* @return 	string	Message (Parsed)
	*/
	/* ## */
	function parse_bbcode_rt($message, $method = 'either')
	{
		// globalize variables
		global $vbphrase, $stylevar, $show;
		
		// intialize field counter, for xhtml purposes
		static $hidefieldid = 0;
		$hidefieldid++;
		
		// correct the escapements
		$message = str_replace('\"', '"', $message);
		
		// decide whether the person can view
		$canview = false;
		if (!$this->settings['banned'])
		{
			if ($this->can_override())
			{
				$canview = true;
			}
			else
			{
				if ($method == 'either' || $method == 'reply')
				{
					if (!$canview)
					{
						static $has_reply = array();
							
						if (!isset($has_reply[$this->vars['threadid']]))
						{
							$check_reply = $this->registry->db->query_first("
								SELECT postid 
								FROM " . TABLE_PREFIX . "post
								WHERE 
									userid = " . $this->registry->userinfo['userid'] . "
									AND (userid > 0 OR ipaddress = '" . IPADDRESS . "')
									AND threadid = '" . $this->vars['threadid'] . "'
									AND visible = 1
								LIMIT 1
							");

							$canview = $has_reply[$this->vars['threadid']] = ($check_reply ? true : false);
						}
						else
						{
							$canview = $has_reply[$this->vars['threadid']];
						}
					}
				}
			
				if ($method == 'either' || $method == 'thanks')
				{
					if (!$canview)
					{
						$canview = isset($this->thankscache[$this->registry->userinfo['userid']]);
					}
				}
			}
		}
		
		// construct replacement
		if ($this->settings['dohtml'])
		{
			switch ($method)
			{
				case 'reply':
					eval('$message = "' . fetch_template('bbcode_hrply') . '";');
					break;
				case 'thanks':
					eval('$message = "' . fetch_template('bbcode_htnx') . '";');
					break;
				default:
					eval('$message = "' . fetch_template('bbcode_hrplytnx') . '";');
					break;
			}
		}
		else
		{
			switch ($method)
			{
				case 'reply':
					$message = ($canview ? $message : construct_phrase($vbphrase['hidetag_message_nohtml'], $vbphrase['hidetag_accessdenied_hrply']));
					break;
				case 'thanks':
					$message = ($canview ? $message : construct_phrase($vbphrase['hidetag_message_nohtml'], $vbphrase['hidetag_accessdenied_htnx']));
					break;
				default:
					$message = ($canview ? $message : construct_phrase($vbphrase['hidetag_message_nohtml'], $vbphrase['hidetag_accessdenied_hrplytnx']));
					break;
			}		
		}
		
		// come to papa
		return $message;
	}
	/* ## */
	
	/**
	* Helper function used to parse SHOWTOGROUPS
	*
	* @var		string	Message (Raw)
	* @var		string	Comma-Separated Usergroupids
	*
	* @return 	string	Message (Parsed)
	*/
	function parse_bbcode_showtogroups($message, $usergroupids) 
	{
		// globalize variables
		global $vbphrase, $stylevar, $show;
		
		// intialize field counter, for xhtml purposes
		static $hidefieldid = 0;
		$hidefieldid++;
		
		// correct the escapements
		$message = str_replace('\"', '"', $message);
		
		// fetch usergroups
		$usergroupids = array_intersect(array_keys($this->registry->usergroupcache), explode(",", $usergroupids));
		sort($usergroupids, SORT_NUMERIC);

		if (sizeof($usergroupids) > 0)
		{
			foreach ($usergroupids as $usergroupid)
			{
				$uglist .= (!empty($uglist) ? ', ' : '') . $this->registry->usergroupcache[$usergroupid]['title'] . " :: " . $usergroupid;
			}
		}
		else
		{
			$uglist = "N/A :: 0 - Invalid Usergroup(s) Specified";
		}
		
		// decide whether the person can view
		$canview = false;		
		if (!$this->settings['banned'])
		{
			if ($this->can_override())
			{
				$canview = true;
			}
			else
			{
				if (is_member_of($this->registry->userinfo, $usergroupids))
				{
					$canview = true;
				}
			}
		}
		
		// construct caption
		$caption = construct_phrase($vbphrase['hidetag_caption_showtogroups'], $uglist);
		
		// construct replacement
		if ($this->settings['dohtml'])
		{
			eval('$message = "' . fetch_template('bbcode_showtogroups') . '";');
		}
		else
		{
			$message = ($canview ? $message : construct_phrase($vbphrase['hidetag_message_nohtml'], $caption));
		}
		
		// come to papa
		return $message;
	}
	
	/**
	* Helper function used to parse STU
	*
	* @var		string	Message (Raw)
	* @var		string	Comma-Separated Userids
	*
	* @return 	string	Message (Parsed)
	*/
	function parse_bbcode_stu($message, $userids) 
	{
		// globalize variables
		global $vbphrase, $stylevar, $show;
		
		// intialize field counter, for xhtml purposes
		static $hidefieldid = 0;
		$hidefieldid++;
		
		// correct the escapements
		$message = str_replace('\"', '"', $message);
		
		// fetch users
		$users = explode(",", $userids);
		sort($users, SORT_NUMERIC);

		if (sizeof($users) > 0)
		{
			foreach ($users as $userid)
			{
				$userlist .= (!empty($userlist) ? ', ' : '') . $userid;
			}
		}
		else
		{
			$userlist = "N/A :: 0 - Invalid User(s) Specified";
		}
		
		// decide whether the person can view
		$canview = false;		
		if (!$this->settings['banned'])
		{
			if ($this->can_override())
			{
				$canview = true;
			}
			else
			{
				if (in_array($this->registry->userinfo['userid'], $users))
				{
					$canview = true;
				}
			}
		}
		
		// construct caption
		$caption = construct_phrase($vbphrase['hidetag_caption_stu'], $userlist);
		
		// construct replacement
		if ($this->settings['dohtml'])
		{
			eval('$message = "' . fetch_template('bbcode_stu') . '";');
		}
		else
		{
			$message = ($canview ? $message : construct_phrase($vbphrase['hidetag_message_nohtml'], $caption));
		}
		
		// come to papa
		return $message;
	}
	
	/**
	* Helper function used to strip bbcode.
	*
	* @var		string	Message (Raw)
	* @var		string	Purpose of Stripping ('editor', 'email', 'dopost')
	*
	* @return 	string	Message (Parsed)
	*/
	function strip_bbcode($message, $purpose = '') 
	{
		// globalize variables
		global $vbphrase, $stylevar, $show;

		// construct replacement
		switch ($purpose)
		{
			case 'editor':
				$replacement = $vbphrase['hidetag_stripped_quote'];
				break;
			case 'email':
				$replacement = $vbphrase['hidetag_stripped_email'];
				break;
			case 'dopost':
				$replacement = '';
				break;
			default:
				$replacement = 'vS-Hide Hack Resurrection: Undefined Replacement';
				break;
		}

		if (!($purpose == 'dopost' AND $show['toolbar_hposts_button']))
		{
			$message = preg_replace("/\[(" . $this->tags['HIDE-POSTS'] . ")\](.*)\[\/\\1\]/siU", $replacement, $message);
			$message = preg_replace("/\[(" . $this->tags['HIDE-POSTS'] . ")=(&quot;|\"|'|)([0-9]+)\\2\](.*)\[\/\\1\]/siU", $replacement, $message);		
		}

		/* ## */
		if (!($purpose == 'dopost' AND $show['toolbar_hrply_button']))
		{
			$message = preg_replace("/\[(" . $this->tags['HIDE-REPLY'] . ")\](.*)\[\/\\1\]/siU", $replacement, $message);
		}
		
		if (!($purpose == 'dopost' AND $show['toolbar_htnx_button']))
		{
			$message = preg_replace("/\[(" . $this->tags['HIDE-THANKS'] . ")\](.*)\[\/\\1\]/siU", $replacement, $message);
		}
		
		if (!($purpose == 'dopost' AND $show['toolbar_hrplytnx_button']))
		{
			$message = preg_replace("/\[(" . $this->tags['HIDE-REPLY-THANKS'] . ")\](.*)\[\/\\1\]/siU", $replacement, $message);
		}
		/* ## */
		
		if (!($purpose == 'dopost' AND $show['toolbar_showtogroups_button']))
		{
			$message = preg_replace("/\[(" . $this->tags['SHOWTOGROUPS'] . ")=(&quot;|\"|'|)([0-9,]+)\\2\](.*)\[\/\\1\]/siU", $replacement, $message);
		}
		
		/* ## */
		if (!($purpose == 'dopost' AND $show['toolbar_stu_button']))
		{
			$message = preg_replace("/\[(" . $this->tags['STU'] . ")=(&quot;|\"|'|)([0-9,]+)\\2\](.*)\[\/\\1\]/siU", $replacement, $message);
		}
		/* ## */
		
		// come to papa
		return $message;
	}
	
	/**
	* Returns true if the viewer can view hidden content regardless of conditional, false otherwise.
	*
	* @return 	boolean
	*/
	function can_override()
	{
		if ($this->registry->userinfo['permissions']['hhroptions'] & $this->registry->bf_ugp['hhroptions']['can_override_hide'])
		{
			return true;
		}
		if (in_array(THIS_SCRIPT, array('editpost', 'newthread')))
		{
			return true; // can override if [user is editing or making a post]
		}
		if (THIS_SCRIPT == 'newreply' AND !$this->vars['postid'])
		{
			return true; // can override if [user is previewing a new post]
		}
		if ($this->registry->userinfo['userid'] AND $this->registry->userinfo['userid'] == $this->vars['userid'])
		{
			return true; // can override if [user is the absolute post's owner (not a guest)]
		}
		if (can_moderate($this->vars['forumid'], 'caneditposts'))
		{
			return true; // can override if [user is a moderator of the current forum with post editing permissions]
		}
		
		return false;
	}
	
	/* ## */
	
	/**
	* Integrated Thank-You Engine ® - Sets Thanks Cache
	*/
	function set_thankscache(&$thankscache)
	{
		if (is_array($thankscache))
		{
			$this->thankscache =& $thankscache;
		}
		else if ($thankscache = unserialize($thankscache))
		{
			$this->thankscache =& $thankscache;
		}
		else
		{
			$thankscache = array();
			$this->thankscache =& $thankscache;
		}
	}
	
	/**
	* Integrated Thank-You Engine ® - Inserts a 'Thank You' into the database.
	*
	* @var &array	Post Info Holder
	* @var &array	Thread Info Holder
	* @var &array	Forum Info Holder
	* @var boolean	Whether to die on error
	*
	* @return integer	0 on registered user failure, 1 on success, 9999 on unregistered user failure
	*/
	function insert_thanks(&$postinfo, &$threadinfo, &$foruminfo, $die = true)
	{
		// sanitize it
		if (!is_array($postinfo) OR !is_array($threadinfo) OR !is_array($foruminfo))
		{
			return 0;
		}
		
		// initialize thankscache
		$this->set_thankscache($postinfo['thankscache']);
		
		// verify that the post is thankable
		if (!$this->is_thankable($postinfo, $threadinfo, $foruminfo))
		{
			if ($die)
			{
				print_no_permission();
			}
			return 0;
		}

		// verify permissions
		if (!$this->registry->userinfo['userid'] OR $this->registry->userinfo['userid'] == $postinfo['userid'] OR isset($this->thankscache[$this->registry->userinfo['userid']]))
		{
			if ($die)
			{
				print_no_permission();
			}
			
			return ($this->registry->userinfo['userid'] ? 0 : 9999);
		}
		
		// update thanks cache
		$this->thankscache = array($this->registry->userinfo['userid'] => array(
				'username'	=> $this->registry->userinfo['username'], 
				'dateline'	=> TIMENOW,
				'deleted'	=> false,
		)) + $this->thankscache;

		// update database
		$this->registry->db->query_write("
			UPDATE " . TABLE_PREFIX . "post
			SET thankscache = '" . $this->registry->db->escape_string(serialize($this->thankscache)) . "'
			WHERE postid = " . $postinfo['postid'] . "
			LIMIT 1
		");
		
		$this->registry->db->query_write("
			UPDATE " . TABLE_PREFIX . "user
			SET thankedcount = thankedcount + 1
			WHERE userid = " . $postinfo['userid'] . "
			LIMIT 1
		");
		
		$this->registry->db->query_write("
			INSERT INTO " . TABLE_PREFIX . "thanks
			VALUES (" . $this->registry->userinfo['userid'] . ", '" . $postinfo['postid'] . "', '" . $postinfo['userid'] . "', " . TIMENOW . ")
		");
		
		// thread bump
		if ($this->registry->options['hidetag_thanks_bump'] && $foruminfo['hidetag_thanks_bump'])
		{
			// require functions
			require_once(DIR . '/includes/functions_bigthree.php');
			
			// update lastpost in the thread
			$threadman =& datamanager_init('Thread', $this->registry, ERRTYPE_SILENT, 'threadpost'); 
			$threadman->set_existing($threadinfo); 
			$threadman->set('lastpost', TIMENOW); 
			$threadman->save();
			
			// mark it read
			mark_thread_read($threadinfo, $foruminfo, $this->registry->userinfo['userid'], TIMENOW);
		}

		// execute reputation change
		$this->thanks_reputation($postinfo['userid'], 1, 'plus');
		
		// come to papa
		return 1;
	}

	/**
	* Integrated Thank-You Engine ® - Deletes 'Thank You' from the database. Can delete multiple items.
	* Is triggered from the public areas by using the standard "remove thanks" buttons.
	*
	* @var &array	Post Info Holder
	* @var &array	Thread Info Holder
	* @var &array	Forum Info Holder
	* @var integer	Whose 'Thank You' to delete (-1 to delete everyone's for the post)
	* @var boolean	Whether to die on error
	*
	* @return integer	0 on failure, 1 on single-item success, 2 on multiple-item success
	*/
	function delete_thanks(&$postinfo, &$threadinfo, &$foruminfo, $userid, $die = true)
	{
		// sanitize it
		$userid = intval($userid);
		if (!is_array($postinfo) OR !is_array($threadinfo) OR !is_array($foruminfo))
		{
			return 0;
		}
		
		// initialize thankscache
		$this->set_thankscache($postinfo['thankscache']);
		
		// verify that the post is unthankable
		if (!$this->registry->options['hidetag_thankyou_system'])
		{
			if ($die)
			{
				print_no_permission();
			}
			return 0;
		}
		
		// verify permissions
		$candeleteown = ($this->registry->userinfo['permissions']['hhroptions'] & $this->registry->bf_ugp['hhroptions']['can_delete_own_thanks']);
		$candeleteoth = ($this->registry->userinfo['permissions']['hhroptions'] & $this->registry->bf_ugp['hhroptions']['can_delete_oth_thanks']);
		$candeleteall = ($candeleteown && $candeleteoth);
		
		$return = 0;
		if ($postinfo && ($candeleteown || $candeleteoth))
		{
			$dodeleteall = false;
			
			if ($userid == -1)
			{
				if ($candeleteall)
				{
					$dodeleteall = true;
					$return = 2;
				}
			}
			else if (($userid == $this->registry->userinfo['userid'] AND $candeleteown) || ($userid != $this->registry->userinfo['userid'] AND $candeleteoth))
			{
				$return = 1;
			}
		}
		
		// execute operations
		if (!$return)
		{
			($die ? (print_no_permission()) : false);
		}
		else
		{
			if (!$dodeleteall)
			{
				// update thanks cache
				if (isset($this->thankscache["$userid"]))
				{
					unset($this->thankscache["$userid"]);
				}
				else
				{
					if ($die)
					{
						print_no_permission();
					}
					return 0;
				}

				// update database
				$this->registry->db->query_write("
					UPDATE " . TABLE_PREFIX . "post
					SET thankscache = '" . $this->registry->db->escape_string(serialize($this->thankscache)) . "'
					WHERE postid = " . $postinfo['postid'] . "
					LIMIT 1
				");

				$this->registry->db->query_write("
					UPDATE " . TABLE_PREFIX . "user
					SET thankedcount = thankedcount - 1
					WHERE userid = " . $postinfo['userid'] . "
					LIMIT 1
				");

				$this->registry->db->query_write("
					DELETE FROM " . TABLE_PREFIX . "thanks
					WHERE postid = '" . $postinfo['postid'] . "'
					AND userid = $userid
				");
				
				// needed for reputation change
				$affected_rows = 1;
			}
			else
			{
				// update database
				$this->registry->db->query_write("
					UPDATE " . TABLE_PREFIX . "post
					SET thankscache = ''
					WHERE postid = " . $postinfo['postid'] . "
					LIMIT 1
				");

				$this->registry->db->query_write("
					UPDATE " . TABLE_PREFIX . "user
					SET thankedcount = (thankedcount - " . sizeof($this->thankscache) . ")
					WHERE userid = " . $postinfo['userid'] . "
					LIMIT 1
				");

				$this->registry->db->query_write("
					DELETE FROM " . TABLE_PREFIX . "thanks
					WHERE postid = '" . $postinfo['postid'] . "'
				");
				
				// needed for reputation change
				$affected_rows = sizeof($this->thankscache);
				
				// update thanks cache
				$this->thankscache = array();
			}
			
			// execute reputation change
			$this->thanks_reputation($postinfo['userid'], $affected_rows, 'minus');
		}
		
		// return function
		return $return;
	}
	
	/**
	* Integrated Thank-You Engine ® - Changes a user's reputation, with accordance to settings.
	*
	* @var integer	User ID of the user whose reputation needs to be changed.
	* @var integer	The multiple of effect.
	* @var string	Action, which presently can be either plus or minus.
	*
	* @return boolean
	*/
	function thanks_reputation($userid, $quantity = 1, $action = 'plus')
	{
		if (!$this->registry->options['hidetag_reputation'])
		{
			return false;
		}

		$userid = intval($userid);
		$quantity = intval($quantity);
		
		if (!$userid OR !$quantity)
		{
			return false;
		}
		else
		{
			$userinfo = $this->registry->db->query_first("
				SELECT userid, reputation, reputationlevelid
				FROM " . TABLE_PREFIX . "user
				WHERE userid = $userid
				LIMIT 1
			");

			if ($userinfo)
			{
				if ($action == 'plus')
				{
					$userinfo['reputation'] = $userinfo['reputation'] + $this->registry->options['hidetag_reputation'] * $quantity;
				}
				else
				{
					$userinfo['reputation'] = $userinfo['reputation'] - $this->registry->options['hidetag_reputation'] * $quantity;
				}				

				$reputationlevel = $this->registry->db->query_first("
					SELECT reputationlevelid
					FROM " . TABLE_PREFIX . "reputationlevel
					WHERE minimumreputation <= $userinfo[reputation]
					ORDER BY minimumreputation DESC LIMIT 1
				");
									
				if ($reputationlevel)
				{
					$this->registry->db->query_write("
						UPDATE " . TABLE_PREFIX . "user
						SET
							reputation = $userinfo[reputation],
							reputationlevelid = $reputationlevel[reputationlevelid]
						WHERE
							userid = $userinfo[userid]
						LIMIT 1
					");
				}
			}
			
			return true;
		}
	}

	/**
	* Integrated Thank-You Engine ® - Constructs 'Thank You' data for a single post.
	*
	* @var &array	Postinfo
	*
	* @return string	Constructed HTML
	*/
	function build_thanks(&$postinfo)
	{
		// globalize variables
		global $vbulletin, $vbphrase, $show;
		global $post_thanks_count, $user_thanks_count;
		
		// sanitize it
		if (!is_array($postinfo))
		{
			return '';
		}
		
		// initialize thankscache
		$this->set_thankscache($postinfo['thankscache']);
		
		// initialize variables
		$post_thanks_count = sizeof($this->thankscache);
		$user_thanks_count = intval($postinfo['thankedcount']);
		
		// verify permissions
		if (isset($this->registry->bf_ugp['hhroptions']))
		{
			$candeleteownthanks = ($this->registry->userinfo['permissions']['hhroptions'] & $this->registry->bf_ugp['hhroptions']['can_delete_own_thanks']);
			$candeleteoththanks = ($this->registry->userinfo['permissions']['hhroptions'] & $this->registry->bf_ugp['hhroptions']['can_delete_oth_thanks']);
			
			$show['delete_mass_thanks'] = ($candeleteownthanks AND $candeleteoththanks);
		}
		else
		{
			$candeleteownthanks = false;
			$candeleteoththanks = false;
			
			$show['delete_mass_thanks'] = false;
		}

		// construct post data
		$thanks_bit = '';
		if ($post_thanks_count)
		{
			$show['thanks_bit'] = true;	
			if (!$vbulletin->options['hidetag_max_thankers'])
			{
				$thanks_bit = '';
			}
			else if ($vbulletin->options['hidetag_max_thankers'] != -1 AND $post_thanks_count > $vbulletin->options['hidetag_max_thankers'])
			{
				$thanks_bit = $vbphrase['hidetag_thankers_too_many_to_display'];
			}
			else
			{
				foreach ($this->thankscache as $userid => $thanks)
				{
					if (isset($this->registry->bf_ugp['hhroptions']))
					{
						$show['delete_thanks'] = (
							($show['delete_mass_thanks']) OR
							($userid == $this->registry->userinfo['userid'] AND $candeleteownthanks) OR
							($userid != $this->registry->userinfo['userid'] AND $candeleteoththanks)
						);
					}
					else
					{
						$show['delete_thanks'] = false;
					}

					$thanks['dateline'] = vbdate($this->registry->options['dateformat'], $thanks['dateline'], true, true);
					eval('$thanks_bit .= "' . fetch_template('thanks_bit') . '";');
				}
			}
		}
		
		// return post data
		return $thanks_bit;
	}
	
	/**
	* Integrated Thank-You Engine ® - Deletes thanks from a post.
	* Is triggered internally by the deletion of posts or entire threads.
	*
	* @param &array	Postinfo
	*/
	function delete_post_thanks(&$postinfo)
	{
		// get the number of thanks for this post
		$thanks = $this->registry->db->query_first("
			SELECT COUNT(*) as X
			FROM " . TABLE_PREFIX . "thanks
			WHERE postid = " . $postinfo['postid'] . "
		");
		
		// update user table
		if ($thanks['X'] > 0)
		{
			$userid = (isset($postinfo['postuserid']) ? $postinfo['postuserid'] : $postinfo['userid']);
			$this->registry->db->query_first("
				UPDATE " . TABLE_PREFIX . "user
				SET thankedcount = thankedcount - " . $thanks['X'] . "
				WHERE userid = $userid
				LIMIT 1
			");
		}
				
		// delete thanks
		$this->registry->db->query_first("
			DELETE FROM " . TABLE_PREFIX . "thanks
			WHERE postid = " . $postinfo['postid'] . "
		");
	}
	
	/**
	* Integrated Thank-You Engine ® - Renames thanks that a user has given or deletes them.
	*
	* @param int	User ID of the user being renamed/deleted.
	* @param string	Action, which is either 'delete' or 'rename'.
	* @param string	User's new name.
	*/
	function update_delete_user_thanks($userid, $action = 'delete', $rename = '(?)')
	{
		// validate input
		$userid = intval($userid);
		$rename = ($rename != '' ? $rename : '(?)');
		if (!in_array($action, array('delete', 'rename')))
		{
			return false;
		}
	
		// fetch posts for which this user has thanked
		$getposts = $this->registry->db->query_read("
			SELECT postid FROM " . TABLE_PREFIX . "thanks
			WHERE userid = $userid
		");
		
		// construct an array of these postids
		$postids = array();
		while ($getpost = $this->registry->db->fetch_array($getposts))
		{
			$postids[] = $getpost['postid'];
		}
		
		// update the cache of these posts
		if (sizeof($postids))
		{
			// fetch posts with thankscache...
			$getposts = $this->registry->db->query_read("
				SELECT postid, thankscache FROM " . TABLE_PREFIX . "post
				WHERE postid IN (" . implode(',', $postids) . ")
			");
						
			// scan and update...
			while ($postinfo = $this->registry->db->fetch_array($getposts))
			{
				// initialize thankscache...
				$this->set_thankscache($postinfo['thankscache']);
					
				// update thankscache...
				if (isset($this->thankscache[$userid]))
				{
					switch ($action)
					{
						case 'rename':
						{
							$do_update = true;
							$this->thankscache[$userid]['username'] = $rename;
							$this->thankscache[$userid]['deleted'] = false;
						}
						break;
						case 'delete':
						{
							$do_update = true;
							$this->thankscache[$userid]['username'] = $vbphrase['n_a'];
							$this->thankscache[$userid]['deleted'] = true;
						}
						break;
						default:
					}
								
					if ($do_update)
					{
						$this->registry->db->query_write("
							UPDATE " . TABLE_PREFIX . "post
							SET thankscache = '" . $this->registry->db->escape_string(serialize($this->thankscache)) . "'
							WHERE postid = " . $postinfo['postid'] . "
							LIMIT 1
						");
					}
				}
			}
		}
	}
	
	/**
	* Integrated Thank-You Engine ® - Checks whether the post is thankable.
	*
	* @param &array	Post Info Holder
	* @param &array	Thread Info Holder
	* @param &array	Forum Info Holder
	*
	* @return boolean
	*/
	function is_thankable(&$postinfo, &$threadinfo, &$foruminfo)
	{
		// sanitize it...
		if (!is_array($postinfo) OR !is_array($threadinfo) OR !is_array($foruminfo))
		{
			return false;
		}
	
		// initialize thankscache...
		$this->set_thankscache($postinfo['thankscache']);

		// get an answer...
		if (!$this->registry->options['hidetag_thankyou_system'])
		{
			$is_thankable = false;
		}
		else if ($this->registry->userinfo['userid'] == $postinfo['userid'])
		{
			$is_thankable = false;
		}
		else if (!($this->registry->userinfo['permissions']['hhroptions'] & $this->registry->bf_ugp['hhroptions']['can_post_thanks']))
		{
			$is_thankable = false;
		}
		else if (isset($this->thankscache[$this->registry->userinfo['userid']]))
		{
			$is_thankable = false;
		}
		else if ($postinfo['postid'] != $threadinfo['firstpostid'] AND ($this->registry->options['hidetag_thanks_fponly'] OR $foruminfo['hidetag_thanks_fponly']))
		{
			$is_thankable = false;
		}
		else if (!$threadinfo['open'] AND (!$this->registry->options['hidetag_thanks_in_closed_threads'] OR !$foruminfo['hidetag_thanks_in_closed_threads']))
		{
			$is_thankable = false;
		}
		else
		{
			$is_thankable = true;
		}
		
		// come to papa...
		return $is_thankable;
	}
}

$hhr = new vS_HHR_Engine($vbulletin, $hhr_forumid);

/*======================================================================*\
|| #################################################################### ||
|| # vS-Hide Hack Resurrection (Expanded Edition) for vBulletin 3.5.x - 3.8.x by Anton Kanevsky
|| #################################################################### ||
\*======================================================================*/
?>