<?php

	class priv_check {
		
// main event processing function
		
		function process_event($event, $userid, $handle, $cookieid, $params) {
			if(qa_opt('priv_check') && in_array($event,array('q_post','a_post','c_post','q_claim','a_claim','c_claim','a_select','q_vote_up','a_vote_up'))) {
				$this->check_privilege($event,$userid,$params);
			}
		}
// worker functions

		
		function award_badge($object_id, $user_id, $badge_slug, $badge_badge = false) {
			
			// add badge to userbadges
			
			qa_db_query_sub(
				'INSERT INTO ^userbadges (awarded_at, notify, object_id, user_id, badge_slug, id) '.
				'VALUES (NOW(), #, #, #, #, 0)',
				1, $object_id, $user_id, $badge_slug
			);
			qa_badge_notification($user_id, $object_id, $badge_slug);		
			// check for sheer number of badges, unless this badge was for number of badges (avoid recursion!)
			if(!$badge_badge) $this->check_badges($user_id);
		}

		function priviledge_notify($object_id, $user_id, $badge_slug) {
			
		}
		
		function get_post_data($id) {
			$result = qa_db_read_one_assoc(
				qa_db_query_sub(
					'SELECT * FROM ^posts WHERE postid=#',
					$id
				),
				true
			);
			return $result;
		}

		function check_privilege($event,$userid,$params) {

			// points

			require_once QA_INCLUDE_DIR.'qa-db-points.php';

			$optionnames=qa_db_points_option_names();
			$options=qa_get_options($optionnames);
			$multi = (int)$options['points_multiple'];
			
			switch($event) {
				case 'q_post':
					$event_points = (int)$options['points_post_q']*$multi;
					break;
				case 'a_post':
					$event_points = (int)$options['points_post_a']*$multi;
					break;
				case 'a_select':
					$event_points = (int)$options['points_select_a']*$multi;
					break;
				case 'q_vote_up':
					$event_points = (int)$options['points_vote_up_q']*$multi;
					break;
				case 'a_vote_up':
					$event_points = (int)$options['points_vote_up_a']*$multi;
					break;
				default:
					return;
			}
				
			if(in_array($event,array('a_select','q_vote_up','a_vote_up'))) { // other user
				$userid = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT userid FROM ^posts WHERE postid=#',
						$params['postid']
					),
					true
				);			
				if(!isset($userid)) return;
			}

			$p_options = qa_get_permit_options();
			
			$user = qa_db_select_with_pending(qa_db_user_points_selectspec($userid,true));
			$upoints = (int)$user['points'];
			$before_points = (int)$user['points']-$event_points;
			
			$notices = '';
			
			foreach ($p_options as $option) {
				if(qa_opt($option) == QA_PERMIT_POINTS) {
					$opoints = (int)qa_opt($option.'_points');
					if($opoints < $upoints && $opoints > $before_points) {
						$notices = ($notices?$notices.',':'').$option;
					}
				}
			}
			if(!$notices) return;
			qa_db_query_sub(
				'INSERT INTO ^usermeta (user_id,meta_key,meta_value) VALUES (#,$,$) ON DUPLICATE KEY UPDATE meta_value=$',
				$userid,'priv_notify',$notices,$notices
			);
			if(qa_opt('priv_email_notify_on'))
				$this->notify($userid, $notices);			
		}


		function notify($uid,$notices) {
			
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
			
			$subject = qa_opt('priv_email_subject');
			$body = qa_opt('priv_email_body');
			
			$n = explode(',',$notices);
			
			if(count($n)>1) {
				$body = preg_replace('/\^single=`([^`]+)`/','',$body);
				preg_match('/\^multi=`([^`]+)`/',$body,$multi);
				$m = str_replace('#',count($n),$multi[1]);
				$body = preg_replace('/\^multi=`([^`]+)`/',$m,$body);
			}
			else {
				$body = preg_replace('/\^single=`([^`]+)`/','$1',$body);
				$body = preg_replace('/\^multi=`([^`]+)`/','',$body);
			}
			
			$site_url = qa_opt('site_url');
			$profile_url = qa_path_html('user/'.$handle, null, $site_url);

			$subs = array(
				'^profile_url'=> $profile_url,
				'^site_url'=> $site_url,
			);
			qa_send_notification($uid, '@', $handle, $subject, $body, $subs);
		}
		
	}
