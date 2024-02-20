.. include:: /Includes.rst.txt
.. index:: Utilities
.. _utilities-IteratorUtility:

=====================
Iterator Utility
=====================

Helpers for iterate and handle throught lists.

.. php:namespace::  Jar\Utilities\Utilities

.. php:class:: IteratorUtility

------------------------------------

.. php:method:: sortArrayByColumn(&$arr, $col, $dir = SORT_ASC)

   Sort array by column/key value.

   :param array $arr: Reference of array.
   :param string $col: The column/key name.
   :param int $dir: The direction SORT_ASC or SORT_DESC 

   **Example:**

   .. code-block:: php

      $persons = [
         [
            'name' => 'peter',
            'age' => 34
         ],
         [
            'name' => 'klaus',
            'age' => 21
         ],
         [
            'name' => 'michael',
            'age' => 17
         ],
      ];

      IteratorUtility::sortArrayByColumn($persons, 'age');

   returns

   .. code-block:: php

      [
         [
            'name' =>  'michael' 
            'age' =>  17
         ],
         [
            'name' =>  'klaus' 
            'age' =>  21
         ],
         [
            'name' =>  'peter' 
            'age' =>  34
         ]
      ]

------------------------------------

.. php:method:: extractValuesViaGetMethod($listOfObjects, $methodName)

   Extracts properties from objects via their get method.

   :param array $listOfObjects: List of objects.
   :param string $methodName: The name of the method, without the beginning 'get'.
   :returns: Extracted values.

   **Example:**

   .. code-block:: php

      class Person
      {
         private string $name;

         function __construct($name)
         {
            $this->name = $name;
         }

         public function getName(): string
         {
            return $this->name;
         }
      };

      /* ... */


      $persons = [ new Person('klaus'), new Person('peter')];
      $names = IteratorUtility::extractValuesViaGetMethod($persons, 'name');

      var_dump($names);

   returns

   .. code-block:: php

      ['klaus', 'peter']

------------------------------------

.. php:method:: extractValuesViaGetMethodFlattened($listOfObjects, $methodName, $keepKeys = false)

   Extracts properties from objects via their get method and flattens the result.

   :param array $listOfObjects: List of objects.
   :param string $methodName: The name of the method, without the beginning 'get'.
   :param bool $keepKeys: Keep keys in result. Will overwrite existing values for this key.
   :returns: Extracted flattened values.

   **Example:**

   .. code-block:: php

      class Element
      {
         private array $items;

         function __construct($items)
         {
            $this->items = $items;
         }

         public function getItems(): array
         {
            return $this->items;
         }
      };

      /* ... */


      $elements = [
         new Element(
            [
               'a' => 1,
               'b' => 2,
               'c' => 3,
               'd' => 4
            ]
         ),
         new Element([
            'e' => 5,
            'f' => 6,
            'g' => 7,
            'a' => 8
         ])
      ];

      // without "keepKeys"
      
      $allItems = IteratorUtility::extractValuesViaGetMethodFlattened($elements, 'items');      
      var_dump($allItems);
      /*
         Result:
            0 =>  1
            1 =>  2
            2 =>  3
            3 =>  4
            4 =>  5
            5 =>  6
            6 =>  7
            7 =>  8
      */

      // with "keepKeys"

      $allItems = IteratorUtility::extractValuesViaGetMethodFlattened($elements, 'items', true);
      var_dump($allItems);
      /*
         Result:
            'a' =>  8
            'b' =>  2
            'c' =>  3
            'd' =>  4
            'e' =>  5
            'f' =>  6
            'g' =>  7
      */

------------------------------------

.. php:method:: callMethod($listOfObjects, $method)

   Calls a method in each object and returns the results.

   :param array $listOfObjects: List of objects.
   :param string $method: name of the method.
   :returns: List of method results.

   **Example:**

   .. code-block:: php

      class Element
      {
         private int $number;

         function __construct($number)
         {
            $this->number = $number;
         }

         public function add(): int
         {
            return $this->number + 1;
         }
      };

      /* ... */

      $numbers = [ new Element(5), new Element(2) ];

      $addedNumbers = IteratorUtility::callMethod($numbers, 'add');

      var_dump($addedNumbers);

   returns

   .. code-block:: php

      [6, 3]

------------------------------------

.. php:method:: compact($arr)

   Returns a copy of the list with all falsy values (null, 0, '') removed. 

   :param array $arr: List with values.
   :returns: List without falsy values

   **Example:**

   .. code-block:: php

      IteratorUtility::compact(['hello', null, '', 0, 'world'])

   returns

   .. code-block:: php

      ['hello', 'world']

------------------------------------

.. php:method:: flatten($arr)

   Flattens a nested array.

   :param array $arr: The nested array.
   :returns: The flat array.

   **Example:**

   .. code-block:: php

      IteratorUtility::flatten([
         [1, 2, 3],
         [4, 5],
         [6]
      ]);

   returns

   .. code-block:: php

      [1, 2, 3, 4, 5, 6]

