.. include:: /Includes.rst.txt
.. index:: Utilities
.. _utilities-TypoScriptUtility:

=====================
TypoScript Utility
=====================

Load and progress faster with TypoScript.

.. php:namespace::  Jar\Utilities\Utilities

.. php:class:: TypoScriptUtility

------------------------------------

.. php:method:: get($path = null, $pageUid = null, $populated = false)

   Loads current TypoScript like TypoScriptUtility::get('plugin.tx_jarfeditor.settings')

   :param string|null $path: Dot notated TypoScript path.
   :param int|null $pageUid: PageUid from which page the TypoScript should be loaded (optional in Frontend).
   :param bool $populated: should the Data be populated (f.e. "element = TEXT / element.value = Bla" => "element = Bla").
   :returns: The plain TypoScript array.

------------------------------------

.. php:method:: convertTypoScriptArrayToPlainArray($typoscriptArray)

   Wrapper for the Core convertTypoScriptArrayToPlainArray

   :param array $typoscriptArray: A TypoScript array.
   :returns: The plain TypoScript array or "null" when not found.

------------------------------------

.. php:method:: populateTypoScriptConfiguration($conf, $cObj = null)

   Resolves cObjects and leaves values without deeper configuration as they are

   :param array $conf: Plain TypoScript array.
   :param null|ContentObjectRenderer $cObj: ContentObject which should be used.
   :returns: The plain populated TypoScript array.

   **Example:**
   
   .. code-block:: typoscript
      
      # converts this typoscript array from ...
         hello = world
         element = TEXT 
         element.value = Bla
         tree.value = Blupp
         
      # ... to this:
         hello = world
         element = Bla
         tree.value = Blupp

