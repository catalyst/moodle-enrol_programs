<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace enrol_programs\rb\source;

use core_tag_area;
use html_writer;
use enrol_programs\local\allocation;
use enrol_programs\local\program;
use enrol_programs\local\content\item;
use enrol_programs\local\content\top;
use enrol_programs\local\content\set;
use enrol_programs\local\content\course;
use local_reportbuilder\dblib\base;
use local_reportbuilder\rb_base_source;
use local_reportbuilder\rb_column_option;
use local_reportbuilder\rb_content_option;
use local_reportbuilder\rb_filter_option;
use local_reportbuilder\rb_join;
use local_reportbuilder\rb_param_option;
use moodle_url;

/**
 * Program allocations source.
 *
 * NOTE: this source was incorrectly called "completions" before,
 * there are just a few columns related to completion here.
 *
 * @package    enrol_programs
 * @copyright  2024 Open LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link       https://www.openlms.net/
 */
final class allocations extends rb_base_source {

    public function __construct() {
        $this->base = '{enrol_programs_allocations}';
        parent::__construct();
        $this->registercustomfieldhandler(\enrol_programs\customfield\fields_handler::create(),
            'base.id', 'program_customfields', 'base',
            get_string('pluginname', 'enrol_programs'));
    }

    /**
     * Returns name of the source.
     * @return string
     */
    public static function get_sourcetitle(): string {
        return get_string('rb_source_allocation', 'enrol_programs');
    }

    /**
     * Returns unique internal source name.
     *
     * @return string
     */
    public static function get_sourcename(): string {
        return 'enrol_programs_allocations';
    }

    protected function define_joinlist() {
        $joinlist = [];

        $this->add_user_table_to_joinlist($joinlist, 'base', 'userid');
        $this->add_program_table_to_joinlist($joinlist, 'base', 'programid', 'INNER');
        $this->add_program_item_table_to_joinlist($joinlist, 'base', 'programid');
        $this->add_course_table_to_joinlist($joinlist, 'program_item', 'courseid');
        $this->add_program_visible_cohort_table_to_joinlist($joinlist, 'base', 'programid');
        $this->add_program_context_table_to_joinlist($joinlist, 'program', 'contextid');
        $this->add_program_source_table_to_joinlist($joinlist, 'base', 'sourceid', 'INNER');
        $this->add_course_category_table_to_joinlist($joinlist, 'context', 'instanceid');
        $this->add_tag_tables_to_joinlist('enrol_programs', 'program', $joinlist, 'base', 'programid');

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = [
            new rb_column_option(
                'program_completion',
                'dateallocated',
                get_string('rb_dateallocated', 'enrol_programs'),
                'base.timeallocated',
                ['displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp']
            ),
            new rb_column_option(
                'program_completion',
                'allocationtype',
                get_string('rb_programallocationtypes', 'enrol_programs'),
                'programsource.type',
                [
                    'displayfunc' => 'program_allocation_type',
                    'joins' => ['programsource']
                ]
            ),
            new rb_column_option(
                'program_completion',
                'timecompletedsinceenrol',
                get_string('rb_timetocompletesinceenrol', 'enrol_programs'),
                "CASE WHEN base.timecompleted IS NULL OR base.timecompleted = 0 THEN null
                      ELSE base.timecompleted - base.timeallocated END",
                [
                    'displayfunc' => 'duration_hours_minutes',
                    'dbdatatype' => 'integer'
                ]
            ),
            new rb_column_option(
                "program_completion",
                "progresspercent",
                get_string('rb_progresspercent', 'enrol_programs'),
                "base.id",
                [
                    'displayfunc' => 'program_progress',
                    'extrafields' => [
                        'program_id' => "base.programid",
                    ]
                ]
            ),
            new rb_column_option(
                "program_completion",
                "coursesall",
                get_string('rb_coursesall', 'enrol_programs'),
                "program_item.courseid",
                [
                    'joins' => ['program_item', 'course'],
                    'grouping' => 'comma_list_unique',
                    'displayfunc' => 'program_content',
                    'extrafields' => [
                        'allocation_id' => 'base.id',
                        'program_id' => "base.programid",
                        'user_id' => "base.userid"
                    ],
                    'nosort' => true
                ]
            ),
        ];

        $this->add_user_fields_to_columns($columnoptions);
        $this->add_program_fields_to_columns($columnoptions);
        $this->add_program_category_fields_to_columns($columnoptions);
        $this->add_core_tag_fields_to_columns('enrol_programs', 'program', $columnoptions);

        $columnoptions = array_merge($columnoptions, $this->programcompletion_cols('base'));

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $sourcenames = allocation::get_source_names();

        $enroltypeoptions = $sourcenames;
        $filteroptions = [
            new rb_filter_option(
                'program_completion',
                'dateallocated',
                get_string('rb_dateallocated', 'enrol_programs'),
                'date'
            ),
            new rb_filter_option(
                'program_completion',
                'allocationtype',
                get_string('rb_programallocationtype', 'enrol_programs'),
                'select',
                [
                    'selectchoices' => $enroltypeoptions,
                    'simplemode' => true
                ]
            ),
        ];

        $filteroptions = array_merge($filteroptions, $this->programcompletion_filters());

        $this->add_user_fields_to_filters($filteroptions);
        $this->add_program_fields_to_filters($filteroptions);
        $this->add_program_item_fields_to_filters($filteroptions);
        $this->add_program_category_fields_to_filters($filteroptions);
        $this->add_core_tag_fields_to_filters('core', 'course', $filteroptions);

        return $filteroptions;
    }

