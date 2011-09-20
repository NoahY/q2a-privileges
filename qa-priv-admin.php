<?php
	class qa_priv_admin {

		function allow_template($template)
		{
			return ($template!='admin');
		}

		function option_default($option) {

			switch($option) {
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
