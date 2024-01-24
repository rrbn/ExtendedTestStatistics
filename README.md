ILIAS Extended Test Statistics plugin
=====================================

Copyright (c) 2021 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

- Authors:   Fred Neumann <fred.neumann@ili.fau.de>, Jesus Copado <jesus.copado@ili.fau.de>
- Forum: http://www.ilias.de/docu/goto_docu_frm_3474_4315.html
- Bug Reports: http://www.ilias.de/mantis (Choose project "ILIAS plugins" and filter by category "ExtendedTestStatistics")

This plugin includes the PHPExcel library, see lib/PHPExcel-1.8/license.md


Installation
------------

When you download the Plugin as ZIP file from GitHub, please rename the extracted directory to *ExtendedTestStatistics*
(remove the branch suffix, e.g. -master).

1. Copy the ExtendedTestStatistics directory to your ILIAS installation at the followin path
(create subdirectories, if neccessary): Customizing/global/plugins/Services/UIComponent/UserInterfaceHook
2. Go to Administration > Plugins
3. Choose action  "Update" for the ExtendedTestStatistics plugin
4. Choose action  "Activate" for the ExtendedTestStatistics plugin

Configuration
-------------

5. Choose action "Configure" 
6. Select which evaluations should be presented to all users with access to the test statistics,
   which should only be shown to platform admins and which should be hidden at all.
7. Set the calculation parameters and quality thresholds for single evaluations.

Usage
-----
This plugin replaces the sub tab "Aggregated Test Results" of the "Statistics" tab in a test 
with two new sub tabs "Aggregated Test Results" and "Aggregated Question Results"

Both tabs show tables with statistical figures related to the test or its question. The values that are
calculated by standard ILIAS are always shown. Other values are shown based on the setting in the plugin 
configuration.

Additional test evaluations:
* Coefficient of Internal Consistency (Cronbach's alpha)
* Mean Score
* Median Score
* Standard Deviation

Additional question evaluations:
* Discrimination index
* Facility Index
* Standard deviation
* List of chosen options for single and multiple choice
* Percentage of Correct Answers (shown as diagram on the question overview)

The evaluation titles have tooltips with further explanations. Some values have read/yellow/green signs to indicate their quality. 
Values displayed in italics are considered as 'uncertain' because they are calculated in a random test. Nevertheless they 
may provide useful hints. Values may also have comments appearing as tooltips.

The toolbar above the tables allows to select of they are calculated for the scored pass (standard), the best pass or the last pass of each 
participant.

Export
------
The aggregated results can be exported to CSV or MS Excel files. A CSV file contains only one table with either test or questions overview
and includes no comments or quality signs. An MS Excel file contains both tables on separate sheets with an extra sheet for the legend. It
includes all comments and color bachgrounds for the differend quality signs. An MS Excel file with details contains an extra sheet for any
detailed evaluation.

Diagrams
--------
Since version 1.1.0 the generation of a diagram is supported. An evaluation has to provide a details table that is used to generate the diagram.
One column of this table is used for the labels and one or some other columns are used as the data series of the diagram. The
diagram is shown to the details screen if the evaluations supports this.


Known Issues
------------
* The extended statistics are currently not calculated for tests with setting 'Question Queue - All Questions from a Question Pool'.
  This, however, is also the case for the standard statistics of ILIAS.

* The calculation of extended evaluations takes care of performance, e.g. the additional question evaluations are calculated on a separate
  page only for the questions that are shown. But the basic values from ILIAS are calculated for all, which takes time for large tests.
  This is now improved by caching.

Extension
---------
You may extend the plugin with your own evaluations. See the following add-on as an example:
https://github.com/ilifau/ExampleEvaluations

Version History
===============

Version 1.7.2 (2024-01-24)
-------------------------
- added diagrams for discrimination index, facility index, percentage of points, standard deviation
- simplified the calculation of the discrimination index (produces same results)
- removed option to create from getParticipant() getQuestion(), getAnswer() of ilExteStatSourceData
- added return types to the functions of ilExteStatSourceData (preparation for ilias 8)

Version 1.7.1 (2023-11-29)
--------------------------
- fixed saving of evaluation parameters (thx to jcopado)
- added diagram for percentage of correct answers

Version 1.6.1 (2022-01-11)
--------------------------
- Removed the deprecated PHPExcel library (replaced by PhpSpreadsheet from ILIAS)

Version 1.6.0 (2021-12-01)
--------------------------
- Updated for ILIAS 7

Version 1.5.1 (2021-07-12)
--------------------------
- Variant-regardless Option is now sorted.
- Solved a typo.


Version 1.5.0 (2021-06-21)
--------------------------
- Includes extra features for STACK Questions.

Version 1.4.1 (2020-12-02)
--------------------------
- Includes support for STACK Questions.

Version 1.3.0 (2019-07-17)
--------------------------
- Update for ILIAS 5.4


Version 1.2.1 (2018-06-12)
--------------------------
Thanks to Christoph Jobst!
- Add selection of first evaluated pass
- Allow strings in options
- Allow custom HTML in evaluations

Version 1.2.0 (2018-03-29)
--------------------------
Beta version for ILIAS 5.3


Version 1.1.1 (2017-09-12)
--------------------------
- Permission "Statistics" is checked instead of "Write"

- Caching of basic and calculated data
- Diagram support
- Bar diagrams for single/multiple choice options



Version 1.0.0 (2017-03-20)
-------------
- Support ILIAS 5.0 to ILIAS 5.2
- Improved Excel export
- Improved quality signs
- Configuration parameters for evaluations
- Selection of evaluated pass (scored, last, best)
- Added 'uncertain' status for calculations in random tests
