<?php
	class qa_priv_admin {

		function allow_template($template)
		{
			return ($template!='admin');
		}

		function option_default($option) {

			switch($option) {
				case 'priv_title':
					return 'Privileges';
				case 'priv_hover':
					return 'requires # points; you have % of the points required to earn this privilege';
				case 'priv_hover_earned':
					return 'requires # points; you have already earned this privilege';
				case 'priv_notify_text':
					return 'Congratulations!  You have earned the privilege "^privilege".  Visit your <a href="^profile">profile</a> to learn more.';
				case 'priv_notify_text_multi':
					return 'Congratulations!  You have earned # privileges.  Visit your <a href="^profile">profile</a> to learn more.';
				case 'priv_css':
					return '.notify-container {
	left: 0;
	right: 0;
	top: 0;
	padding: 0;
	position: fixed;
	width: 100%;
	z-index: 10000;
}
.priv-notify {
	background-color: #F6DF30;
	color: #444444;
	font-weight: bold;
	width: 100%;
	text-align: center;
	font-family: sans-serif;
	font-size: 14px;
	padding: 10px 0;
	position:relative;
}
.notify-close {
	color: #735005;
	cursor: pointer;
	font-size: 18px;
	line-height: 18px;
	padding: 0 3px;
	position: absolute;
	right: 8px;
	text-decoration: none;
	top: 8px;
}';				
				case 'priv_email_subject':
					return '[^site_title] Privilege Earned';
				case 'priv_email_body':
					return 'Congratulations!  You have earned ^single=`the privilege "^priv_name"`^multi=`# privileges` from ^site_title.

Please log in and visit your profile:

^profile_url

Thanks for your participation, 

^site_title Team';
				default:
					return null;
			}
		}

		function admin_form(&$qa_content)
		{

		//	Process form input

			$ok = null;

			if(qa_clicked('priv_save_settings')) {
				$table_exists = qa_db_read_one_value(qa_db_query_sub("SHOW TABLES LIKE '^usermeta'"),true);
				if(!$table_exists) {
					qa_db_query_sub(
						'CREATE TABLE IF NOT EXISTS ^usermeta (
						meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
						user_id bigint(20) unsigned NOT NULL,
						meta_key varchar(255) DEFAULT NULL,
						meta_value longtext,
						PRIMARY KEY (meta_id),
						UNIQUE (user_id,meta_key)
						) ENGINE=MyISAM  DEFAULT CHARSET=utf8'
					);		
				}
				
				// options

				qa_opt('priv_active', (bool)qa_post_text('priv_active'));
				qa_opt('priv_user_field', (bool)qa_post_text('priv_user_field'));
				qa_opt('priv_title', qa_post_text('priv_title'));
				qa_opt('priv_hover', qa_post_text('priv_hover'));
				qa_opt('priv_shading', (int)qa_post_text('priv_shading'));
				qa_opt('priv_hover_earned', qa_post_text('priv_hover_earned'));
				
				qa_opt('priv_css', qa_post_text('priv_css'));
				qa_opt('priv_check', (bool)qa_post_text('priv_check'));
				qa_opt('priv_notify_text', qa_post_text('priv_notify_text'));
				qa_opt('priv_notify_text_multi', qa_post_text('priv_notify_text_multi'));
				qa_opt('priv_email_notify_on', (bool)qa_post_text('priv_email_notify_on'));
				qa_opt('priv_email_subject', qa_post_text('priv_email_subject'));
				qa_opt('priv_email_body', qa_post_text('priv_email_body'));
				$ok = qa_lang_html('admin/options_saved');
			}

		//	Create the form for display


			$fields = array();

			$fields[] = array(
				'label' => 'Activate Privilege Management',
				'tags' => 'NAME="priv_active"',
				'value' => qa_opt('priv_active'),
				'type' => 'checkbox',
			);

			$fields[] = array(
				'label' => 'Show privileges in user profile',
				'tags' => 'NAME="priv_user_field"',
				'value' => qa_opt('priv_user_field'),
				'type' => 'checkbox',
			);
			
			$shading = array('color','grey');

			$fields[] = array(
				'label' => 'Privilege shading style',
				'tags' => 'NAME="priv_shading"',
				'type' => 'select',
				'options' => $shading,
				'value' => @$shading[qa_opt('priv_shading')],
			);

			$fields[] = array(
				'label' => 'Title of user privilege box',
				'tags' => 'NAME="priv_title"',
				'value' => qa_opt('priv_title'),
			);
			
			$fields[] = array(
				'label' => 'Hover text on unearned privilege name',
				'note' => '# is replaced by required points, % by percentage user already has',
				'tags' => 'NAME="priv_hover"',
				'value' => qa_opt('priv_hover'),
			);
			$fields[] = array(
				'label' => 'Hover text on earned privilege name',
				'note' => '# is replaced by required points',
				'tags' => 'NAME="priv_hover_earned"',
				'value' => qa_opt('priv_hover_earned'),
			);

			$fields[] = array(
				'type' => 'blank',
			);

			$fields[] = array(
				'label' => 'enable new privilege notification popup',
				'tags' => 'NAME="priv_check"',
				'value' => (bool)qa_opt('priv_check'),
				'type' => 'checkbox',
			);

			$fields[] = array(
				'label' => 'privilege popup text (single privilege)',
				'tags' => 'NAME="priv_notify_text"',
				'value' => qa_html(qa_opt('priv_notify_text')),
				'note' => 'substitutes ^profile for profile url, ^privilege for privilege name',
			);

			$fields[] = array(
				'label' => 'privilege popup text (multiple privileges)',
				'tags' => 'NAME="priv_notify_text_multi"',
				'value' => qa_html(qa_opt('priv_notify_text_multi')),
				'note' => 'substitutes ^profile for profile url, # for number of privileges',
			);

			$fields[] = array(
				'label' => 'enable email notification',
				'tags' => 'NAME="priv_email_notify_on"',
				'value' => (bool)qa_opt('priv_email_notify_on'),
				'type' => 'checkbox',
			);

			$fields[] = array(
				'label' => 'email subject',
				'tags' => 'NAME="priv_email_subject"',
				'value' => qa_html(qa_opt('priv_email_subject')),
			);

			$fields[] = array(
				'label' =>  'email body',
				'tags' => 'name="priv_email_body"',
				'value' => qa_html(qa_opt('priv_email_body')),
				'type' => 'textarea',
				'rows' => 20,
				'note' => 'Available replacement text:<br/><br/><i>^site_title<br/>^handle<br/>^email<br/>^open<br/>^close<br/>^profile_url<br/>^site_url<br/>^single=`text`^multi=`text`</i>',
			);

			return array(
				'ok' => ($ok && !isset($error)) ? $ok : null,

				'fields' => $fields,

				'buttons' => array(

					array(
						'label' => 'Save',
						'tags' => 'NAME="priv_save_settings"',
					),
				),
			);
		}
	}
