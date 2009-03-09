<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.textformattermanager.php');

	Class contentExtensionMarkItUpPreview extends AjaxPage{

		function view(){
			if (!$this->_context[0]) {
				echo __('Error: No formatter selected.');
				exit();
			}

			$value = $_POST['data'];
			if (!$value) {
				exit();
			}

			$tm = new TextformatterManager($this->_Parent);
			$formatter = $tm->create($this->_context[0]);

			if (!$formatter) {
				echo __('Error: %s text formatter does not exist.', array($this->_context[0]));
				exit();
			}

			echo $formatter->run($value);
			exit();
		}
	}

?>