------------------------------------

.. php:method:: filter($arr, $func)

   Filters a list with a closure condition

   :param array $arr: The array.
   :param callable $func: The filter closure.
   :returns: The filtered result.

   **Example:**
   
   .. code-block:: php

      IteratorUtility::filter(
         [1, 2, 3, 4, 5, 6],
         function($value) {            
            return (bool) ($value % 2);
         }
      );

   returns

   .. code-block:: php

      [1, 3, 5]


------------------------------------

.. php:method:: map($arr, $func)

   Iterates each item and maps the new value throught a function.

   :param array $arr: The array.
   :param callable $func The transformation function (receives three parametes: 1. value, 2. key, 3. current transformated list).
   :returns: The mapped array.

   **Example:**
   
   .. code-block:: php

      IteratorUtility::map(
         [1, 2, 3, 4, 5, 6],
         function($value) {            
            return $value * 2;
         }
      );

   returns

   .. code-block:: php

      [2, 4, 6, 8, 10, 12]



------------------------------------

.. php:method:: first($arr)

   Returns the first element from a list.
   
   :param array $arr: The array.
   :returns: First element.

   **Example:**
   
   .. code-block:: php

      IteratorUtility::first(['hello', 'world'])

   returns

   .. code-block:: php

      'hello'

------------------------------------

.. php:method:: pluck($arr, $key)

   Extracts a the value of a certain key.

   :param array $arr: The array.
   :param string $key: The key.
   :returns: The extracted values.

   **Example:**
   
   .. code-block:: php

      $persons = [
         [
            'name' => 'peter',
            'age' => 34
         ],
         [
            'name' => 'klaus',
            'age' => 21
         ],
         [
            'name' => 'michael',
            'age' => 17
         ],
      ];

      IteratorUtility::pluck($persons, 'name');

   returns

   .. code-block:: php

      ['peter' , 'klaus' , 'michael']

   

------------------------------------

.. php:method:: contains($arr, $needle)

   Checks if a value exist in an array.

   :param array $arr: The array.
   :param string $needle: The value to check.
   :returns: Check result.

   **Example:**

   .. code-block:: php

      // true
      IteratorUtility::contains([1, 2, 3, 4, 5, 6], 2);

      // false
      IteratorUtility::contains([1, 2, 3, 4, 5, 6], 7);

------------------------------------

.. php:method:: whitelist($array, $whitelist)

   Returns only array entries listed in a whitelist.

   :param array $array: Original array to operate on.
   :param array $whitelist: Keys you want to keep.
   :returns: The whitelisted entries.

   **Example:**

   .. code-block:: php

      IteratorUtility::whitelist([
         'a' => 1,
         'b' => 2,
         'c' => 3,
         'd' => 4
      ], ['a', 'c']);

   returns

   .. code-block:: php

      [
         'a' =>  1,
         'c' =>  3
      ]

------------------------------------

.. php:method:: whitelistList($array, $whitelist)

   Returns only nested array entries listed in a whitelist.

   :param array $array: List of nested array items
   :param array $whitelist: Keys you want to keep.
   :returns: The whitelisted entries.

   **Example:**

   .. code-block:: php

      IteratorUtility::whitelistList([
         [
            'a' => 1,
            'b' => 2,
            'c' => 3,
            'd' => 4
         ],
         [
            'a' => 5,
            'b' => 6,
            'c' => 7,
            'd' => 8
         ]
      ], ['a', 'c'])

   returns

   .. code-block:: php

      [
         [
            'a' =>  1,
            'c' =>  3
         ],
         [
            'a' =>  5,
            'c' =>  7
         ]
      ] 

------------------------------------

.. php:method:: indexBy($arr, $key)

   Create a indexed list based on key values.

   :param array $array: The array.
   :param string $key: The index key name.
   :returns: The indexed List.

   **Example:**

   .. code-block:: php

      $persons = [
         [
            'id' => 5,
            'name' => 'peter',
         ],
         [
            'id' => 2,
            'name' => 'klaus',
         ],
         [
            'id' => 3,
            'name' => 'michael',
         ],
      ];      

      var_dump($persons);
      /*
         Result: 
         [
            0 => [
               'id' =>  5
               'name' =>  'peter'
            ],
            1 => [
               'id' =>  2
               'name' =>  'klaus'
            ],
            2 => [
               'id' =>  3
               'name' =>  'michael'
            ]
         ]
      */

      $indexedPersons = IteratorUtility::indexBy($persons, 'id');

      var_dump($indexedPersons);
      /*
         Result: 
         [
            5 => [
               'id' =>  5
               'name' =>  'peter'
            ],
            2 => [
               'id' =>  2
               'name' =>  'klaus'
            ],
            3 => [
               'id' =>  3
               'name' =>  'michael'
            ]
         ]
      */