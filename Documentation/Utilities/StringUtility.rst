.. include:: /Includes.rst.txt
.. index:: Utilities
.. _utilities-StringUtility:

=====================
String Utility
=====================

Collection of string helpers.

.. php:namespace::  Jar\Utilities\Utilities

.. php:class:: StringUtility

------------------------------------

.. php:method:: crop($value, int $maxCharacters = 150)

   Crops a string.

   :param string $value: The string to crop.
   :param int $maxCharacters: Length of cropping.
   :returns: The cropped string.

   **Example:**

   .. code-block:: php

      var_dump(StringUtility::crop('Lorem ipsum dolor sit amet.', 20));
      // 'Lorem ipsum dolor...'

      // Respects also crops in Tags
      var_dump(StringUtility::crop('<h1>Lorem ipsum dolor sit.</h1>', 20));
      // '<h1>Lorem ipsum dolor...</h1>'



------------------------------------

.. php:method:: ripTags($string)

   Same as "strip_tags" but leaves spaces at the position of removed tags.

   :param string $string: The string to strip.
   :returns: The stripped string.

   **Example:**

   .. code-block:: php

      var_dump(strip_tags('<span>Hello</span><span>World</span>'));
      // 'HelloWorld'

      var_dump(StringUtility::ripTags('<span>Hello</span><span>World</span>'));
      // 'Hello World'

------------------------------------

.. php:method:: fastSanitize($string, $toLowerCase = true)

   Simple sanitizing of strings, no complex handling of umlauts like "äöü".

   :param string $string: The string to sanitize.
   :param bool $toLowerCase: Should the string converted to lower case?
   :returns: The sanitized string.

   **Example:**

   .. code-block:: php

      var_dump(StringUtility::fastSanitize('Über wie viele Brücken musst du gehen?', true));
      // 'ber_wie_viele_br_cken_musst_du_gehen'

      var_dump(StringUtility::fastSanitize('Über wie viele Brücken musst du gehen?', false));
      // 'ber_wie_viele_Br_cken_musst_du_gehen'



------------------------------------

.. php:method:: sanitize($string, $toLowerCase = true)

   More complex sanitizing of strings, also handles of umlauts like "äöü".

   :param string $string: The string to sanitize.
   :param bool $toLowerCase: Should the string converted to lower case?
   :returns: The sanitized string.

   **Example:**

   .. code-block:: php

      var_dump(StringUtility::sanitize('Über wie viele Brücken musst du gehen?', true));
      // 'ueber_wie_viele_bruecken_musst_du_gehen'
      
      var_dump(StringUtility::sanitize('Über wie viele Brücken musst du gehen?', false));
      // 'UEber_wie_viele_Bruecken_musst_du_gehen'

