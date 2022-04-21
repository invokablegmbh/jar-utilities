.. include:: /Includes.rst.txt
.. index:: Utilities
.. _utilities-NumberUtility:

=====================
Number Utility
=====================

Utility Class for working with numbers.

.. php:namespace::  Jar\Utilities\Utilities

.. php:class:: NumberUtility

------------------------------------

.. php:method:: isWholeInt($val)

   Checks if the value represents a whole number (integer).

   :param mixed $val: The value to check.
   :returns: "True" if is a whole number else return "false".

   **Example:**

   .. code-block:: php

      NumberUtility::isWholeInt(1234);
      // returns true      
      
      NumberUtility::isWholeInt(12.34);
      // returns false      
      
      NumberUtility::isWholeInt("01234");
      // returns true      
      
      NumberUtility::isWholeInt("1234");
      // returns true      
      
      NumberUtility::isWholeInt("hello");
      // returns false      
      
      NumberUtility::isWholeInt("");
      // returns false      
      
      NumberUtility::isWholeInt(null);
      // returns false
