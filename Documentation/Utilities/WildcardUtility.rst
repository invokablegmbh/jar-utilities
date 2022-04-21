.. include:: /Includes.rst.txt
.. index:: Utilities
.. _utilities-WildcardUtility:

=====================
Wildcard Utility
=====================

Utility Class for handling wildcard opertations like "b?a_*"

.. php:namespace::  Jar\Utilities\Utilities

.. php:class:: WildcardUtility

------------------------------------

.. php:method:: matchAgainstPatternList($patterns, $string, $flags = 0)

   Matches a string against a whole list of patterns, returns "true" on first match

   :param array $patterns: List of patterns like ['b?a_*', 'plupp_*']
   :param string $string: The string to match.
   :param int $flags: Flags ("FNM_PATHNAME" or 1, "FNM_NOESCAPE" or 2, "FNM_PERIOD" or 4, "FNM_CASEFOLD" or 16) based on https://www.php.net/manual/en/function.fnmatch.php#refsect1-function.fnmatch-parameters
   :returns: Returns "true" on first match, otherwise false.

------------------------------------

.. php:method:: match($pattern, $string, $flags = 0)

   Simple wildcard which matches a string against a pattern. Wildcards like * or ? are useable.

   :param string $pattern: The Pattern like "hello*world"
   :param string $string: The string to match.
   :param int $flags: Flags ("FNM_PATHNAME" or 1, "FNM_NOESCAPE" or 2, "FNM_PERIOD" or 4, "FNM_CASEFOLD" or 16) based on https://www.php.net/manual/en/function.fnmatch.php#refsect1-function.fnmatch-parameters
   :returns: Returns "true" on match, otherwise false.

   **Example:**
   
   .. code-block:: php

      $pattern = 'hello*world';

      WildcardUtility::match($pattern, 'hello beatiful world'); // true
      WildcardUtility::match($pattern, 'hello happy planet');   // false
