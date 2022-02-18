.. include:: /Includes.rst.txt

.. _overview:

=============
Overview
=============

Data Processors
===============

==============  ========
Name            Description
==============  ========
GetRow          Shorthand for Loading just one element, if you want to load multiple items use :php:class:`TYPO3\\CMS\\Frontend\\DataProcessing\\DatabaseQueryProcessor` instead
Link            Creates a link on a (mainly) dataprocessed element
Localization    Add translations directly to the template - this gives frontenders faster handling of the translation variables
Reflection      Process data to complex objects and convert them to a simple Array structure based of TCA Configuration
==============  ========

