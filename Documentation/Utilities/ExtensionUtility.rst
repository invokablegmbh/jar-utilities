.. include:: /Includes.rst.txt
.. index:: Utilities
.. _utilities-ExtensionUtility:

=====================
Extension Utility
=====================

Load informations from TYPO3 extensions.

.. php:namespace::  Jar\Utilities\Utilities

.. php:class:: ExtensionUtility

------------------------------------

.. php:method:: getExtensionConfiguration($extkey)

   Loads the configuration from a extension.

   :param string $extkey: The extension key.
   :returns: The extension configuration.

   **Example:**

   .. code-block:: php

      ExtensionUtility::getExtensionConfiguration('backend');

   returns

   .. code-block:: php

      [        
         'backendFavicon' => '',
         'backendLogo' => '',
         'loginBackgroundImage' => '',
         'loginFootnote' => '',
         'loginHighlightColor' => '',
         'loginLogo' => ''
      ]

------------------------------------

.. php:method:: getAbsExtPath($extkey, $path)

   Get the absolute path to a extension.

   :param string $extkey: The extension key.
   :param string $path: Optional path in extension directory.
   :returns: The absolute path.

   **Example:**

   .. code-block:: php

      ExtensionUtility::getAbsExtPath('backend', 'Resources/Public');

   returns

   .. code-block:: php

      /var/www/.../typo3/sysext/backend/Resources/Public


------------------------------------

.. php:method:: getExtensionVersion($extkey)
   
   Same as ExtensionManagementUtility::getExtensionVersion but removes the trailing "v".
   Handy when using version_compare.

   :param string $extkey: The extension key.
   :returns: The extension version as a string in the format "x.y.z".

   **Example:**

   .. code-block:: php

      debug( ExtensionManagementUtility::getExtensionVersion('core') );
      // returns 'v11.5.9'
      
      debug( ExtensionUtility::getExtensionVersion('core') );
      // returns '11.5.9'