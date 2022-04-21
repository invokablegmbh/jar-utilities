.. include:: /Includes.rst.txt
.. index:: Utilities
.. _utilities-TcaUtility:

=====================
TCA Utility
=====================

Utility Class for working faster with the TCA.

.. php:namespace::  Jar\Utilities\Utilities

.. php:class:: TcaUtility

------------------------------------

.. php:method:: getColumnsByType($table, $type)

   Returns active columns by TCA type.

   :param string $table: The table name.
   :param string $type: The type name.
   :returns: List of column names.

   **Example:**

   .. image:: /Images/TcaUtility/getColumnsByType.png
      :alt: getColumnsByType example TCA structure

   |

   .. code-block:: php

      var_dump(TcaUtility::getColumnsByType('tt_content', 'html'));
      // ['CType', 'colPos', 'header', 'bodytext', 'layout', 'frame_class', ... ]      

      var_dump(TcaUtility::getColumnsByType('tt_content', 'image'));
      // ['CType', 'colPos', 'header', ..., 'image', 'imagewidth', 'imageheight', ...]

------------------------------------

.. php:method:: getColumnsByRow($table, $row)

   Returns active columns based on a table record.

   :param string $table: The table name.
   :param array $row: The table record.
   :returns: List of column names.

   **Example:**

   .. code-block:: php

      // Shorthand for:
      TcaUtility::getColumnsByType( TcaUtility::getTypeFromRow( $row ));

      // Example output, see above under "getColumnsByType".

------------------------------------

.. php:method:: getColumnsByTable($table)

   Returns all default (first type) columns from a table.

   :param string $table: The table name.
   :returns: List of column names.


------------------------------------

.. php:method:: getTypeFromRow($table, $row)

   Returns actice TCA type based on a table record. 

   :param string $table: The table name.
   :param array $row: The table record.
   :returns: Name of the type will fallback to default type when no individual type is found.

 
------------------------------------

.. php:method:: getVisibleColumnsByRow($table, $row)

   Just return the columns which are visible for the current Backend User, respects current active display conditions of fields.

   :param string $table: The table name.
   :param array $row: The table record.
   :returns: List of column names.


------------------------------------

.. php:method:: getTypeFieldOfTable($table)

   Returns the column name which contains the "type" value from a table.

   :param string $table: The table name.
   :returns: The name of the "type" column.


------------------------------------

.. php:method:: getLabelFieldOfTable($table)

   Returns the column name which contains the "label" value from a table.

   :param string $table: The table name.
   :returns: The name of the "label" column.


------------------------------------

.. php:method:: getLabelFromRow($row, $table)

   Returns the label from a table record.

   :param array $row: The table record.
   :param string $table: The table name.
   :returns: The label or "null" when empty.

   **Example:**

   .. code-block:: php      

      TcaUtility::getLabelFromRow([
         'uid' => 3,
         'doktype => 254',
         'title' => 'Elemente',
         /* ... */
      ], 'pages');

      // returns "Elemente"

------------------------------------

.. php:method:: mapStringListToColumns($list, $table = null)

   Converts a comma-separated list of TCA Columns (a,b,c) to [a,b,c]. Also columns of containing pallets will be resolved (if parameter table is available).

   :param string $list: Comma-separated list of TCA Columns.
   :param string $table: The table name.
   :returns: List of column names.

------------------------------------

.. php:method:: getFieldDefinition($table, $column, $type = null)

   Returns the current TCA field definition from a table column. Also resolves column overrides when parameter "type" is set.

   :param string $table: The table name.
   :param string $column: The column name.
   :param null|string $type: The type to respect column overrides.
   :returns: The field definition or "null" when no field definition is found.

------------------------------------

.. php:method:: getFieldConfig($table, $column, $type = null)

   Returns the TCA field configuration from a table column.

   :param string $table: The table name.
   :param string $column: The column name.
   :param null|string $type: The type to respect column overrides.
   :returns: The field configuration or "null" when no field configuration is found.

------------------------------------

.. php:method:: remapItemArrayToKeybasedList($items)

   Converts a TCA item array to a key-based list.

   :param array $items: TCA item array.
   :returns: Key-based list.

   **Example:**

   .. code-block:: php

      TcaUtility::remapItemArrayToKeybasedList([['LLL:.../locallang.xlf:creation', 'uid'], ['LLL:.../locallang.xlf:backendsorting', 'sorting']]);

      // returns:

      [
         'uid' => [
            'label' => 'LLL:.../locallang.xlf:creation',
            'icon' => null
         ],
         'sorting' => [
            'label' => 'LLL:.../locallang.xlf:backendsorting',
            'icon' => null
         ]
      ]

------------------------------------

.. php:method:: getLabelOfSelectedItem($value, $column, $table, $type = null, $localize = true)

   Load the backend label from a selected item.

   :param string $value: The selected item. F.e ['LLL:.../locallang.xlf:backendsorting', 'sorting']
   :param string $column: Column name which contains the selected item.
   :param string $table: The table name.
   :param null|string $type: The type to respect column overrides.
   :param boolean $localize: If false return the raw value, otherwise return the translated value.
   :returns: The label.

------------------------------------

.. php:method:: getL10nConfig($table)

   Returns the TCA language fields from a table or null, if not set.

   :param string $table: The table name.
   :returns: TCA language fields from a table or null, if not set

------------------------------------

.. php:method:: getTca()

   Returns the current TCA.
      
   :returns: The TCA.
