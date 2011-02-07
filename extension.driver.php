<?php
	Class extension_markitup extends Extension{

		public function about(){
			return array('name' => 'markItUp!',
						 'version' => '2.0',
						 'release-date' => '2011-01-17',
						 'author' => array('name' => 'Marcin Konicki, Nils Werner',
										   'website' => 'http://ahwayakchih.neoni.net',
										   'email' => 'ahwayakchih@neoni.net'),
						 'description' => __('Add some basic text editor buttons to every textarea that is using one of supported formatters.')
			);
		}
		
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'InitaliseAdminPageHead',
					'callback'	=> 'initaliseAdminPageHead'
				)
			);
		}

		public function initaliseAdminPageHead($context) {
			$page = $context['parent']->Page;

            $page->addScriptToHead(URL . '/extensions/markitup/assets/dist/markitup/jquery.markitup.js', 3466703);
            $page->addStylesheetToHead(URL . '/extensions/markitup/assets/dist/markitup/skins/simple/style.css', 'screen', 3466701);
            $page->addScriptToHead(URL . '/extensions/markitup/assets/markitup.publish.js', 3466703);
			$page->addStylesheetToHead(URL . '/extensions/markitup/assets/markitup.publish.css', 'screen', 3466702);
		}
	}
