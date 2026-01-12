<?php

$EM_CONF['jar_utilities'] = [
	'title' => 'JAR Utilities',
	'description' => 'Utility classes that simplify TYPO3 development.',
	'category' => 'plugin',
	'author' => 'invokable GmbH',
	'author_email' => 'info@invokable.gmbh',
	'version' => '3.0.0',
	'state' => 'stable',
	'constraints' => [
		'depends' => [
			'typo3' => '13.0.0-13.4.99',
			'php' => '8.2.0-8.4.99',
		],
		'conflicts' => [
		],
		'suggests' => [
		],
	],
];
