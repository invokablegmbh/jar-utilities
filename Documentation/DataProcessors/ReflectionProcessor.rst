.. include:: /Includes.rst.txt
.. index:: DataProcessors
.. _dataprocessors-ReflectionProcessor:

==================
Reflection Processor
==================

TypoScript wrapper for :ref:`services-reflection`. Maps data to a simple array structure, based on the TCA configuration of the table. Also resolves all TCA-relations to other tables.
The strength lies in the fast loading of already existing structures, especially when using Ajax requests or headless systems.

=======================================    =======================================
Parameter                                  Description
=======================================    =======================================
buildingConfiguration                      Contains e.g. instructions for the preparation of files and images (see :ref:`dataprocessors-ReflectionProcessor-example`).
debug                                      var_dumps information which elements would be reflected.
maxDepth                                   The maximum depth at which related elements are loaded (default is 8).
replace                                    Replace the current data with the result of this DataProcessor
row                                        If set, data content of **row** would be reflected, otherwise the current data would be used (default).
rows                                       Same behavior as using **row**, with one important difference:
                                           To guard against endless recursions and unperformant reloading of rows, the Reflection service uses an internal store for previously loaded elements.
                                           This is store is accessable for all elements in **rows**. This is a huge performance gain, especially when the elements in **rows** are often related to the same elements.

                                           .. note::

                                              Load order

                                              1. **rows**
                                              2. **row**
                                              3. current data

table                                      Tablename which should be mapped (default: tt_content) (see :ref:`dataprocessors-ReflectionProcessor-example`).
tableColumnBlacklist                       List of table columns which should be blacklisted (wildcards like "*" and "?" are useable) (see :ref:`dataprocessors-ReflectionProcessor-example`).

                                           .. note::

                                              You don't need to blacklist core-related columns like 't3ver_oid', 't3_origuid', etc. The Reflection Service will filter them out. The 'uid' column can't be blacklisted

tableColumnRemapping                       List of table columns to be renamed (see :ref:`dataprocessors-ReflectionProcessor-example`)
tableColumnRemoveablePrefixes              List of table columns where the prefix should be removed (see :ref:`dataprocessors-ReflectionProcessor-example`)
tableColumnWhitelist                       List of table columns which should be whitelisted (wildcards like "*" and "?" are useable) (see :ref:`dataprocessors-ReflectionProcessor-example`)
=======================================    =======================================

.. _dataprocessors-ReflectionProcessor-example:

Example Case
------------

Assuming we have want to reflect of an slider element with the following table column / TCA structure:

*  CType **"slider"** (located in table **tt_content**)

   *  all default tt_content fields (header, categories, space_before_class, etc ...)
   *  tx_myextension_duration (input, with eval "int")
   *  tx_myextension_slides (IRRE relation to table **tx_myextension_slides**)

      *  image (FAL with the croppings for desktop, medium, tablet, mobile)
      *  headline (input)
      *  descrition (RTE text)
      *  link (input with link wizard)
      *  categories (category tree)

Output before reflection
~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

   [
      'data' => [
         'uid' => 123,
         'pid' => 345,
         'categories' => 4,
         'header' => 'my slider',
         'tx_myextension_duration' => 8000,
         'tx_myextension_slides' = 3
         // ... many other system related columns
      ],
      'current' => null
   ]


Reflecting of related data
~~~~~~~~~~~~~~~~~~~~~~~~~~

.. attention::

   Don't reflect (tt_content) elements without black- or whitelisting! Every relation (including **every** sub-relation) would be resolved!


.. code-block:: typoscript

   tt_content.slider.dataProcessing {
      10 = Jar\Utilities\DataProcessing\ReflectionProcessor
      10 {
         # FYI: this is superfluous, because "tt_content" is the default value
         table = tt_content
         
         tableColumnWhitelist {
            # just reflect the slide related fields and the header in tt_content
            tt_content = tx_myextension_*, header
         }
         
         tableColumnBlacklist {
            # don't reflect the "categories" column in tx_myextension_slides
            tx_myextension_slides = categories
         }

         tableColumnRemapping {
            tt_content {
               # rename the column "header" to "title" in the result
               header = title 
            }
         }

         tableColumnRemoveablePrefixes {
            # remove the prefix "tx_myextension_" from our columns
            tt_content = tx_myextension_
         }

         buildingConfiguration {
            file {
               # we don't want deeper informations about files (f.e. filename, extension, size, etc ..)
               showDetailedInformations = 0

               # set image rendering instructions for the different cropping variants
               processingConfigurationForCrop {
                  desktop.maxWidth = 3000
                  medium.maxWidth = 1920
                  tablet.maxWidth = 1024
                  mobile.maxWidth = 920
               }
            }
         }
      }
   }

.. tip::

   In most cases **buildingConfiguration** it's helpful to outsource to an globaly place like an lib-object

Output
""""""

.. code-block:: php

   [
      // original data:

      'data' => [
         'uid' => 123,
         'pid' => 345,
         'categories' => 4,
         'header' => 'my slider',
         'tx_myextension_duration' => 8000,
         'tx_myextension_slides' = 3
         // ... many other system related columns
      ],
      'current' => null

      
      // results of reflection:

      'uid' => '123',
      'title' => 'my slider',
      'duration' => 8000,
      'slides' => [
         0 => [            
            'image' => [
               0 => [
                  'uid' => 1
                  'url' => 'fileadmin/user_upload/my-image-original.jpg',
                  'alt' => 'some text',
                  'title' => 'some text',
                  'description' => 'some text',
                  'link' => NULL,
                  'cropped' => [
                     'desktop' => 'fileadmin/_processed_/1/my-image-original-max-width-3000.jpg',
                     'medium' => 'fileadmin/_processed_/1/my-image-original-max-width-1920.jpg',
                     'tablet' => 'fileadmin/_processed_/1/my-image-original-max-width-1024.jpg',
                     'mobile' => 'fileadmin/_processed_/1/my-image-original-max-width-920.jpg'
                  ]
               ]
            ],
            'headline' => 'some text',
            'descrition' => '<p>some RTE content</p>',
            'link' => [
               'url' => 'https://example.com/a-page?bla=1',
               'base' => 'https://example.com/a-page',
               'params' => '?bla=1',
               'target' => '_blank',
               'text' => 'Click me',
               'class' => 'warning',
               'raw' => 't3://page?uid=196 _blank warning "Click me" ?bla=1'
            ]
         ],
         1 => [
            // same structure as above
         ],
         2 => [
            // same structure as above
         ]
      ]
   ]


.. seealso::

   List how the Reflection Service will structure the output: :ref:`services-reflection-examples-link`