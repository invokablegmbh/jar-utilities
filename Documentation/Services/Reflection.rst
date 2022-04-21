.. include:: /Includes.rst.txt
.. index:: Services
.. _services-reflection:

=====================
Reflection Service
=====================

Service class for converting complex objects to a simple array structure based of TCA configuration.
Handy for faster integration development, headless systems and ajax calls.

.. php:namespace::  Jar\Utilities\Services

.. php:class:: ReflectionService
   
   .. rst-class:: h4

      .. rubric:: Configuration

   .. tip::
      For column definitions, you can use wildcards like "?" and "*". So instead of define
      ``table_class, table_caption, table_delimiter, table_enclosure, ...`` specificly, you can use ``table_*``.


   .. php:method:: setPropertiesByConfigurationArray($configuration)

      Sets multiple properties in one call.

      :param array $configuration: Configuration settings.
      :returns: ``self``

      Example:

      .. code-block:: php
                                                                                                
         setPropertiesByConfigurationArray([
            'tableColumnBlacklist' => [        
               // don't reflect the "categories" column in tx_myextension_slides          
               'tx_myextension_slides' => 'categories',                  
               // don't reflect the "doktype" column and all columns
               // starting with "cache_" in pages
               'pages' => 'cache_*, doktype'
            ],
            'tableColumnWhitelist' => [
               // just reflect the slide related fields and the header in tt_content
               'tt_content' => 'tx_myextension_*, header'
            ],
            'tableColumnRemoveablePrefixes' => [
               // remove the prefix "tx_myextension_" from our columns
               'tt_content' => 'tx_myextension_'
            ],
            'tableColumnRemapping' => [
               'tt_content' => [
                  // rename the column "header" to "title" in the result
                  'header' => 'title'
               ]
            ],
            'buildingConfiguration' => [
               // ... same settings like "setBuildingConfiguration" (example below)
            ],
            // deactivate debug output of reflection
            'debug' => false,
         ]);

      .. attention::

         In comparison to the direct using of setter-methods, some table-handle-definitions are commaseparated.
         This affects the setting for ``tableColumnBlacklist``, ``tableColumnWhitelist`` and ``tableColumnRemoveablePrefixes``.

         

   .. php:method:: setColumnBlacklist($columnBlacklist)

      Set list of columns that are generally not processed.
      This blacklist will be applied to every table.

      :param array $columnBlacklist: List of columns that are generally not processed.
      :returns: ``self``

      Example:

      .. code-block:: php
                                                                                                
         setColumnBlacklist([
            't3ver_*',
            'l18n_*',
            'l10n_*',
            'crdate',
            'cruser_id',
            'editlock',
            /* ... */
         ]);   

   .. php:method:: setTableColumnBlacklist($columnBlacklist)

      Set list of table specific columns which aren't processed.

      :param array $tableColumnBlacklist: List of table specific columns which aren't processed.
      :returns: ``self``

      Example:

      .. code-block:: php
                                                                                                
         setTableColumnBlacklist([        
            // don't reflect the "categories" column in tx_myextension_slides          
            'tx_myextension_slides' => ['categories'],                  
            // don't reflect the "doktype" column and all columns
            // starting with "cache_" in pages
            'pages' => ['cache_*', 'doktype']
         ]);

   .. php:method:: addToTableColumnBlacklist($tableColumnBlacklist)

      Add to list of table specific columns which aren't processed.
      Same as ``setTableColumnBlacklist`` without replacing the whole tableColumnBlacklist-Settings

      :param array $tableColumnBlacklist: List of table specific columns which aren't processed.
      :returns: ``self``

   .. php:method:: setTableColumnWhitelist($tableColumnWhitelist)

      Set List of tables columns which should be processed exclusively.

      :param array $tableColumnWhitelist: List of tables columns which should be processed exclusively.
      :returns: ``self``

      Example:

      .. code-block:: php
                                                                                                
         setTableColumnWhitelist([        
            // just reflect the slide related fields and the header in tt_content
            'tt_content' => ['tx_myextension_*', 'header']
         ]);

   .. php:method:: setTableColumnRemoveablePrefixes($tableColumnRemoveablePrefixes)

      Set wildcard based replacement for column names.
      F.e. ``'tt_content' => ['table_']`` converts ``'tt_content->table_caption'`` to ``'tt_content->caption'``

      :param array $tableColumnRemoveablePrefixes: Wildcard based replacement for column names.
      :returns: ``self``

      Example:

      .. code-block:: php
                                                                                                
         setTableColumnRemoveablePrefixes([        
            // remove the prefix "tx_myextension_" from our columns
            'tt_content' => ['tx_myextension_']
         ]);

   .. php:method:: setTableColumnRemapping($tableColumnRemapping)

      Set remap column-names in reflected result. F.e. ``'tt_content' => ['table_caption' => 'heading']`` converts ``'tt_content->table_caption' to 'tt_content->heading'``.
	   Important: takes action AFTER replacement of ColumnNames! Keep that in mind.

      :param array $tableColumnRemapping: Remapping definition list.
      :returns: ``self``

      Example:

      .. code-block:: php
                                                                                                
         setTableColumnRemapping([        
            'tt_content' => [
               // rename the column "header" to "title" in the result
               'header' => 'title'
            ]
         ]);

   .. php:method:: setBuildingConfiguration($arrayBuildingConfiguration)

      Contains e.g. instructions for the preparation of files and images.

      :param array $arrayBuildingConfiguration: Instructions for the preparation of files and images.
      :returns: ``self``

      Example:

      .. code-block:: php
                                                                                                
         setBuildingConfiguration([
            'file' => [
               // we don't want deeper informations about files (f.e. filename,
               // extension, size, etc ..)
               'showDetailedInformations' => false,
               // set image rendering instructions for the different cropping variants             
               'processingConfigurationForCrop' => [                  
                  'desktop' => [
                     'maxWidth' => 3000
                  ],
                  'medium' => [
                     'maxWidth' => 1920
                  ],
                  'tablet' => [
                     'maxWidth' => 1024
                  ],
                  'mobile' => [
                     'maxWidth' => 920
                  ]
               ]
            ]
         ]);

   |

   .. rst-class:: h4

      .. rubric:: Reflection output

   .. php:method:: buildArrayByRows($rows, $table, $maxDepth)

         Reflects a list of record rows.

         :param array $rows: The record list.
         :param string $table: The tablename.
         :param int $maxDepth: The maximum depth at which related elements are loaded (default is 8).
         :returns: Reflected result.

   .. php:method:: buildArrayByRow($rows, $table, $maxDepth)

         Reflects a single record row.

         :param array $row: The record row.
         :param string $table: The tablename.
         :param int $maxDepth: The maximum depth at which related elements are loaded (default is 8).
         :returns: Reflected result.

   .. tip::
      For examples output, take a look into :ref:`Reflection Processor example <dataprocessors-ReflectionProcessor-example>` or :ref:`services-reflection-input-output-examples`

   |

   .. rst-class:: h4

      .. rubric:: Getter

   .. php:method:: getTcaFieldDefinition()

         Get array for used TCA field definitions, helpful for Post-handling that prepared data.
         
         :returns: All used TCA table and column definitions which was used on the last reflection.

   .. note::
      For each configration-Setter above there is also a getter available (f.e. ``setColumnBlacklist`` / ``getColumnBlacklist``). 
      For the sake of clarity, these are not listed here.


