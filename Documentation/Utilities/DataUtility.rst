.. include:: /Includes.rst.txt
.. index:: Utilities
.. _utilities-DataUtility:

=====================
Data Utility
=====================

Doing database related stuff.

.. php:namespace::  Jar\Utilities\Utilities

.. php:class:: DataUtility

------------------------------------

.. php:method:: getRow($table, $uid)

   Load one record from a table.

   :param string $table: The table name.
   :param int $uid: The record uid.
   :returns: The resulting row.

   **Example:**

   .. code-block:: php

      DataUtility::getRow('tt_content', 123);

   returns

   .. code-block:: php

      [
         'uid' => 1455,
         'pid' => 267,
         't3ver_oid' => 0,
         't3ver_wsid' => 0,
         't3ver_state' => 0,
         't3ver_stage' => 0,
         't3ver_count' => 0,
         't3ver_tstamp' => 0,
         't3ver_move_id' => 0,
         't3_origuid' => 0,
         'tstamp' => 1637579249,
         'crdate' => 1637579249,
         'cruser_id' => 9,
         'editlock' => 0,
         'hidden' => 0,
         'sorting' => 128,
         'CType' => 'html',
         'header' => '',
         'header_position' => '',
         'rowDescription' => NULL,
         'bodytext' => '...',
         // ...
      ]