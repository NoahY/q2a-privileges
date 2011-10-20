<?php

	class qa_html_theme_layer extends qa_html_theme_base {

	// theme replacement functions


		function main_parts($content)
		{
			if (qa_opt('priv_active')) {
				if($this->template == 'user') {

					if((bool)qa_opt('priv_user_field')) { 
					// add user badge list

						if($content['q_list']) {  // paranoia
						
							$keys = array_keys($content);
							$vals = array_values($content);

							$insertBefore = array_search('q_list', $keys);

							$keys2 = array_splice($keys, $insertBefore);
							$vals2 = array_splice($vals, $insertBefore);

							$keys[] = 'form-priv-list';
							$vals[] = $this->user_priv_form();

							$content = array_merge(array_combine($keys, $vals), array_combine($keys2, $vals2));
						}
						else $content['form-priv-list'] = $this->user_priv_form();  // this shouldn't happen
					}
				}
					
			}

			qa_html_theme_base::main_parts($content);

		}


// worker functions

	// layout

		function user_priv_form() {
			// displays badge list in user profile
			
			global $qa_request;
			
			$handle = preg_replace('/^[^\/]+\/([^\/]+).*/',"$1",$qa_request);
			
			$userid = $this->priv_getuserfromhandle($handle);
			
			if(!$userid) return;

			$options = qa_get_permit_options();
			
			$user = qa_db_select_with_pending(qa_db_user_points_selectspec($userid,true));
			$upoints = (int)$user['points'];
			foreach ($options as $option) {
				if(qa_opt($option) == QA_PERMIT_POINTS) {
					$opoints = (int)qa_opt($option.'_points');
					$popts[$option] = $opoints;
				}
			}
			if(!isset($popts)) return;
			
			arsort($popts);

			$fields = array();
			foreach ($popts as $key => $val) {
					if ($upoints > $val)
						$ppoints = 100;
					else 
						$ppoints = round($upoints/$val*100);
			// shading

				if(qa_opt('priv_shading') == 0) {

					if ($ppoints <= 50) {
						$col = round($ppoints/50*255);
						$col = dechex($col);
						if (strlen($col) == 1) $col = '0'.$col;
						$col = '#'. 'FF' . $col . '00';
					}
					else {
						$col = round(($ppoints - 50)/50*255)*(-1)+255;
						$col = dechex($col);
						if (strlen($col) == 1) $col = '0'.$col;
						$col = '#' . $col .'FF' . '00';
					}
				}
				else {
					$col = (255-round($ppoints/100*255))*3/4;
					$col = dechex($col);
					if (strlen($col) == 1) $col = '0'.$col;
					$col = '#' . $col .$col . $col;
				}
				
			// hover text

				error_log($val);
				if($ppoints == 100) {
					$hover = str_replace('#',$val,qa_opt('priv_hover_earned'));
					error_log($hover);
				}
				else {
					$hover = str_replace('#',$val,qa_opt('priv_hover'));
					$hover = str_replace('%',$ppoints,$hover);
				}

				$text[] = ($ppoints == 100? '<b ':'<font ').'title="'.$hover.'" style="color:'.$col.'; cursor:pointer">'.qa_lang('profile/'.$key).'</td><td class="qa-form-tall-label">'.($ppoints == 100? '<b ':'<font ').'title="'.$hover.'" style="color:'.$col.'; cursor:pointer">'.$ppoints.'%';
			}
			$fields[] = array(
					'label' => implode('</td></tr><tr class="priv-row"><td class="qa-form-tall-label">',$text),
					'type' => 'static',
			);

			$ok = null;
			$tags = null;
			$buttons = array();
			
			return array(				
				'ok' => ($ok && !isset($error)) ? $ok : null,
				'style' => 'tall',
				'tags' => $tags,
				'title' => qa_opt('priv_title'),
				'fields'=>$fields,
				'buttons'=>$buttons,
			);
			
		}

	// badge popup notification

		function priv_notify() {
			$userid = qa_get_logged_in_userid();
			
			$result = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND notify>=1',
					$userid
				)
			);
			
			if(count($result) > 0) {

				$notice = '<div class="notify-container">';
				
				// populate notification list
				foreach($result as $slug) {
					$badge_name=qa_badge_lang('badges/'.$slug);
					if(!qa_opt('badge_'.$slug.'_name')) qa_opt('badge_'.$slug.'_name',$badge_name);
					$name = qa_opt('badge_'.$slug.'_name');
					
					$notice .= '<div class="badge-notify notify">'.qa_badge_lang('badges/badge_notify')."'".$name.'\'!&nbsp;&nbsp;'.qa_badge_lang('badges/badge_notify_profile_pre').'<a href="'.qa_path_html('user/'.qa_get_logged_in_handle()).'">'.qa_badge_lang('badges/badge_notify_profile').'</a><div class="notify-close" onclick="jQuery(this).parent().hide(\'slow\')">x</div></div>';
				}

				$notice .= '</div>';
				
				// remove notification flag
				
				qa_db_query_sub(
					'UPDATE ^userbadges SET notify=0 WHERE user_id=# AND notify>=1',
					$userid
				);
				$this->output($notice);
			}
		}

		function priv_getuserfromhandle($handle) {
			require_once QA_INCLUDE_DIR.'qa-app-users.php';
			
			if (QA_FINAL_EXTERNAL_USERS) {
				$publictouserid=qa_get_userids_from_public(array($handle));
				$userid=@$publictouserid[$handle];
				
			} 
			else {
				$userid = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT userid FROM ^users WHERE handle = $',
						$handle
					),
					true
				);
			}
			if (!isset($userid)) return;
			return $userid;
		}
		
	}
	
