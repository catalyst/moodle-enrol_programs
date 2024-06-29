# Programs for Moodle

## Overview

_Programs for Moodle_ by Open LMS is a set of plugins that implements programs,
also known as learning pathways.

Main features include:

* program content created as a hierarchy of courses and course sets with flexible sequencing rules,
* _Program catalogue_ where students may browse available programs and related courses,
* multiple sources for allocation of students to programs,
* advanced program scheduling settings,
* efficient course enrolment automation,
* _My programs_ dashboard block,
* Training value custom course field,
* easy-to-use program management interface.

See [Use cases](./docs/en/use_cases.md) and [Program management](./docs/en/management.md)
documentation pages for more information.

## Installation

_Programs for Moodle_ consists of the following plugins published on GitHub:

* [moodle-enrol_programs](https://github.com/open-lms-open-source/moodle-enrol_programs)
* [moodle-block_myprograms](https://github.com/open-lms-open-source/moodle-block_myprograms)
* [moodle-local_openlms](https://github.com/open-lms-open-source/moodle-local_openlms)
* [moodle-customfield_training](https://github.com/open-lms-open-source/moodle-customfield_training)
* [moodle-certificateelement_programs](https://github.com/open-lms-open-source/moodle-certificateelement_programs)

There are no special installation instructions, _My programs_ block is automatically added
to all dashboards during installation.

Plugins are compatible with latest Moodle 4.3.x releases. Some features
that require Moodle core changes might be available only in OLMS Work 3.x.

Unsupported environments:

* PHP for Windows is not supported, use Windows Subsystem for Linux if necessary
* Oracle Databases are not supported

## Feedback

Before proposing a new feature or reporting problems please read
[Known problems and future plans](./docs/en/plans.md).

You can use [Feedback form](https://form.asana.com/?k=oMNm1HIGalQh5DD42RQ7OA&d=36833584313346)
if you want to leave feedback privately or feel free to comment on the original
announcement post on moodle.org.
