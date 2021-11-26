<?php

$EM_CONF['jar_utilities'] = array(
	'title' => 'JAR Utilities',
	'description' => 'Utility classes that simplify TYPO3 development',
	'category' => 'plugin',
	'author' => 'JAR Media GmbH',
	'author_email' => 'info@jar.media',
	'version' => '1.0.0',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => '0',
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'constraints' => array(
		'depends' => array(
			'typo3' => '10.4',
			'php' => '7.4.0-7.4.999',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);
