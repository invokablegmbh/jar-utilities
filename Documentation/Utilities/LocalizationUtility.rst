.. include:: /Includes.rst.txt
.. index:: Utilities
.. _utilities-LocalizationUtility:

=====================
Localization Utility
=====================

Shorthands for receiving and output translations.

.. php:namespace::  Jar\Utilities\Utilities

.. php:class:: LocalizationUtility

------------------------------------

.. php:method:: loadTyposcriptTranslations($extension)

   Loads the translations, set by _LOCAL_LANG from a extension.

   :param string $extension: Extension Key without the beginnining `tx_`
   :returns: The translations.

   **Example:**

   .. code-block:: typoscript

      plugin.tx_myextension._LOCAL_LANG {
         default {
            hello = Hello
            world = World
         }
         de {
            hello = Hallo
         }
      }

   .. code-block:: php

      LocalizationUtility::loadTyposcriptTranslations('myextension');

   returns

   .. code-block:: php

      // in EN
      [
         'hello' =>  'Hello',
         'world' =>  'World',
      ]

      // in DE
      [
         'hello' =>  'Hallo',
         'world' =>  'World',
      ]

------------------------------------

.. php:method:: getLanguageService()

   Get the current Language Service.

   :returns: The Language Service.

------------------------------------

.. php:method:: localize($input)

   Localize a translation key to the translation value.

   :param string $input: The translation key.
   :returns: The translation value or the translation key, when no translation is found.