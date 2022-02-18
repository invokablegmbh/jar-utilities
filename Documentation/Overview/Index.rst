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

Services
===============

============================    ========
Name                            Description
============================    ========
Reflection                      Service Class for Converting complex objects to a simple Array structure based of TCA Configuration
Registry                        Simple Memory Cache Class, handy for use before TYPO3 native Caches are available (they can not be injected/instantiated during ext_localconf.php)
============================    ========

Utilities
===============

============================    ========
Name                            Description
============================    ========
Backend                         Backend Developing related Helpers
Content                         Load and render Content Elements
Data                            Doing Database related stuff
Extension                       Load informations from TYPO3 extensions
File                            Handle files and their references
Format                          Utility Class which mainly converts TYPO3 Backend strings to handy arrays
Frontend                        Get Informations about the current Frontend 
Iterator                        Helpers for iterate and handle throught Lists
Localization                    Shorthands for receiving and output translations
Number                          Utility Class for working with numbers
Page                            Doing Page (and Pagetree) related stuff
String                          Collection sometimes-need string helpers
Tca                             Working faster with the TCA structure
TypoScript                      Load and progress faster with TypoScript
Wildcard                        Handling wildcard opertations like "b?a_*"
============================    ========

.. toctree::
   :maxdepth: 5
   :titlesonly:

   DataProcessors