    protected function define_contentoptions() {
        return [
            new rb_content_option(
                'date',
                get_string('rb_completiondate', 'enrol_programs'),
                'base.timecompleted'
            ),
            new rb_content_option(
                'date',
                get_string('rb_programduedate', 'enrol_programs'),
                'base.timedue',
                null,
                ['optionidentifier' => 'duedate']
            ),
            new rb_content_option(
                'user',
                get_string('user', 'local_reportbuilder'),
                ['userid' => 'auser.id', 'city' => 'auser.city', 'country' => 'auser.country',
                    'institution' => 'auser.institution', 'department' => 'auser.department'],
                ['auser']
            )
        ];
    }

    protected function define_paramoptions() {
        return [
            new rb_param_option(
                'userid',
                'base.userid',
                null
            ),
            new rb_param_option(
                'programid',
                'base.programid'
            ),
        ];
    }

    protected function define_defaultcolumns() {
        return [
            [
                'type' => 'user',
                'value' => 'namelink',
            ],
            [
                'type' => 'program',
                'value' => 'programlink',
            ],
            [
                'type' => 'program_completion',
                'value' => 'status',
            ],
            [
                'type' => 'program_completion',
                'value' => 'completeddate',
            ],
        ];
    }

    protected function define_defaultfilters() {
        return [
            [
                'type' => 'user',
                'value' => 'fullname',
            ],
            [
                'type' => 'program',
                'value' => 'fullname',
                'advanced' => 1,
            ],
            [
                'type' => 'program_category',
                'value' => 'path',
                'advanced' => 1,
            ],
            [
                'type' => 'program_completion',
                'value' => 'completeddate',
                'advanced' => 1,
            ],
            [
                'type' => 'program_completion',
                'value' => 'status',
                'advanced' => 1,
            ],
        ];
    }

    /**
     * Adds the program table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'program id' field
     * @param string $field Name of program id field to join on
     * @param string $jointype Type of Join (INNER, LEFT, RIGHT)
     */
    protected function add_program_table_to_joinlist(&$joinlist, $join, $field, $jointype = 'LEFT') {
        $joinlist[] = new rb_join(
            'program',
            $jointype,
            '{enrol_programs_programs}',
            "program.id = $join.$field",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            [$join]
        );
    }

    /**
     * Adds the program item table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'program id' field
     * @param string $field Name of program id field to join on
     * @param string $jointype Type of Join (INNER, LEFT, RIGHT)
     */
    protected function add_program_item_table_to_joinlist(&$joinlist, $join, $field, $jointype = 'LEFT') {
        $joinlist[] = new rb_join(
            'program_item',
            $jointype,
            '{enrol_programs_items}',
            "program_item.programid = $join.$field",
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            [$join]
        );
    }

