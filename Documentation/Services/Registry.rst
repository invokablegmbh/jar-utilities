.. include:: /Includes.rst.txt
.. index:: Services
.. _services-Registry:

=====================
Registry Service
=====================

Simple Memory cache class, handy for use before TYPO3 native caches
are available (they can not be injected/instantiated during ext_localconf.php).

.. php:namespace::  Jar\Utilities\Services

.. php:class:: RegistryService

   .. php:method:: set($storeName, $key, $value)

      Put a item in a store.

      :param string $storeName: Name of the store.
      :param string $key: Key of the item.
      :param mixed $value: Value of the item.
      :returns: ``self``
         

   .. php:method:: get($storeName, $key)

      Returns a value out of a store.

      :param string $storeName: Name of the store.
      :param string $key: Key of the item.      
      :returns: Value of the item or ``false``.

   .. php:method:: getWholeStore($storeName)

      Returns the whole content of a store.

      :param string $storeName: Name of the store.
      :returns: Value of the store or ``null``.
    

Example:

.. code-block:: php
                                                                                          
   $cache = GeneralUtility::makeInstance(RegistryService::class);

   $hash = 'my-elements';

   if (($elements = $cache->get('my-little-store', $hash)) === false) {
      $elements = [1, 2, 3, 4, 5];
      $cache->set('my-little-store', $hash, $elements);
   }

   var_dump($cache->getWholeStore('my-little-store'));
   // Result of var_dump:
   [
      'my-elements' => [1, 2, 3, 4, 5]
   ]
