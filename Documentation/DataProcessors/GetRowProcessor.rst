.. include:: /Includes.rst.txt
.. index:: DataProcessors
.. _dataprocessors-GetRowProcessor:

==================
GetRow Processor
==================

Loads one row from a database table

============================    ========
Parameter                       Description
============================    ========
table                           Name of the database table
uid                             UID of the database row
as                              If set, the result would be stored in this key, otherwise the result-fields will directly be merged in the data(!)
============================    ========

.. tip::

   If you want to load multiple items use :php:class:`TYPO3\\CMS\\Frontend\\DataProcessing\\DatabaseQueryProcessor` instead

Example
-------

Loading a row for detail-view, the uid is delivered via the get parameter **jobid**

.. code-block:: typoscript

   tt_content.tx_myextension_jobdetailctype {
      dataProcessing {
         100 = Jar\Utilities\DataProcessing\GetRowProcessor
         100 {
            table = tx_myextension_jobs
            uid = TEXT
            uid.data = GP:jobid
            as = item
         }
      }
   }
