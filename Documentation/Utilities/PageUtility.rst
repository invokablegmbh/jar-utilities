.. include:: /Includes.rst.txt
.. index:: Utilities
.. _utilities-PageUtility:

=====================
Page Utility
=====================

Doing Page (and Pagetree) related stuff.

.. php:namespace::  Jar\Utilities\Utilities

.. php:class:: PageUtility

------------------------------------

.. php:method:: getPidsRecursive($pids, $level = 3)

   Returns all Sub-Pids of certain PIDs.

   :param string $pids: The starting PID.
   :param int $level: Depth of the traversing levels.
   :returns: List of matching PIDs.

   **Example:**

   .. image:: /Images/pagetree.png
      :alt: My little sweet page tree

   |

   .. code-block:: php

      var_dump(PageUtility::getPidsRecursive(1));
      // ['1','2', '3', '7', '8', '4', '5', '9', '10', '6']

      var_dump(PageUtility::getPidsRecursive(1, 1));
      // ['1','2', '3', '4', '5', '6']

------------------------------------

.. php:method:: getPageFieldSlided($fieldname)

   Slides up a the Pagetree (starting from the current page) and return the nearest filled value of the field.

   :param string $fieldname: Name of the field/column.
   :returns: Value of the field when found, otherwise "null".