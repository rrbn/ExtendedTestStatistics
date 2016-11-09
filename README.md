ILIAS Extended Test Statistics plugin
=====================================

Copyright (c) 2016 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv2, see LICENSE

- Author:   Fred Neumann <fred.neumann@ili.fau.de>, Jesus Copado <jesus.copado@ili.fau.de>


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
6. Select which additional evaluations should be presented to all users with access to the test statistics,
   which should only be shown to platform admins and which should be hidden at all.

Usage
-----
This plugin replaces the sub tab "Aggregated Test Results" of the "Statistics" tab in a test 
with two new sub tabs "Aggregated Test Results" and "Aggregated Question Results"

Both show tables with statistical figures related to the test or its question. The values that are
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
* List of chosen options for single / multiple choice
* Standard deviation

The evaluation titles nave tooltips with further explanations. Some values have read/yellow/green
markers to indicate their quality and further comments. Some evaluations (the list of chosen options) 
have a detailed view.

Version History
===============

Version 0.9.0
-------------
First beta version for community testing.