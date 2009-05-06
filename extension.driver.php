<?php
	Class extension_markitup extends Extension{

		public function about(){
			return array('name' => 'markItUp!',
						 'version' => '1.0',
						 'release-date' => '2009-03-09',
						 'author' => array('name' => 'Marcin Konicki',
										   'website' => 'http://ahwayakchih.neoni.net',
										   'email' => 'ahwayakchih@neoni.net'),
						 'description' => __('Add some basic text editor buttons to every textarea that is using one of supported formatters.')
			);
		}

		function install(){
			if (!$this->_Parent->Database->query('
				CREATE TABLE IF NOT EXISTS tbl_markitup_fields (
					field_id int unsigned,
					section_id int unsigned,
					markup varchar(32),
					PRIMARY KEY (field_id),
					INDEX section_id (section_id)
				)
			')) return false;

			if (!$this->_Parent->Database->query('
				CREATE TABLE IF NOT EXISTS tbl_markitup_cache (
					section_id int unsigned,
					data text,
					PRIMARY KEY (section_id)
				)
			')) return false;

			$supported = array(
				'pb_markdown' => 'markdown', // Markdown extension
				'pb_markdownextra' => 'markdown', // Markdown extension
				'pb_markdownextrasmartypants' => 'markdown', // Markdown extension
				'ta_typogrifymarkdown' => 'markdown', // Typogrify extension
				'ta_typogrifymarkdownextra' => 'markdown', // Typogrify extension
				'ta_typogrifytextile' => 'textile', // Typogrify extension
				'textile' => 'textile', // Textile extension
				'pb_html_complete' => 'html', // HTML extension
				'pb_html_restricted' => 'html', // HTML extension
				'pb_bbcode_modern' => 'bbcode', // BBCode extension
				'pb_bbcode_traditional' => 'bbcode', // BBCode extension
			);

			$fields = $this->_Parent->Database->fetch('
				SELECT f.parent_section, ft.field_id, ft.formatter
				FROM tbl_fields f, tbl_fields_textarea ft
				WHERE f.id = ft.field_id
			');

			if ($fields) {
				foreach ($fields as $data) {
					if (isset($supported[$data['formatter']]) && file_exists(EXTENSIONS.'/markitup/assets/markitup/sets/'.$supported[$data['formatter']])) {
						$this->_Parent->Database->query('
							INSERT INTO tbl_markitup_fields VALUES ('
								.$data['field_id'].','
								.$data['parent_section'].',"'
								.$supported[$data['formatter']].'"
							)
						');
					}
				}
			}

			foreach ($supported as $formatter => $type) {
				$this->_Parent->Configuration->set($formatter, $type, 'markitup');
			}

			return $this->_Parent->saveConfig();
		}

		public function uninstall() {
			$this->_Parent->Database->query('DROP TABLE IF EXISTS tbl_markitup_fields');
			$this->_Parent->Database->query('DROP TABLE IF EXISTS tbl_markitup_cache');

			$files = General::listStructure(CACHE, array('css', 'js'), false, 'asc');
			if (empty($files['filelist'])) return true;

			foreach ($files['filelist'] as $file) {
				if (strpos(CACHE."/$file", 'markitup_') !== false) General::deleteFile(CACHE."/$file");
			}

			$this->_Parent->Configuration->remove('markitup');
			return $this->_Parent->saveConfig();
		}

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'ModifyTextareaFieldPublishWidget',
					'callback' => '__publishPanelInject',
				),
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => '__missingDelegateWorkaround',
				),
				array(
					'page' => '/administration/',
					'delegate' => 'AdminPagePreGenerate',
					'callback' => '__publishPanelJS',
				),
				array(
					'page' => '/administration/',
					'delegate' => 'AdminPagePostGenerate',
					'callback' => '__settingsPanelInject',
				),
			);
		}

		public function __publishPanelInject($ctx) {
			// context array contains: &$field, &$label, &$textarea

			$Admin = Administration::instance();
			if (!isset($Admin->markitup)) $Admin->markitup = array();

			$value = $Admin->Database->fetchVar('markup', 0, '
				SELECT markup
				FROM tbl_markitup_fields	
				WHERE field_id = '.$ctx['field']->get('id').' AND section_id = '.$ctx['field']->get('parent_section')
			);
			if (!trim($value)) return;

			$supported = $Admin->Configuration->get('markitup');

			$formatter = $ctx['field']->get('formatter');
			if (!isset($supported[$formatter])) return;

			$set = $supported[$formatter];
			if (!in_array($set, $Admin->markitup)) {
				$Admin->markitup[] = $set;
			}

			if ($set != 'textile') {
				$ctx['textarea']->setAttribute('class', $ctx['textarea']->getAttribute('class').' markItUp '.$set);
			}
			else {
				$ctx['textarea']->setAttribute('class', $ctx['textarea']->getAttribute('class').' markItUp');
			}
		}

		// Adds script and css to head right before page rendering.
		// That way we're sure that no more textareas will be generated and it is safe to generate cached JS and CSS files.
		public function __publishPanelJS($ctx) {
			// context array contains: &$oPage
			$Admin = Administration::instance();
			if (!is_array($Admin->markitup) || empty($Admin->markitup)) return;

			$fileMode = $Admin->Configuration->get('write_mode', 'file');

			asort($Admin->markitup);
			$path = CACHE.'/markitup_'.md5(implode(',', $Admin->markitup));

			$cacheJS = $path.'.js';
			$cacheCSS = $path.'.css';
			if (file_exists($cacheJS)) $cacheJSTime = filemtime($cacheJS);
			if (file_exists($cacheCSS)) $cacheCSSTime = filemtime($cacheCSS);

			$path = EXTENSIONS.'/markitup/assets/markitup/sets';
			$refresh = false;
			foreach ($Admin->markitup as $type) {
				if (file_exists("{$path}/{$type}/set.js") && filemtime("{$path}/{$type}/set.js") > $cacheJSTime) {
					$refresh = true;
					break;
				}
				if (file_exists("{$path}/{$type}/style.css") && filemtime("{$path}/{$type}/style.css") > $cacheCSSTime) {
					$refresh = true;
					break;
				}
			}

			if ($refresh) {
				$supported = $Admin->Configuration->get('markitup');

				$data = '';
				$data .= "\n\t\tvar markItUpFormatters = {";
				foreach ($supported as $format => $class) {
					$data .= "\n\t\t\t'{$format}': '{$class}',";
				}
				$data .= "\n\t\t};\n";
				$data .= "\n\t\tvar markItUpFormattersRegex = /(^| )(".implode('|', array_keys($supported)).')( |$)/;';
				$data .= "\n\n";

				foreach ($Admin->markitup as $type) {
					$data .= $this->convertJS(file_get_contents("{$path}/{$type}/set.js"), $type);
					$data .= "\n\n";
				}

				$data = file_get_contents(EXTENSIONS.'/markitup/assets/jquery.pack.js')
					.";\n\n"
					.file_get_contents(EXTENSIONS.'/markitup/assets/markitup/jquery.markitup.pack.js')
					.";\n\n"
					.'
(function($) { 
'.$data.'
	$(document).ready(function() {
		$(".markItUp").each(function(){
			var formatter = this.className.match(markItUpFormattersRegex);
			if (!formatter[2]) return;
			formatter = formatter[2];

			var c = markItUpFormatters[formatter];
			if (c != "default") {
				myMarkItUpSettings[c].previewParserPath = "'.URL.'/symphony/extension/markitup/preview/"+formatter+"/";
				myMarkItUpSettings[c].previewParserVar = "data";
			}
			$(this).markItUp(myMarkItUpSettings[c]);

		});
	});
})(jQuery);
jQuery.noConflict();
';

				if (!General::writeFile($cacheJS, $data, $fileMode)) {
					$script = new XMLElement('script', $data, array('type' => 'text/javascript'));
					$script->setSelfClosingTag(false);
					$ctx['oPage']->addElementToHead($script, 1001);
					$cacheJS = NULL;
				}
			}

			if ($cacheJS) {
				$ctx['oPage']->addScriptToHead(str_replace(DOCROOT, URL, $cacheJS), 1001);
			}

			if ($refresh) {
				$data = file_get_contents(EXTENSIONS.'/markitup/assets/markitup/skins/symphony/style.css')."\n";
				$url = str_replace(DOCROOT, URL, $path);
				foreach ($Admin->markitup as $type) {
					$data .= $this->convertCSS(file_get_contents("{$path}/{$type}/style.css"), $type, "{$url}/{$type}");
				}

				if (!General::writeFile($cacheCSS, $data, $fileMode)) {
					$style = new XMLElement('style', $data, array('type' => 'text/css', 'media' => 'screen'));
					$ctx['oPage']->addElementToHead($style, 1002);
					$cacheCSS = NULL;
				}
			}

			if ($cacheCSS) {
				$ctx['oPage']->addStylesheetToHead(str_replace(DOCROOT, URL, $cacheCSS), 'screen', 1002);
			}
		}

		// TODO: whole function is one, big hack. It should be replaced with something clean, when Symphony will implement "field saved" delegate.
		public function __missingDelegateWorkaround($ctx) {
			$page = $_GET['page'];
			if (!preg_match('%^blueprints/sections/(edit/(\d+)|new)(/|$)%', $page, $temp)) return;

			$section_id = intval($temp[2]);

			if (!$section_id) return;

			$lastPost = $this->_Parent->Database->fetchRow(0, 'SELECT * FROM tbl_markitup_cache WHERE section_id = '.$section_id);
			if ($lastPost) {
				$lastPost = unserialize($lastPost['data']);
				$markup = array();
				foreach ($lastPost as $position => $data) {
					if ($data['type'] == 'textarea' && isset($data['markitup'])) {
						$name = Lang::createHandle($data['label'], NULL, '-', false, true, array('@^[\d-]+@i' => ''));
						$markup[$name] = $data['markitup'];
					}
				}

				$fields = $this->_Parent->Database->fetch('
					SELECT * 
					FROM tbl_fields 
					WHERE parent_section = '.$section_id.' AND element_name IN (\''.implode("','", array_keys($markup)).'\')'
				);
				if ($fields) {
					$this->_Parent->Database->query('DELETE FROM tbl_markitup_fields WHERE section_id = '.$section_id);
					foreach ($fields as $field) {
						$this->_Parent->Database->query('
							INSERT INTO tbl_markitup_fields 
							VALUES ('.$field['id'].','.$section_id.',"'.$markup[$field['element_name']].'")
						');
					}
				}
				$this->_Parent->Database->query('DELETE FROM tbl_markitup_cache WHERE section_id = '.$section_id);
			}

			if (!$_POST['fields'] || isset($_POST['action']['delete'])) return;

			$this->_Parent->Database->query('INSERT INTO tbl_markitup_cache VALUES ('.$section_id.', \''.$this->_Parent->Database->cleanValue(serialize($_POST['fields'])).'\')');
		}

		// A bit ugly, but seems to work ok. At least until more extensions start using the same trick and conflict with each other :(.
		// It depends on missingDelegateWorkaround hack, so maybe there will be a way to get this functionality without workaround in future.
		public function __settingsPanelInject($ctx) {
			// context array contains: &$output
			$page = $_GET['page'];
			if (!preg_match('%^blueprints/sections/(edit/(\d+)|new)(/|$)%', $page, $temp)) return;

			$section_id = intval($temp[2]);

			if (!preg_match_all('%<li>\s*<h4>Textarea</h4>(?:[\w\W]+) name=[\'"]fields\[(\d+)\]\[id\][\'"](?:[\w\W]+) value=[\'"](\d+)[\'"](?:[\w\W]+)</li>%iU', $ctx['output'], $textareas)) return;

			$sets = General::listStructure(EXTENSIONS.'/markitup/assets/markitup/sets', NULL, false, 'asc');
			if (!$sets) return;

			$supported = $this->_Parent->Configuration->get('markitup');

			$options = '<option value=""></option>';
			foreach ($sets['dirlist'] as $set) {
				if ($set == 'default' || !in_array($set, $supported)) continue;
				$options .= '<option value="'.$set.'">'.ucfirst($set).'</option>';
			}

			for ($i = 0; $i < count($textareas[0]); $i++) {
				$id = $textareas[1][$i];
				$field_id = $textareas[2][$i];
				$html = '<div class="group">';

				if (preg_match('%<label><input name=[\'"]fields\['.$id.'\]\[required\][\'"](?:[\w\W]+)</label>%iU', $textareas[0][$i], $required)) {
					$html .= $required[0];
				}

				$value = $this->_Parent->Database->fetchVar('markup', 0, '
					SELECT markup 
					FROM tbl_markitup_fields 
					WHERE field_id = '.$field_id.' AND section_id = '.$section_id
				);
				$html .= '<label>markItUp! <select name="fields['.$id.'][markitup]">'.str_replace(' value="'.$value.'"', ' value="'.$value.'" selected="selected"', $options).'</select></label></div>';

				$required = str_replace($required[0], '', $textareas[0][$i]);
				$html = str_replace('<label class="meta">', $html.'<label class="meta">', $required);

				$ctx['output'] = str_replace($textareas[0][$i], $html, $ctx['output']);
			}
		}

		private function convertJS($js, $name) {
			$find = array(
				'/mySettings = \{/',
				'/\{\s*name:\s*([\'"])([^\\1]+)\\1/U',
				'/\splaceHolder:\s*([\'"])([^\\1]+)\\1/U',
			);
			$rplc = array(
				"try { if(!myMarkItUpSettings) myMarkItUpSettings = {}; }\ncatch (e) { myMarkItUpSettings = {}; }\nmyMarkItUpSettings.{$name} = {\n\tnameSpace:'markItUp {$name}',",
				'{name:(Symphony.Language[$1$2$1] ? Symphony.Language[$1$2$1] : $1$2$1)',
				' placeHolder: (Symphony.Language[$1$2$1] ? Symphony.Language[$1$2$1] : $1$2$1)',
			);
			return preg_replace($find, $rplc, $js);
		}

		private function convertCSS($css, $name, $url) {
			$find = array(
				'/(\s)\.markItUp\s/',
				'/url\(([^\)]+)\)/U',
			);
			$rplc = array(
				'$1.markItUp.'.$name.' ',
				'url('.$url.'/$1)',
			);
			return preg_replace($find, $rplc, $css);
		}
	}

