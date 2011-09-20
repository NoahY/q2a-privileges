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
				default:
					return null;
			}
		}

		function admin_form(&$qa_content)
		{

		//	Process form input

			$ok = null;

			if(qa_clicked('priv_save_settings')) {

					// options

				qa_opt('priv_active', (bool)qa_post_text('priv_active'));
				qa_opt('priv_user_field', (bool)qa_post_text('priv_user_field'));
				qa_opt('priv_title', qa_post_text('priv_title'));
				qa_opt('priv_hover', qa_post_text('priv_hover'));
				qa_opt('priv_hover_earned', qa_post_text('priv_hover_earned'));
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
