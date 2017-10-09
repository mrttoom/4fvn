<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.0.1
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 01:27 27-07-2008
|| #################################################################### ||
\*======================================================================*/
global $vbulletin;
if (defined('VB_AREA')) {
	if (!isset($vbulletin->kBankHide)) {
		class kBankHide
		{
			var $registry = null;
			var $tags = array(
				0 => array(
					'name' => 'HIDE-THANKS',
					'function' => 'parse_bbcode_thanks'
				)
			);
			var $settings = array(
				'banned' => false,
				'dohtml' => false,
			);
			var $vars = array(
				'regex' => "",
			);

			function kBankHide(&$registry) {
				//create new object
				if (is_object($registry)) {
					$this->registry =& $registry;
				} else {
					trigger_error("vB_Database::Registry object is not an object", E_USER_ERROR);
				}
				
				//Check if user is banned
				$is_banned = (!($this->registry->usergroupcache[$this->registry->userinfo['usergroupid']]['genericoptions'] & $this->registry->bf_ugp_genericoptions['isnotbannedgroup']) ? true : false);			
				
				if (is_member_of($this->registry->userinfo, 3, 4) OR $is_banned OR THIS_SCRIPT == 'misc') {
					$this->settings['banned'] = true;
				}
				
				//Check if we need to use html version
				if (in_array(
					THIS_SCRIPT
					,array(
						'ajax' //Post new
						, 'editpost' //Editing (preview)
						, 'misc'
						, 'newthread' //New thread (preview)
						, 'newreply' //New reply (preview)
						, 'printthread' //Print thread
						, 'showpost' //Show post
						, 'showthread' //Show thread
						)
					)
				) {
					$this->settings['dohtml'] = true; //Let's use html!
				}
				else
				{
					$this->settings['dohtml'] = false; //Plain text only
				}

				if (isset($this->registry->kbank['hide']['shortcut'])) {
					foreach ($this->tags as $key => $tag) {
						if (strtolower($tag['name']) == strtolower($this->registry->kbank['hide']['shortcut'])) {
							$this->tags[$key]['name'] .= '|HIDE';
						}
					}
				}
				
				//Prepair regex
				foreach ($this->tags as $key => $tag) {
					$this->tags[$key]['regex'] = "\[($tag[name])(=(&quot;|\"|'|)?([0-9]+)\\3)?\]((.|\r|\n)*)\[\/\\1\]";
					//$this->tags[$key]['regex_close'] = "\[\/($tag[name])\]"; - not needed
					$this->tags[$key]['regex_open'] = "\[($tag[name])(=(&quot;|\"|'|)?([0-9]+)\\3)?\]";
				}
			}
			
			function parse_bbcode($message, $forumid, $threadid, $postid, $userid, $oldmethod = false) {
				//main function parse all of our bbcodes
				global $vbphrase, $stylevar;
				
				//Cache important info
				$this->vars = array(
					'forumid' => intval($forumid),
					'threadid' => intval($threadid),
					'postid' => intval($postid),
					'userid' => intval($userid)
				);
				
				//Parse through the tags
				foreach ($this->tags as $tag) {
					if (strlen($message) > 0 //just for safe
						AND isset($tag['function'])) {
						if ($oldmethod) {
							//using old method which is faster but risky! Cause a lot of server errors
							$message = preg_replace("/$tag[regex]/esiU", "\$this->$tag[function]('\\5', intval('\\4'))", $message);
						} else if ($tag['regex_open']) {
							//new method, safer
							$status = 0;
							/*status available values
							1: inside something
							0: none
							99: finish searching - exit code
							*/
							$offset = 0;
							while ($offset < strlen($message)
								AND $status !== 99) {
								if ($status === 0) {
									//looking for open tag
									if (preg_match("/$tag[regex_open]/i",$message,$matches,0,$offset)) {
										//store data
										$tagname = $matches[1];
										$options = $matches[4];
										//update offset
										$tagstart = strpos($message,$matches[0],$offset);
										$datastart = $offset = $tagstart + strlen($matches[0]);
										//update status
										$status = 1;
									} else {
										//update status with exit code
										$status = 99;
									}
								} else if ($status === 1) {
									//looking for close tag
									if (preg_match("/\[\/$tagname\]/i",$message,$matches,0,$offset)) {										
										//update offset
										$dataend = strpos($message,$matches[0],$offset) - 1;
										$tagend = $offset = $dataend + 1 + strlen($matches[0]);
										//process
										eval('$processed_tmp = $this->' . $tag['function'] . '(substr($message,$datastart,$dataend - $datastart + 1),$options);');
										$message = substr_replace($message,$processed_tmp,$tagstart,$tagend - $tagstart);
										//manually update offset
										$offset = $tagstart + strlen($processed_tmp);
										//update status
										$status = 0;
									} else {
										//update status with exit code
										$status = 99;
									}
								}
							}
						}
					}
				}
				return $message;
			}
			
			/*Function to handle BBCode. Format
			#########################
			function parse_bbcode_something($message,$option) {
				$message is variable to store the message to parse
				$option store option information (if any)
				
				IMPORTANT: Remember to return $message after processing!!!! 
			}
			*/
			
			//Function to handle HIDE-THANKS
			function parse_bbcode_thanks($message, $thanks_required) {
				global $vbphrase, $stylevar;
				
				//prepair variables
				include_once(DIR . '/kbank/award_functions.php'); //include kBank Award functions
				$thanks = fetchAwarded($this->vars['postid'],true,false,$this->registry->userinfo['userid']);
				if ($this->registry->kbank['hide']['thanksMax'] != 0) {
					//there is a limit for maximum required thank amount
					$thanks_required = min($thanks_required,$this->registry->kbank['hide']['thanksMax']);
				}
				
				//fix " issue
				$message = str_replace('\"', '"', $message);
				
				//check permission
				$canview = false;
				if (!$this->settings['banned']) {
					if ($this->can_override()) {
						$canview = true;
					} else {
						if ($this->registry->userinfo['userid'] 
							AND is_numeric($thanks[$this->registry->userinfo['userid'] ]['points'])
							AND $thanks[$this->registry->userinfo['userid'] ]['points'] >= $thanks_required) {
							$canview = true;
						}
					}
				}
				
				//build info message
				if ($thanks_required) {
					$info_message = construct_phrase($vbphrase['kbank_hide_thanks_required_with_amount'], $thanks_required, $this->registry->kbank['name']);
				} else {
					$info_message = construct_phrase($vbphrase['kbank_hide_thanks_required']);
				}
				
				//prepair output
				if ($this->settings['dohtml']) {
					eval('$message = "' . fetch_template('kbank_hide_replacement_thanks') . '";');
				} else {
					$message = ($canview ? $message : construct_phrase($vbphrase['kbank_hide_message_nohtml'], $info_message));
				}
				
				//Everything done! Return result
				return $message;
			}
			
			function strip_bbcode($message, $place = '') {
				//function to remove our bbcode from message
				global $vbphrase, $stylevar;

				//choose appropriate phrase
				switch ($place) {
					case 'editor': //in editor
					case 'archive': //in archive
					case 'postpreview': //preview post (searching)
						//just strip off!
						$replacement = $vbphrase['kbank_hide_stripped'];
						break;
					case 'email': //email subscription
					case 'rss': //external output
						//with refer message
						$replacement = $vbphrase['kbank_hide_stripped_refer'];
						break;
					default:
						$replacement = '';
						break;
				}

				if (!($place == 'dopost')) {
					//process through $this->tags
					foreach ($this->tags as $tag) {
						$message = preg_replace("/$tag[regex]/siU", $replacement, $message);
					}
				}
				
				//everything done! Return result
				return $message;
			}
			
			//function to check override permissions
			function can_override() {
				if (
					(in_array(THIS_SCRIPT, array('editpost', 'newthread'))) //editing/posting
					OR (THIS_SCRIPT == 'newreply' AND !$this->vars['postid']) //preview
					OR ($this->registry->userinfo['userid'] AND $this->registry->userinfo['userid'] == $this->vars['userid']) //user is the poster of this post
					OR can_moderate($this->vars['forumid'], 'caneditposts') //user have moderating permisison
				) {
					return true;
				}
				
				return false;
			}
		}

		//create our object in vBulletin global object
		$vbulletin->kBankHide = new kBankHide($vbulletin);
	}
}
?>