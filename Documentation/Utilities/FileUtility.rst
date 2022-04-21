.. include:: /Includes.rst.txt
.. index:: Utilities
.. _utilities-FileUtility:

=====================
File Utility
=====================

Handle files and their references.

.. php:namespace::  Jar\Utilities\Utilities

.. php:class:: FileUtility

------------------------------------


.. php:method:: getFileReferenceByUid($uid)

   Loads ``\TYPO3\CMS\Core\Resource\FileReference`` object from sys_file_reference table via UID.

   :param int $uid: UID of the sys_file_reference record.
   :returns: ``TYPO3\CMS\Core\Resource\FileReference`` or ``null`` if resource doesn't exist or file is missing.

   **Example:**

   .. code-block:: php

      FileUtility::getFileReferenceByUid(123);

   returns

   .. code-block:: php

      // TYPO3\CMS\Core\Resource\FileReference
      {
         'propertiesOfFileReference' => /* ... */,
         'name' => /* ... */,
         'originalFile' => /* ... */,
         'mergedProperties' => /* ... */
      }

-------------------------------------------------

.. php:method:: buildFileArrayBySysFileReferenceUid($uid, $configuration)

   Shorthand for ``FileUtility::buildFileArrayBySysFileReference(FileUtility::getFileReferenceByUid($uid))``, accepts the UID of an FileReference instead of using the FileReference object directly.

   :param int $uid: UID of the sys_file_reference record.
   :param array $configuration: (optional) See :ref:`buildFileArrayBySysFileReference <utilities-FileUtility-buildFileArrayBySysFileReference>` for further details.
   :returns: File-information array or ``null`` if resource doesn't exist or file is missing.

   **Example:**

   .. code-block:: php

      FileUtility::buildFileArrayBySysFileReferenceUid(123);

   returns

   .. code-block:: php

      [
         'uid' => 123
         'url' => 'fileadmin/user_upload/my-image-original.jpg',
         'alt' => 'some text',
         'title' => 'some text',
         'description' => 'some text',
         'link' => NULL,
      ]

-------------------------------------------------

.. _utilities-FileUtility-buildFileArrayBySysFileReference:

.. php:method:: buildFileArrayBySysFileReference($fileReference, $configuration)

   Preparation of files and images in a simple array structure. Very helpful in image preparation and cropping.

   :param \TYPO3\CMS\Core\Resource\FileReference: $fileReference
   :param array $configuration: (optional). The configuration is based on three settings:

      1. ``showDetailedInformations`` (bool): For all kind of files. If active, list more informations about the file (``name, extension, type, mimetype, size, basename``).

         .. warning::

            This has an noticeable impact on performance when a huge amount of files is processed.

      2. ``tcaCropVariants`` (array): Just for images. List of :ref:`t3tca:columns-imageManipulation-properties-cropVariants`, which are applied to the images.

         .. tip::

            Instead of putting this together yourself, it's easier to use the :ref:`services-reflection` or :ref:`dataprocessors-ReflectionProcessor`. These automatically read the tcaCropVariants from the corresponding TCA configurations of the images.

      3. ``processingConfigurationForCrop`` (array): Just for images. Configuration how the image should be proceed in the different ``tcaCropVariants``. Configurations like ``maxWidth``, ``minWidth``, ``width`` (applies also for ``heigth``) are possible.

   :returns: File-information array or ``null`` if resource doesn't exist or file is missing.

   **Example:**

   .. code-block:: php

      FileUtility::buildFileArrayBySysFileReference(
         $aFileReference,
         [			
            // show informations about file
            'showDetailedInformations' => true,

            // set cropping variants
            'tcaCropVariants' => [
               'desktop' => [
                  /* .. */
                  'cropArea' => [
                     'x' => 0.0,
                     'y' => 0.0,
                     'width' => 1.0,
                     'height' => 1.0,
                  ]
               ], 
               'mobile' => [
                  /* .. */
                  'cropArea' => [
                     'x' => 0.0,
                     'y' => 0.0,
                     'width' => 1.0,
                     'height' => 1.0,
                  ]
               ]
            ],

            // set image rendering instructions for the different cropping variants             
            'processingConfigurationForCrop' => [
               'desktop' => [
                  'maxWidth' => 3000
               ],
               // will be ignored, because we have no tcaCropVariants['medium'] informations
               'medium' => [
                  'maxWidth' => 1920
               ],
               // will be ignored, because we have no tcaCropVariants['tablet'] informations
               'tablet' => [
                  'maxWidth' => 1024
               ],
               'mobile' => [
                  'maxWidth' => 920
               ]
            ]
         ]
      );

   returns

   .. code-block:: php
                                                                                                   
      [      
         // base informations
         'uid' => 1
         'url' => 'fileadmin/user_upload/my-image-original.jpg',
         'alt' => 'some text',
         'title' => 'some text',
         'description' => 'some text',
         'link' => NULL,

         // detailed informations
         'name' => 'my-image-original.jpg',
         'extension' => 'jpg',
         'type' => 2,
         'mimetype' => 'image/jpeg',
         'size' => 2313,
         'basename' => 'my-image-original',

         // 'cropped' is just available for image-files
         'cropped' => [
            'desktop' => 'fileadmin/_processed_/1/my-image-original-max-width-3000.jpg',           
            'mobile' => 'fileadmin/_processed_/1/my-image-original-max-width-920.jpg'
         ]      
      ]

-------------------------------------------------

.. php:method:: humanFilesize($bytes, $decimals = 2)

   Converts filesizes in a human readable format.

   :param int $bytes: Size of file in bytes.
   :param int $decimals: (optional) Length of decimals.
   :returns: Filesize in human readable format.

   **Example:**

   .. code-block:: php

      FileUtility::humanFilesize(123456);    // 120.56 kB
      FileUtility::humanFilesize(123456, 0); // 121 kB