    /**
     * Adds the visible program cohorts table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'program id' field
     * @param string $field Name of program id field to join on
     * @param string $jointype Type of Join (INNER, LEFT, RIGHT)
     */
    protected function add_program_visible_cohort_table_to_joinlist(&$joinlist, $join, $field, $jointype = 'LEFT') {
        $joinlist[] = new rb_join(
            'programcohort',
            'LEFT',
            // Subquery as table name.
            "(SELECT cohort.id, cohort.name, cohort.visible, program_cohort.programid
                FROM {enrol_programs_cohorts} program_cohort
           LEFT JOIN {cohort} cohort ON cohort.id = program_cohort.cohortid)",
            "programcohort.programid = $join.$field and programcohort.visible = 1",
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            [$join]
        );
    }

    /**
     * Adds the context table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'program id' field
     * @param string $field Name of program id field to join on
     * @param string $jointype Type of Join (INNER, LEFT, RIGHT)
     */
    protected function add_program_context_table_to_joinlist(&$joinlist, $join, $field, $jointype = 'LEFT') {
        $joinlist[] = new rb_join(
            'context',
            $jointype,
            '{context}',
            "context.id = $join.$field",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            [$join]
        );

    }

    /**
     * Adds the program source table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'program id' field
     * @param string $field Name of program id field to join on
     * @param string $jointype Type of Join (INNER, LEFT, RIGHT)
     */
    protected function add_program_source_table_to_joinlist(&$joinlist, $join, $field, $jointype = 'LEFT') {
        $joinlist[] = new rb_join(
            'programsource',
            $jointype,
            '{enrol_programs_sources}',
            "programsource.id = $join.$field",
            null,
            [$join]
        );

    }

    /**
     * Adds the tag tables to the $joinlist array
     *
     * @param string $component component for the tag
     * @param string $itemtype tag itemtype
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     $type table
     * @param string $field Name of course id field to join on
     */
    protected function add_tag_tables_to_joinlist($component, $itemtype, &$joinlist, $join, $field) {
        $idlist = base::getbdlib()->sql_group_concat(base::getbdlib()
            ->sql_cast_2char('t.id'), '|');
        $joinlist[] = new rb_join(
            'tagids',
            'LEFT',
            // Subquery as table name.
            "(SELECT til.id AS tilid, {$idlist} AS idlist
                FROM {enrol_programs_programs} til
           LEFT JOIN {tag_instance} ti ON til.id = ti.itemid AND ti.itemtype = '{$itemtype}'
           LEFT JOIN {tag} t ON ti.tagid = t.id AND t.isstandard = '0'
            GROUP BY til.id)",
            "tagids.tilid = {$join}.{$field}",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );

        $namelist = base::getbdlib()->sql_group_concat(base::getbdlib()
            ->sql_cast_2char('t.name'), ', ');
        $joinlist[] = new rb_join(
            'tagnames',
            'LEFT',
            // Subquery as table name.
            "(SELECT tnl.id AS tnlid, {$namelist} AS namelist
                FROM {enrol_programs_programs} tnl
           LEFT JOIN {tag_instance} ti ON tnl.id = ti.itemid AND ti.itemtype = '{$itemtype}'
           LEFT JOIN {tag} t ON ti.tagid = t.id AND t.isstandard = '0'
            GROUP BY tnl.id)",
            "tagnames.tnlid = {$join}.{$field}",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );

        // Create a join for each tag in the collection.
        $tagcollectionid = core_tag_area::get_collection($component, $itemtype);
        $tags = self::get_tags($tagcollectionid);
        foreach ($tags as $tag) {
            $tagid = $tag->id;
            $name = "{$itemtype}_tag_$tagid";
            $joinlist[] = new rb_join(
                $name,
                'LEFT',
                '{tag_instance}',
                "($name.itemid = $join.$field AND $name.tagid = $tagid " .
                "AND $name.itemtype = '{$itemtype}')",
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                $join
            );
        }

        return true;
    }

    /**
     * Adds some common program info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $join Name of the join that provides the 'program' table
     */
    protected function add_program_fields_to_columns(&$columnoptions, $join = 'program') {
        global $DB;

        $columnoptions[] = new rb_column_option(
            'program',
            'fullname',
            get_string('rb_programname', 'enrol_programs'),
            "$join.fullname",
            array('joins' => $join,
                'dbdatatype' => 'char',
                'outputformat' => 'text')
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'programlink',
            get_string('rb_programnamelinked', 'enrol_programs'),
            "$join.fullname",
            array(
                'joins' => $join,
                'displayfunc' => 'link_program',
                'defaultheading' => get_string('rb_programname', 'enrol_programs'),
                'extrafields' => array('program_id' => "$join.id",
                    'program_public' => "$join.public")
            )
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'programexpandlink',
            get_string('rb_programexpandlink', 'enrol_programs'),
            "$join.fullname",
            array(
                'joins' => $join,
                'displayfunc' => 'program_expand',
                'defaultheading' => get_string('programname', 'enrol_programs'),
                'extrafields' => array(
                    'program_id' => "$join.id",
                    'allocation_id' => "base.id"
                )
            )
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'public',
            get_string('rb_programvisible', 'enrol_programs'),
            "$join.public",
            array(
                'joins' => $join,
                'displayfunc' => 'yes_no'
            )
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'visiblecohorts',
            get_string('rb_visiblecohorts', 'enrol_programs'),
            "programcohort.name",
            array(
                'joins' => 'programcohort',
                'dbdatatype' => 'char',
                'outputformat' => 'text'
            )
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'idnumber',
            get_string('rb_programidnumber', 'enrol_programs'),
            "$join.idnumber",
            array('joins' => $join,
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'char',
                'outputformat' => 'text')
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'id',
            get_string('rb_programid', 'enrol_programs'),
            "$join.id",
            array('joins' => $join)
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'timecreated',
            get_string('rb_programedatecreated', 'enrol_programs'),
            "$join.timecreated",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            )
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'startdate',
            get_string('rb_programstartdate', 'enrol_programs'),
            "$join.timeallocationstart",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            )
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'enddate',
            get_string('rb_programenddate', 'enrol_programs'),
            "$join.timeallocationend",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            )
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'duedate',
            get_string('rb_programduedate', 'enrol_programs'),
            "base.timedue",
            array(
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            )
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'name_and_summary',
            get_string('rb_programnameandsummary', 'enrol_programs'),
            // Case used to merge even if one value is null.
            "CASE WHEN $join.fullname IS NULL THEN $join.description
                WHEN $join.description IS NULL THEN $join.fullname
                ELSE " . $DB->sql_concat("$join.fullname", "'" . html_writer::empty_tag('br') . "'",
                "$join.description") . ' END',
            array(
                'joins' => $join,
                'displayfunc' => 'editor_textarea',
                'extrafields' => array(
                    'filearea' => '\'description\'',
                    'component' => '\'program\'',
                    'context' => '\'context_program\'',
                    'recordid' => "$join.id"
                )
            )
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'summary',
            get_string('rb_programsummary', 'enrol_programs'),
            "$join.description",
            array(
                'joins' => $join,
                'displayfunc' => 'editor_textarea',
                'extrafields' => array(
                    'format' => "$join.descriptionformat",
                    'filearea' => '\'description\'',
                    'component' => '\'program\'',
                    'context' => '\'context_program\'',
                    'recordid' => "$join.id"
                ),
                'dbdatatype' => 'text',
                'outputformat' => 'text'
            )
        );
    }

    /**
     * Adds some common program category info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     */
    protected function add_program_category_fields_to_columns(&$columnoptions) {
        $columnoptions[] = new rb_column_option(
            'program_category',
            'name',
            get_string('rb_programcategory', 'enrol_programs'),
            "context.id",
            array('joins' => 'course_category',
                'displayfunc' => 'program_category_name',
                'extrafields' => array(
                    'category_name' => "course_category.name",
                ),
                'dbdatatype' => 'char',
                'outputformat' => 'text')
        );
        $columnoptions[] = new rb_column_option(
            'program_category',
            'idnumber',
            get_string('rb_programcategoryidnumber', 'enrol_programs'),
            "course_category.idnumber",
            array(
                'joins' => 'course_category',
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'char',
                'outputformat' => 'text'
            )
        );
        $columnoptions[] = new rb_column_option(
            'program_category',
            'id',
            get_string('rb_programcategoryid', 'enrol_programs'),
            "course_category.id",
            array('joins' => 'course_category')
        );
    }

    /**
     * Adds program filters to the $filteroptions array
     *
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     */
    protected function add_program_fields_to_filters(&$filteroptions, $join = 'program') {
        $filteroptions[] = new rb_filter_option(
            'program',
            'fullname',
            get_string('rb_programname', 'enrol_programs'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'program',
            'idnumber',
            get_string('rb_programidnumber', 'enrol_programs'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'program',
            'public',
            get_string('rb_programvisible', 'enrol_programs'),
            'select',
            array(
                'selectchoices' => array(0 => get_string('no'), 1 => get_string('yes')),
                'simplemode' => true
            )
        );
        $filteroptions[] = new rb_filter_option(
            'program',
            'timecreated',
            get_string('rb_programedatecreated', 'enrol_programs'),
            'date',
            array('castdate' => true)
        );
        $filteroptions[] = new rb_filter_option(
            'program',
            'duedate',
            get_string('rb_programduedate', 'enrol_programs'),
            'date',
            array('castdate' => true)
        );
        $filteroptions[] = new rb_filter_option(
            'program',
            'startdate',
            get_string('rb_programstartdate', 'enrol_programs'),
            'date',
            array('castdate' => true)
        );
        $filteroptions[] = new rb_filter_option(
            'program',
            'name_and_summary',
            get_string('rb_programnameandsummary', 'enrol_programs'),
            'textarea'
        );
        $filteroptions[] = new rb_filter_option(
            'program',
            'id',
            get_string('rb_programmultiitem', 'enrol_programs'),
            'program_multi'
        );
        $filteroptions[] = new rb_filter_option(
            'program',
            'idsingle',
            get_string('rb_programsingleitem', "enrol_programs"),
            'program_single',
            [],
            "$join.id",
            $join
        );
    }

    /**
     * Adds program content item filters to the $filteroptions array
     *
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     */
    protected function add_program_item_fields_to_filters(&$filteroptions) {
        $filteroptions[] = new rb_filter_option(
            'course',
            'fullname',
            get_string('coursename', 'local_reportbuilder'),
            'text',
            [
                'joins' => 'course',
            ],
            'course.fullname',
        );
    }

    /**
     * Adds program category filters to the $filteroptions array
     *
     * @param array &$columnoptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     */
    protected function add_program_category_fields_to_filters(&$filteroptions) {
        $filteroptions[] = new rb_filter_option(
            'program_category',
            'id',
            get_string('rb_programcategory', 'enrol_programs'),
            'select',
            array(
                'selectfunc' => 'course_categories_list',
                'attributes' => rb_filter_option::select_width_limiter(),
            )
        );
        $filteroptions[] = new rb_filter_option(
            'course_category',
            'path',
            get_string('rb_programcategorymultichoice', 'enrol_programs'),
            'category',
            [],
            'course_category.path',
            'course_category'
        );
    }

    /**
     * Convert a program name into a link.
     *
     * @param string $program
     * @param array $row
     * @param bool $isexport
     * @return string
     */
    public function rb_display_link_program($program, $row, $isexport = false) {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        if ($isexport) {
            return format_string($program);
        }

        $programid = $row->program_id;
        $attr = array('class' => $this->get_style_visibility($row, 'program_public'));
        $url = new moodle_url('/enrol/programs/my/program.php', array('id' => $programid));
        return html_writer::link($url, $program, $attr);
    }

    /**
     * Convert a program name into an expanding link.
     *
     * @param string $program
     * @param array $row
     * @param bool $isexport
     * @return string
     */
    public function rb_display_program_expand($program, $row, $isexport = false) {
        if ($isexport) {
            return format_string($program);
        }

        return \local_reportbuilder\rb\display\base::create_expand_link($program, 'program_details',
            array('expandprogramid' => $row->program_id, 'allocationid' => $row->allocation_id));

    }

    /**
     * Convert a program allocation type into allocation name.
     *
     * @param string $sourcetype
     * @param \stdClass $row
     * @return string
     */
    public function rb_display_program_allocation_type(string $sourcetype, \stdClass $row) : string {
        return get_string('source_' . $sourcetype, 'enrol_programs');
    }

    /**
     * Convert a program name into a percent.
     *
     * @param string $allocationid
     * @param array $row
     * @return string
     */
    public function rb_display_program_progress($allocationid, $row) {
        return sprintf('%.1f%%', $this->get_program_progress($allocationid, $row->program_id));
    }

    /**
     * Gets HTML for expanded program details
     *
     * @return string
     */
    public function rb_expand_program_details() {
        global $CFG, $DB, $USER;

        $programid = required_param('expandprogramid', PARAM_INT);
        $allocationid = required_param('allocationid', PARAM_INT);

        $program = $DB->get_record('enrol_programs_programs', ['id' => $programid], '*', MUST_EXIST);

        $formdata = array(
            // The following are required.
            'summary' => $program->description,
            'status' => null,
            'programid' => $programid,
            'allocationtype' => null,
            'progress' => null,
            'enddate' => $program->timeallocationend ?
                userdate($program->timeallocationend, get_string('strftimedatetimeshort')) : '',
            'action' => null,
            'url' => null,
        );

        $options = array('overflowdiv' => true, 'noclean' => true, 'para' => false);
        $summary = file_rewrite_pluginfile_urls($program->description, 'pluginfile.php',
            $program->contextid, 'program', 'summary', null);
        $summary = format_text($summary, $program->descriptionformat, $options, $programid);
        $formdata['summary'] = $summary;

        $formdata['action'] = get_string('rb_viewprogram', 'enrol_programs');
        $formdata['url'] = new moodle_url('/enrol/programs/my/program.php', array('id' => $programid));

        if ($allocationid > 0) {
            $allocation = $DB->get_record('enrol_programs_allocations', ['id' => $allocationid]);

            if ($allocation->timecompleted == 0) {
                $formdata['status'] = get_string('incomplete', 'rbsource_coursecompletion');
            } else if ($allocation->timecompleted > 0) {
                $formdata['status'] = get_string('complete', 'rbsource_coursecompletion');
            } else {
                $formdata['status'] = get_string('notstarted', 'rbsource_coursecompletion');
            }

            $formdata['allocationtype'] = $this->get_allocation_type($allocation->sourceid, $programid);
            $formdata['progress'] = sprintf('%.1f%%', $this->get_program_progress($allocationid, $programid));

        }

        $mform = new \enrol_programs\rb\form\program_expand(null, $formdata);
        return $mform->render();
    }

    /**
     * Creates a hierarchical display of pogram content items with associated icons
     *
     * @param string $itemids row of item program content ids
     * @param array $row
     * @param bool $isexport
     * @return string
     */
    public function rb_display_program_content($itemids, $row, $isexport) {
        global $DB, $CFG;
        require_once($CFG->libdir.'/completionlib.php');
        $top = program::load_content($row->program_id);
        $user = \core_user::get_user($row->user_id, '*', MUST_EXIST);
        $allocationid = $row->allocation_id;

        $programcontent = [];
        $getcontent = function(item $item, $itemdepth) use (&$getcontent, &$programcontent, $DB, $allocationid, $user, $isexport): void {
            $fullname = $item->get_fullname();
            $id = $item->get_id();
            if (!$isexport) {
                $padding = str_repeat('&nbsp;', $itemdepth * 6);
            } else {
                $padding = ' ';
            }

            $completion = '';
            if ($item instanceof set) {
                $completion = $item->get_sequencetype_info();
            }

            if ($item instanceof course) {
                $courseid = $item->get_courseid();
                $coursecontext = \context_course::instance($courseid, IGNORE_MISSING);
                if (!$coursecontext) {
                    $completionstatus = '';
                    $fullname .= ' <span class="badge badge-danger">' . get_string('errorcoursemissing', 'enrol_programs') . '</span>';
                } else {
                    $course = get_course($courseid);
                    if ($coursecontext) {
                        $canaccesscourse = false;
                        if (has_capability('moodle/course:view', $coursecontext)) {
                            $canaccesscourse = true;
                        } else {
                            if ($course && can_access_course($course, null, '', true)) {
                                $canaccesscourse = true;
                            }
                        }
                        if ($canaccesscourse) {
                            $detailurl = new \moodle_url('/course/view.php', ['id' => $courseid]);
                            $fullname = \html_writer::link($detailurl, $fullname);
                        }
                    }
                    $completionstatus = '';
                    $completion = $DB->get_record('enrol_programs_completions', ['itemid' => $item->get_id(), 'allocationid' => $allocationid]);
                    if (!empty($completion) && $completion->timecompleted > 0) {
                        $completionstatus = '<div class="badge badge-success">' . get_string('programstatus_completed', 'enrol_programs') . '</div>';
                    } else {
                        $params = array(
                            'userid' => $user->id,
                            'course' => $courseid,
                        );
                        $ccompletion = new \completion_completion($params);
                        $info = new \completion_info($course);

                        // Is course complete?
                        $coursecomplete = $info->is_course_complete($user->id);

                        if ($coursecomplete) {
                            $completionstatus = '<div class="badge badge-success">' . get_string('programstatus_completed', 'enrol_programs') . '</div>';
                        } else if (!$ccompletion->timestarted) {
                            $completionstatus = '<div class="badge badge-primary">' . get_string('notyetstarted', 'completion') . '</div>';
                        } else {
                            $completionstatus = '<div class="badge badge-light">' . get_string('inprogress', 'completion') . '</div>';;
                        }
                    }
                }
            }

            $itemdetails = '';
            if ($item instanceof top) {
                $icon = '<i class="icon fa fa-cubes fa-fw" title="' . get_string('program', 'enrol_programs') . '"></i>';
                if ($isexport) {
                    $icon = '';
                }
                $itemdetails .= $icon . ' ' . $completion;
            } else if ($item instanceof course) {
                $icon = '<i class="icon fa fa-graduation-cap fa-fw" title="' . get_string('course') . '"></i>';
                if ($isexport) {
                    $icon = '';
                }
                $itemdetails .= $padding . $icon . $fullname . ' ' . $completionstatus;
            } else {
                $icon = '<i class="icon fa fa-list fa-fw" title="' . get_string('set', 'enrol_programs') . '"></i>';
                if ($isexport) {
                    $icon = '';
                }
                $itemdetails .= $padding . $icon . $completion;
            }

            if (!$isexport) {
                $programcontent[] = '<div>' . $itemdetails . '</div>';
            } else {
                $programcontent[] =  $itemdetails;
            }

            foreach ($item->get_children() as $child) {
                $getcontent($child, $itemdepth + 1);
            }
        };
        $getcontent($top, 0);

        if (!$isexport) {
            $programcontent = implode("\n", $programcontent);
        } else {
            $programcontent = implode(', ', $programcontent);
        }

        return $programcontent;
    }



    /**
     * Get program allocation name.
     *
     * @param string $allocationid
     * @param string $programid
     *
     * @return string
     */
    protected function get_allocation_type($sourceid, $programid) {
        global $DB;

        $sources = $DB->get_records('enrol_programs_sources', ['programid' => $programid]);
        $sourcenames = allocation::get_source_names();
        $type = -1;

        foreach ($sources as $source) {
            if ($source->id == $sourceid) {
                $type = $source->type;
            }
        }

        return isset($sourcenames[$type]) ? $sourcenames[$type] : '';
    }

    /**
     * Get program progress as a percent
     *
     * @param string $allocationid
     * @param string $programid
     * @return float
     */
    protected function get_program_progress($allocationid, $programid) {
        global $DB;

        $allocation = $DB->get_record('enrol_programs_allocations', ['id' => $allocationid]);
        $top = program::load_content($programid);
        $completedcount = 0;
        $totalcompletions = 0;

        $renderercolumns = function(item $item, $itemdepth) use (&$renderercolumns,
            $allocation, &$DB, &$completedcount, &$totalcompletions): void {
            if (!($item instanceof top)) {
                $totalcompletions++;
                $completion = $DB->get_record('enrol_programs_completions',
                    ['itemid' => $item->get_id(), 'allocationid' => $allocation->id]);
                if ($completion) {
                    if (!empty($completion->timecompleted)) {
                        $completedcount++;
                    }
                }
            }

            foreach ($item->get_children() as $child) {
                $renderercolumns($child, $itemdepth + 1);
            }
        };

        $renderercolumns($top, 0);

        $percent = ($totalcompletions == 0) ? 0 : ($completedcount / $totalcompletions) * 100;

        return $percent;
    }

    public function programcompletion_cols($joinname = 'programscompletions') {
        $joins = [$joinname];

        return [
            new rb_column_option(
                'program_completion',
                'status',
                get_string('completionstatus', 'rbsource_userenrolments'),
                $this->get_program_completion_status_col_sql($joinname),
                [
                    'displayfunc' => 'program_status',
                    'joins' => $joins,
                    'extrafields' => array(
                        'allocation_id' => 'base.id',
                        'program_id' => "$joinname.programid"
                    )
                ]
            ),
            new rb_column_option(
                'program_completion',
                'iscomplete',
                get_string('iscompleteany', 'rbsource_userenrolments'),
                "CASE WHEN $joinname.timecompleted > 0 THEN 1 ELSE 0 END",
                [
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'boolean',
                    'defaultheading' => get_string('iscomplete', 'rbsource_userenrolments'),
                    'joins' => $joins
                ]
            ),
            new rb_column_option(
                'program_completion',
                'isnotcomplete',
                get_string('isnotcomplete', 'rbsource_userenrolments'),
                "CASE WHEN $joinname.timecompleted > 0 then 0 ELSE 1 END ",
                [
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'boolean',
                    'defaultheading' => get_string('isnotcomplete', 'rbsource_userenrolments'),
                    'joins' => $joins
                ]
            ),
            new rb_column_option(
                'program_completion',
                'isinprogress',
                get_string('isinprogress', 'rbsource_userenrolments'),
                "CASE WHEN $joinname.timecompleted = 0 then 1 ELSE 0 END ",
                [
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'boolean',
                    'defaultheading' => get_string('isinprogress', 'rbsource_userenrolments'),
                    'joins' => $joins
                ]
            ),
            new rb_column_option(
                'program_completion',
                'isnotyetstarted',
                get_string('isnotyetstarted', 'rbsource_userenrolments'),
                "CASE WHEN $joinname.timecompleted IS NULL THEN 1 ELSE 0 END",
                [
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'boolean',
                    'defaultheading' => get_string('isnotyetstarted', 'rbsource_userenrolments'),
                    'joins' => $joins
                ]
            ),
            new rb_column_option(
                'program_completion',
                'completeddate',
                get_string('rb_completiondate', 'enrol_programs'),
                "$joinname.timecompleted",
                ['displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp', 'joins' => $joins]
            ),
            new rb_column_option(
                'program_completion',
                'starteddate',
                get_string('datestarted', 'rbsource_userenrolments'),
                "$joinname.timestart",
                ['displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp', 'joins' => $joins]
            ),
            new rb_column_option(
                'program_completion',
                'timecompletedsincestart',
                get_string('timetocompletesincestart', 'rbsource_userenrolments'),
                "CASE WHEN $joinname.timecompleted IS NULL OR $joinname.timecompleted = 0 THEN null
                      ELSE $joinname.timecompleted - $joinname.timestart END",
                [
                    'displayfunc' => 'duration_hours_minutes',
                    'dbdatatype' => 'integer',
                    'joins' => $joins
                ]
            ),
        ];
    }

    public function programcompletion_filters($joinname = 'programcompletions') {
        $joins = [$joinname];
        return [
            new rb_filter_option(
                'program_completion',
                'completeddate',
                get_string('datecompleted', 'rbsource_userenrolments'),
                'date'
            ),
            new rb_filter_option(
                'program_completion',
                'starteddate',
                get_string('datestarted', 'rbsource_userenrolments'),
                'date'
            ),
            new rb_filter_option(
                'program_completion',
                'status',
                get_string('completionstatus', 'rbsource_userenrolments'),
                'multicheck',
                [
                    'selectfunc' => 'completion_status_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                    'joins' => $joins,
                    'showcounts' => [
                        'joins' => $joins,
                        'datafield' => $this->get_program_completion_status_col_sql($joinname)
                    ]
                ]
            ),
            new rb_filter_option(
                'program_completion',
                'iscomplete',
                get_string('iscompleteany', 'rbsource_userenrolments'),
                'select',
                [
                    'selectfunc' => 'yesno_list',
                    'simplemode' => true,
                ]
            ),
            new rb_filter_option(
                'program_completion',
                'isnotcomplete',
                get_string('isnotcomplete', 'rbsource_userenrolments'),
                'select',
                [
                    'selectfunc' => 'yesno_list',
                    'simplemode' => true,
                ]
            ),
            new rb_filter_option(
                'program_completion',
                'isinprogress',
                get_string('isinprogress', 'rbsource_userenrolments'),
                'select',
                [
                    'selectfunc' => 'yesno_list',
                    'simplemode' => true,
                ]
            ),
            new rb_filter_option(
                'program_completion',
                'isnotyetstarted',
                get_string('isnotyetstarted', 'rbsource_userenrolments'),
                'select',
                [
                    'selectfunc' => 'yesno_list',
                    'simplemode' => true,
                ]
            ),
        ];
    }

    protected function get_program_completion_status_col_sql($joinname = 'programcompletions') {
        $now = time();

        return "CASE
            WHEN ($joinname.timecompleted IS NOT NULL) THEN 1
            WHEN ($joinname.timecompleted IS NULL AND base.timestart > $now) THEN -1
            WHEN (base.timeend IS NOT NULL AND base.timeend < $now) THEN 0
            WHEN (base.timedue IS NOT NULL AND base.timedue < $now) THEN 0
            ELSE 0 END";
    }

    public function rb_filter_completion_status_list() {
        return [
            1 => get_string('complete', 'rbsource_coursecompletion'),
            0 => get_string('incomplete', 'rbsource_coursecompletion'),
            -1 => get_string('notstarted', 'rbsource_coursecompletion')
        ];
    }

    public function rb_display_program_status($status, $row) {
        global $DB;

        $program = $DB->get_record('enrol_programs_programs', ['id' => $row->program_id]);
        $allocation = $DB->get_record('enrol_programs_allocations', ['id' => $row->allocation_id]);

        return allocation::get_completion_status_plain($program, $allocation);
    }
}