|

.. _services-reflection-input-output-examples:   

Reflected TCA field output examples
===================================

Reflected records are builded from the following elements.

Flat fields:
------------

.. _services-reflection-examples-password:

Password (TCA field with eval ``password``)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Input
""""""

``password12345``

Output
""""""

Nothing, for security reasons, we don't reflect password fields.

|
------------------------------------------------------------------------

.. _services-reflection-examples-link:

Link (TCA type ``input`` with renderType ``inputLink``)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Input
""""""

``t3://page?uid=196 _blank warning "Click me" ?bla=1``

Output
""""""

.. code-block:: php
                                                                                                   
   [
      'url' => 'https://example.com/a-page?bla=1',
      'base' => 'https://example.com/a-page',
      'params' => '?bla=1',
      'target' => '_blank',
      'text' => 'Click me',
      'class' => 'warning',
      'raw' => 't3://page?uid=196 _blank warning "Click me" ?bla=1'
   ]

|
------------------------------------------------------------------------


.. _services-reflection-examples-time:

Time (TCA type ``input`` with renderType ``inputDateTime`` and eval ``time``)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Input
"""""

``62880``

Output
""""""

.. code-block:: php
                                                                                                   
   [
      'timeForSorting' => 62880,
      'formatedTime' => '17:28'
   ]

|
------------------------------------------------------------------------

.. _services-reflection-examples-datetime:

DateTime (TCA type ``input`` with renderType ``inputDateTime`` and eval ``datetime`` or ``date``)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Input
""""""

``2021-08-25 13:31:00``

Output
""""""

.. code-block:: php
                                                                                                   
   [
      'unix' => 1629898260,
      'day' => '25',
      'dayNonZero' => '25',
      'weekDayText' => 'Mittwoch',
      'weekDayTextShort' => 'Mi',
      'month' => '08',
      'monthText' => 'August',
      'monthTextShort' => 'Aug',
      'year' => '2021',
      'hour' => '13',
      'minute' => '31',
      'second' => '00',
      'dateForSorting' => '2021-08-25',
      'formatedDate' => '25.08.2021',
      'formatedDateShort' => '25.08.21',
      'formatedDateShorter' => '25.08.',
      'dayOfWeek' => '3',
      'weekOfYear' => '34',
      'formatedTime' => '13:31',
   ]

.. note::
   The example above shows german weekdays and months, this generation is based on the current active language


|
------------------------------------------------------------------------


.. _services-reflection-examples-textarea:

Textarea (TCA type ``text`` and ``enableRichtext`` is ``false``)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Input
""""""

.. code-block:: html

   Hello
   World

Output
""""""

.. code-block:: html
                                                                                                   
   Hello<br />World


|
------------------------------------------------------------------------


.. _services-reflection-examples-rte:

RTE (TCA type ``text`` and ``enableRichtext`` is ``true``)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Input
""""""

.. code-block:: html

   <h1>Lorem Ipsum</h1><p><a href="t3://page?uid=123">Click me</a></p>

Output
""""""

.. code-block:: html
                                                                                                   
   <h1>Lorem Ipsum</h1><p><a href="/a-page">Click me</a></p>


|
------------------------------------------------------------------------


.. _services-reflection-examples-checkbox:

Checkbox (TCA type ``check``)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Output
""""""

``true`` when checked, otherwise ``false``.

|
------------------------------------------------------------------------


.. _services-reflection-examples-rawfields:

Others (TCA type ``radio``, ``passthrough``, ``slug`` and ``flex``)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Output
""""""

The raw field value.

.. note::

   Also all unknown types of flat fields are returned raw.

|
|

.. _services-reflection-examples-relations:

Relation fields:
----------------

.. _services-reflection-examples-images:

Files and Images (TCA -mostly- type ``inline`` and ``foreign_table`` is ``sys_file_reference``)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Input
""""""

Sum of references. The linked records will be resolved via TCA config.

Output
""""""

.. code-block:: php
                                                                                                   
   [
      [
         'uid' => 1
         'url' => 'fileadmin/user_upload/my-image-original.jpg',
         'alt' => 'some text',
         'title' => 'some text',
         'description' => 'some text',
         'link' => NULL,
         // 'cropped' is just available for image-files
         'cropped' => [
            'desktop' => 'fileadmin/_processed_/1/my-image-original-max-width-3000.jpg',
            'medium' => 'fileadmin/_processed_/1/my-image-original-max-width-1920.jpg',
            'tablet' => 'fileadmin/_processed_/1/my-image-original-max-width-1024.jpg',
            'mobile' => 'fileadmin/_processed_/1/my-image-original-max-width-920.jpg'
         ]
      ],
      /* maybe more images ... */
   ]

|
------------------------------------------------------------------------

.. _services-reflection-examples-other-relationfields:

Other relations that file (TCA type ``inline``, ``select``, ``group``)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Input
""""""

Sum of references. The linked records will be resolved via TCA config.

Output
""""""

Each related element will be resolved in an array of this structure of his flat elements and relations.