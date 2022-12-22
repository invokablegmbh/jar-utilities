.. include:: /Includes.rst.txt
.. index:: DataProcessors
.. _dataprocessors-LinkProcessor:

==================
Link Processor
==================

Creates a link on a (mainly) dataprocessed element

============================    ========
Parameter                       Description
============================    ========
page                            PID of the target page
title                           Linktitle
class                           Link CSS Class
target                          Linktarget
params                          Additional params which will prepend as GET-Parameters
as                              If set, the link would be stored in this key, otherwise the key "link" is used
flat                            if true - direct return the URL otherwise return link object (default false)
============================    ========

Output
------

Same as :ref:`services-reflection-examples-link`

Example Case
--------------

Building detail-links for a joblist

.. code-block:: typoscript

   tt_content.tx_myextension_joblistctype {
      dataProcessing {
         10 = TYPO3\CMS\Frontend\DataProcessing\DatabaseQueryProcessor
         10 {
            table = tx_myextension_jobs
            pidInList = 266
            as = jobs
            dataProcessing {
               10 = Jar\Utilities\DataProcessing\LinkProcessor
               10 {
                  page = 187
                  title = TEXT
                  title.data = field:title
                  as = detail_link
                  params {
                     jobid = TEXT
                     jobid.data = field:uid
                  }
               }
            }
         }
      }
   }
