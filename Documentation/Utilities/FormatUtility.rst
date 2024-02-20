.. include:: /Includes.rst.txt
.. index:: Utilities
.. _utilities-FormatUtility:

=====================
Format Utility
=====================

Utility Class which mainly converts TYPO3 Backend strings to handy arrays.

.. php:namespace::  Jar\Utilities\Utilities

.. php:class:: FormatUtility

------------------------------------

.. php:method:: buildLinkArray($params)

   Converts t3link parameters to a list of ready-to-use link informations.

   :param string $params: T3link parameters.
   :returns: Link informations or null when failed.

   **Example:**

   .. code-block:: php

      FormatUtility::buildLinkArray('t3://page?uid=196 _blank warning "Click me" ?bla=1');

   returns

   .. code-block:: php

      [
         'url' => 'https://example.com/a-page?bla=1',
         'base' => 'https://example.com/a-page',
         'params' => '?bla=1',
         'target' => '_blank',
         'text' => 'Click me',
         'class' => 'warning',
         'raw' => 't3://page?uid=196 _blank warning "Click me" ?bla=1'
      ]

------------------------------------

.. php:method:: buildTimeArray($time)

   Build time information for a stored time.

   :param int $time: Time in seconds.
   :returns: Time informations or null when failed.

   **Example:**

   .. code-block:: php

      FormatUtility::buildTimeArray(62880);

   returns

   .. code-block:: php

      [
         'timeForSorting' => 62880,
         'formatedTime' => '17:28'
      ]

------------------------------------

.. _utilities-FormatUtility-buildDateTimeArrayFromString:

.. php:method:: buildDateTimeArrayFromString($date)

   Build date informations from a date string.

   :param string $date: Date string.
   :returns: Date informations or null when failed.

   **Example:**

   .. code-block:: php

      FormatUtility::buildDateTimeArrayFromString('2021-08-25 13:31:00');

   returns

   .. code-block:: php

      [
         'unix' => 1629898260,
         'day' => '25',
         'dayNonZero' => '25',
         'weekDayText' => 'Mittwoch',
         'weekDayTextShort' => 'Mi',
         'month' => '08',
         'monthText' => 'August',
         'monthTextShort' => 'Aug',
         'year' => '2021',
         'hour' => '13',
         'minute' => '31',
         'second' => '00',
         'dateForSorting' => '2021-08-25',
         'formatedDate' => '25.08.2021',
         'formatedDateShort' => '25.08.21',
         'formatedDateShorter' => '25.08.',
         'dayOfWeek' => '3',
         'weekOfYear' => '34',
         'formatedTime' => '13:31',
      ]

------------------------------------

.. php:method:: buildDateTimeArray($date)

   Build date informations from a DateTime object.

   :param \DateTime $date: DateTime object.
   :returns: Date informations or null when failed.

   **Example:**

   Same as :ref:`buildDateTimeArrayFromString <utilities-FormatUtility-buildDateTimeArrayFromString>`, but with a ``\DateTime`` object as parameter.
------------------------------------

.. php:method:: renderRteContent($value)

   Compiles rich-text to the final markup.

   :param string $value: The rich-text.
   :returns: string The final markup.

   **Example:**

   .. code-block:: php

      FormatUtility::renderRteContent('<h1>Lorem Ipsum</h1><p><a href="t3://page?uid=123">Click me</a></p>');

   returns

   .. code-block:: html

      <h1>Lorem Ipsum</h1><p><a href="/a-page">Click me</a></p>
