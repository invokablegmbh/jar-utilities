.. include:: /Includes.rst.txt
.. index:: Utilities
.. _utilities-BackendUtility:

=====================
Backend Utility
=====================

Collection of helpers for backend developing.

.. php:namespace::  Jar\Utilities\Utilities

.. php:class:: BackendUtility

------------------------------------

.. php:method:: createFrontendLink($pageUid, $params)

   Creates a frontend link, also in backend context.

   :param int $pageUid: The page uid.
   :param array $params: Typolink parameters.
   :returns: Link url.

   **Example:**

   .. code-block:: php

      BackendUtility::createFrontendLink(123, ['lightbox' => 1]);

   returns

   .. code-block:: php

      /a-page?lightbox=1&cHash=ffe...

------------------------------------

.. php:method:: currentPageUid()

   Returns the current page uid (in backend and frontend context).

   :returns: Current page uid.

   **Example:**

   .. code-block:: php

      BackendUtility::currentPageUid();

   returns

   .. code-block:: php

      123

------------------------------------

.. php:method:: getHostname()

   Get the fully-qualified domain name of the host.

   :returns: The fully-qualified host name.

   **Example:**

   .. code-block:: php

      // current Domain is https://example.com/bla
      BackendUtility::getHostname();

   returns

   .. code-block:: php

      example.com

------------------------------------

.. php:method:: getEditLink($table, $uid)

   Get route link for editing records in backend.

   :param string $table: The record table.
   :param int $uid: The record uid.
   :returns: The resulting link.

   **Example:**

   .. code-block:: php
      
      BackendUtility::getEditLink('tt_content', 123);

   returns

   .. code-block:: php

      /typo3/index.php?route=%2Frecord%2Fedit&token=75...&returnUrl=%2Ftypo3%2Findex.php%3Froute%3D%252Fmodule%252Fweb%252Flayout%26token%3D74...%26id%3D270%23element-tt_content-123&edit%5Btt_content%5D%5B123%5D=edit

------------------------------------

.. php:method:: getWrappedEditLink($table, $uid, $content)

   Get route link for editing records in backend. Wrapped in a <a>-Tag

   :param string $table: The record table.
   :param int $uid: The record uid.
   :param string $content: Inner HTML of the <a>-tag.
   :returns: The resulting <a>-tag.

   **Example:**

   .. code-block:: php
      
      BackendUtility::getWrappedEditLink('tt_content', 123, 'Click to edit');

   returns

   .. code-block:: html

      <a href="/typo3/index.php?route=%2Frecord%2Fedit&token=...">Click to edit</a>

------------------------------------

.. php:method:: getWizardInformations($ctype)

   Returns informations from the "New Content Wizard".

   :param string $ctype: The CType.
   :returns: Informations about that wizard.

   **Example:**

   .. code-block:: php

      BackendUtility::getWizardInformations('html');

   returns

   .. code-block:: php

      [
         'iconIdentifier' => 'content-special-html',
         'title' => 'Plain HTML',
         'description' => 'With this element you can insert raw HTML code on the page.'
      ]

------------------------------------

.. php:method:: getCurrentPageTS()

   Returns the current page TSconfig as array.

   :returns: Current page TSconfig.

   **Example:**

   .. code-block:: php

      BackendUtility::getCurrentPageTS();

   returns

   .. code-block:: php

      [      
         mod => array(/* 6 items */),
         TCEMAIN => array(/* 4 items */),
         TCEFORM => array(/* 3 items */),
         RTE => array(/* 1 item */),
         options => array(/* 1 item */),
         TCAdefaults => array(/* 2 items */),
         tt_content => array(/* 1 item */),
      ]