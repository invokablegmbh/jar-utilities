<?php

$EM_CONF['jar_utilities'] = array(
	'title' => 'JAR Utilities',
	'description' => 'Utility classes that simplify TYPO3 development.',
	'category' => 'plugin',
	'author' => 'invokable GmbH',
	'author_email' => 'info@invokable.gmbh',
	'version' => '2.0.5',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => '0',
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'constraints' => array(
		'depends' => array(
			'typo3' => '12.4.1-12.4.99',
			'php' => '7.4.0-8.1.999',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);
