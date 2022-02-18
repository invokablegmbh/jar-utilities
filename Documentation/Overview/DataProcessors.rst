.. include:: /Includes.rst.txt
.. index:: Overview
.. _overview-dataprocessors:

=====================
Data Processors
=====================

GetRow
======

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
---------------

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


Link
====

Creates a link on a (mainly) dataprocessed element


Localization
============

Add translations directly to the template - this gives frontenders faster handling of the translation variables


Reflection
==========

Process data to complex objects and convert them to a simple Array structure based of TCA Configuration



How is the extension configured?
Aim to provide simple instructions detailing how the extension is configured.
Always assume that the user has no prior experience of using the extension.

Try and provide a typical use case for your extension
and detail each of the steps required to get the extension running.


.. index::
   overview; Example
   overview; Typical
.. _overview_example:
.. _overview_typical:

Typical example
===============

*  Does the integrator need to include a static template?
*  For example add a code snippet with comments

Minimal example of TypoScript:

*  Code-blocks have support for syntax highlighting
*  Use any supported language

.. code-block:: typoscript

   plugin.tx_myextension.settings {
      # configure basic email settings
      email {
         subject = Some subject
         from = someemail@example.org
      }
   }
