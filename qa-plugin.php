<?php

/*
        Plugin Name: Privileges
        Plugin URI: https://github.com/NoahY/q2a-privileges
        Plugin Update Check URI: https://raw.github.com/NoahY/q2a-privileges/master/qa-plugin.php
        Plugin Description: Privilege Notification, etc.
        Plugin Version: 0.1
        Plugin Date: 2011-07-30
        Plugin Author: NoahY
        Plugin Author URI: 
        Plugin License: GPLv2
        Plugin Minimum Question2Answer Version: 1.4
*/


	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
			header('Location: ../../');
			exit;
	}

	function qa_priv_notification($uid, $oid, $badge_slug) {
		
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		require_once QA_INCLUDE_DIR.'qa-app-emails.php';
		
		if (QA_FINAL_EXTERNAL_USERS) {
			$publictohandle=qa_get_public_from_userids(array($uid));
			$handle=@$publictohandle[$uid];
			
		} 
		else {
			$user = qa_db_single_select(qa_db_user_account_selectspec($uid, true));
			$handle = @$user['handle'];
		}

		$subject = qa_opt('badge_email_subject');
		$body = qa_opt('badge_email_body');

		$body = preg_replace('/\^if_post_text="([^"]*)"/',($oid?'$1':''),$body); // if post text
		
		$site_url = qa_opt('site_url');
		$profile_url = qa_path_html('user/'.$handle, null, $site_url);
		


		if($oid) {
			$post = qa_db_read_one_assoc(
				qa_db_query_sub(
					'SELECT * FROM ^posts WHERE postid=#',
					$oid
				),
				true
			);
			if($post['parentid']) $parent = qa_db_read_one_assoc(
				qa_db_query_sub(
					'SELECT * FROM ^posts WHERE postid=#',
					$post['parentid']
				),
				true
			);
			if(isset($parent)) {
				$anchor = urlencode(qa_anchor($post['basetype'], $oid));

				$post_title = $parent['title'];
				$post_url = qa_path_html(qa_q_request($parent['postid'], $parent['title']), null, qa_opt('site_url'),null, $anchor);
			}
			else {
				$post_title = $post['title'];
				$post_url = qa_path_html(qa_q_request($post['postid'], $post['title']), null, qa_opt('site_url'));
			}

		}
		
		
		$subs = array(
			'^badge_name'=> qa_opt('badge_'.$badge_slug.'_name'),
			'^post_title'=> @$post_title,
			'^post_url'=> @$post_url,
			'^profile_url'=> $profile_url,
			'^site_url'=> $site_url,
		);
		
		qa_send_notification($uid, '@', $handle, $subject, $body, $subs);
	}

	qa_register_plugin_module('event', 'qa-priv-check.php','priv_check','Priv Check');

	qa_register_plugin_module('module', 'qa-priv-admin.php', 'qa_priv_admin', 'Priv Admin');

	//qa_register_plugin_module('page', 'qa-priv-page.php', 'qa_priv_page', 'Privileges');

	qa_register_plugin_layer('qa-priv-layer.php', 'Priv Layer');	

/*
	Omit PHP closing tag to help avoid accidental output
*/
