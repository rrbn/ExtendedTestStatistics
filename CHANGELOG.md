# Change Log

## Version 1.8.0 (2024-03-19)
- Updated for ILIAS 8
- Fixed PHP 8 issues
- Added types and removed globals except DIC
- New place for add-ons inside the plugin directory
- Removed the evaluation of STACK question (is now available as add-on)

## Version 1.7.2 (2024-01-24)
- added diagrams for discrimination index, facility index, percentage of points, standard deviation
- simplified the calculation of the discrimination index (produces same results)
- removed option to create from getParticipant() getQuestion(), getAnswer() of ilExteStatSourceData
- added return types to the functions of ilExteStatSourceData (preparation for ilias 8)

## Version 1.7.1 (2023-11-29)
- fixed saving of evaluation parameters (thx to jcopado)
- added diagram for percentage of correct answers

## Version 1.6.1 (2022-01-11)
- Removed the deprecated PHPExcel library (replaced by PhpSpreadsheet from ILIAS)

## Version 1.6.0 (2021-12-01)
- Updated for ILIAS 7

## Version 1.5.1 (2021-07-12)
- Variant-regardless Option is now sorted.
- Solved a typo.

## Version 1.5.0 (2021-06-21)
- Includes extra features for STACK Questions.

## Version 1.4.1 (2020-12-02)
- Includes support for STACK Questions.

## Version 1.3.0 (2019-07-17)
- Update for ILIAS 5.4

## Version 1.2.1 (2018-06-12)
Thanks to Christoph Jobst!
- Add selection of first evaluated pass
- Allow strings in options
- Allow custom HTML in evaluations

## Version 1.2.0 (2018-03-29)
- Beta version for ILIAS 5.3

## Version 1.1.1 (2017-09-12)
- Permission "Statistics" is checked instead of "Write"
- Caching of basic and calculated data
- Diagram support
- Bar diagrams for single/multiple choice options

## Version 1.0.0 (2017-03-20)
- Support ILIAS 5.0 to ILIAS 5.2
- Improved Excel export
- Improved quality signs
- Configuration parameters for evaluations
- Selection of evaluated pass (scored, last, best)
- Added 'uncertain' status for calculations in random tests
