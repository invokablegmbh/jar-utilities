.. include:: /Includes.rst.txt
.. index:: DataProcessors
.. _dataprocessors-LocalizationProcessor:

==================
Localization Processor
==================

Add translations directly to the template - this gives frontenders faster handling of the translation variables

.. attention::

   Just the _LOCAL_LANG TypoScript Configuration could be loaded with this Processor, if you want to load translations from files, please use the proper ViewHelpers!

============================    ========
Parameter                       Description
============================    ========
extensionsToLoad                Commaseparated list of extension-keys
flat                            If active, all translations of all translations will be outputed in one resulting list, otherwise the translations would be grouped by extension key
                                
                                .. tip::

                                    Activating is useful, when you just want to load translations from one extension
as                              If set, the result would be stored in this key, otherwise the result-fields will directly be merged in the data(!)
============================    ========

Example Case
--------------

.. code-block:: typoscript

   tt_content.tx_myextension_joblistctype {
      dataProcessing {         
         20 = Jar\Utilities\DataProcessing\LocalizationProcessor
         20 {
            extensionsToLoad = myextension
            as = translations
            flat = 1
         }
      }
   }