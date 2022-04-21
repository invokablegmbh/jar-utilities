.. include:: /Includes.rst.txt
.. index:: Utilities
.. _utilities-ContentUtility:

=====================
Content Utility
=====================

Load and render content elements.

.. php:namespace::  Jar\Utilities\Utilities

.. php:class:: ContentUtility

------------------------------------

.. php:method:: renderElement($uid)

   Render the frontend output of a content element.

   :param int $uid: The content element uid.
   :returns: The rendered markup.

   **Example:**

   .. code-block:: php

      ContentUtility::renderElement(123);

   returns

   .. code-block:: html

      <div id="c123" class="component mediabox--outer">
         <div class="mediabox bg-deepgrey">
               <div class="container content">
                  <!-- ... -->
            </div>
         </div>
      </div>