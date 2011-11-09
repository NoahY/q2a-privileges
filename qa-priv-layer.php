<?php

	class qa_html_theme_layer extends qa_html_theme_base {

	// theme replacement functions

		function doctype() {
			if($this->template == 'user' && qa_get_logged_in_handle() === $this->_user_handle()) {
				if(!isset($this->content['navigation']['sub'])) {
					$this->content['navigation']['sub'] = array(
						'profile' => array(
							'url' => qa_path_html('user/'.$this->_user_handle(), null, qa_opt('site_url')),
							'label' => $this->_user_handle(),
							'selected' => !qa_get('tab')?true:false
						),
						'privileges' => array(
							'url' => qa_path_html('user/'.$this->_user_handle(), array('tab'=>'privileges'), qa_opt('site_url')),
							'label' => qa_opt('priv_title'),
							'selected' => qa_get('tab')=='privileges'?true:false
						),
					);
				}
				else {
					$this->content['navigation']['sub']['privileges'] = array(
						'url' => qa_path_html('user/'.$this->_user_handle(), array('tab'=>'privileges'), qa_opt('site_url')),
						'label' => qa_opt('priv_title'),
						'selected' => qa_get('tab')=='privileges'?true:false
					);
				}
			}
			qa_html_theme_base::doctype();
		}
		
		function main_parts($content)
		{
			if (qa_opt('priv_active') && $this->template == 'user' && qa_get('tab')=='privileges') { 
					$content = array();
					$content['form-privileges-list'] = $this->user_priv_form();  // this shouldn't happen
			}

			qa_html_theme_base::main_parts($content);

		}
		
		var $notify = '';

		function head_custom() {
			qa_html_theme_base::head_custom();

			if (qa_opt('priv_active') && qa_opt('priv_check') && qa_get_logged_in_handle()) {
				
				$userid = qa_get_logged_in_userid();
				
				$notices = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT meta_value FROM ^usermeta WHERE user_id=# AND meta_key=$ ',
						$userid,'priv_notify'
					),
					true
				);
				
				if($notices) {

					$n = explode(',',$notices);
					$this->notify = '<div class="notify-container">';
					
					$text = count($n)>1?str_replace('#',count($n),qa_opt('priv_notify_text_multi')):str_replace('^privilege',qa_lang('profile/'.$n[0]),qa_opt('priv_notify_text'));
					
					$text = str_replace('^profile',qa_path_html('user/'.qa_get_logged_in_handle(),array('tab'=>'privileges'),qa_opt('site_url')),$text);
						
					$this->notify .= '<div class="priv-notify notify">'.$text.'<div class="notify-close" onclick="jQuery(this).parent().hide(\'slow\')">x</div></div>';
					 
					$this->notify .= '</div>';
					
					// remove notification flag
					
					qa_db_query_sub(
						'DELETE FROM ^usermeta WHERE user_id=# AND meta_key=$',
						$userid, 'priv_notify'
					);
/*					
					$this->output("
					<script>
						jQuery('document').ready(function() { jQuery('.notify-container').delay(10000).fadeOut(); });
					</script>");
*/
					$this->output('
					<style>',qa_opt('priv_css'),'</style>');
				}
			}
		}
		
		function body_prefix() {
			qa_html_theme_base::body_prefix();
			
			if ($this->notify) {
				$this->output($this->notify);
			}
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

				if($ppoints == 100) {
					$hover = str_replace('#',$val,qa_opt('priv_hover_earned'));
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
	
