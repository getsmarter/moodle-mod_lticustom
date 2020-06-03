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
//
// This file is part of BasicLTI4Moodle
//
// BasicLTI4Moodle is an IMS BasicLTI (Basic Learning Tools for Interoperability)
// consumer for Moodle 1.9 and Moodle 2.0. BasicLTI is a IMS Standard that allows web
// based learning tools to be easily integrated in LMS as native ones. The IMS BasicLTI
// specification is part of the IMS standard Common Cartridge 1.1 Sakai and other main LMS
// are already supporting or going to support BasicLTI. This project Implements the consumer
// for Moodle. Moodle is a Free Open source Learning Management System by Martin Dougiamas.
// BasicLTI4Moodle is a project iniciated and leaded by Ludo(Marc Alier) and Jordi Piguillem
// at the GESSI research group at UPC.
// SimpleLTI consumer for Moodle is an implementation of the early specification of LTI
// by Charles Severance (Dr Chuck) htp://dr-chuck.com , developed by Jordi Piguillem in a
// Google Summer of Code 2008 project co-mentored by Charles Severance and Marc Alier.
//
// BasicLTI4Moodle is copyright 2009 by Marc Alier Forment, Jordi Piguillem and Nikolas Galanis
// of the Universitat Politecnica de Catalunya http://www.upc.edu
// Contact info: Marc Alier Forment granludo @ gmail.com or marc.alier @ upc.edu.

/**
 * This file defines the main lti configuration form
 *
 * @package mod_lticustom
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @author     Chris Scribner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/lticustom/locallib.php');

class mod_lticustom_mod_form extends moodleform_mod {

    public function definition() {
        global $PAGE, $OUTPUT, $COURSE;

        if ($type = optional_param('type', false, PARAM_ALPHA)) {
            component_callback("lticustomsource_$type", 'add_instance_hook');
        }

        $this->typeid = 0;

        $mform =& $this->_form;
        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));
        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('basicltiname','lticustom'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        // Adding the optional "intro" and "introformat" pair of fields.
        $this->standard_intro_elements(get_string('basicltiintro','lticustom'));
        $mform->setAdvanced('introeditor');

        // Display the label to the right of the checkbox so it looks better & matches rest of the form.
        if ($mform->elementExists('showdescription')) {
            $coursedesc = $mform->getElement('showdescription');
            if (!empty($coursedesc)) {
                $coursedesc->setText(' ' . $coursedesc->getLabel());
                $coursedesc->setLabel('&nbsp');
            }
        }

        $mform->setAdvanced('showdescription');

        $mform->addElement('checkbox', 'showtitlelaunch', '&nbsp;', ' ' . get_string('display_name', 'lticustom'));
        $mform->setAdvanced('showtitlelaunch');
        $mform->setDefault('showtitlelaunch', true);
        $mform->addHelpButton('showtitlelaunch', 'display_name', 'lticustom');

        $mform->addElement('checkbox', 'showdescriptionlaunch', '&nbsp;', ' ' . get_string('display_description', 'lticustom'));
        $mform->setAdvanced('showdescriptionlaunch');
        $mform->addHelpButton('showdescriptionlaunch', 'display_description', 'lticustom');

        // Tool settings.
        $tooltypes = $mform->addElement('select', 'typeid', get_string('external_tool_type', 'lticustom'));
        // Type ID parameter being passed when adding an preconfigured tool from activity chooser.
        $typeid = optional_param('typeid', false, PARAM_INT);
        if ($typeid) {
            $mform->getElement('typeid')->setValue($typeid);
        }
        $mform->addHelpButton('typeid', 'external_tool_type', 'lticustom');
        $toolproxy = array();

        // Array of tool type IDs that don't support ContentItemSelectionRequest.
        $noncontentitemtypes = [];

        foreach (lticustom_get_types_for_add_instance() as $id => $type) {
            if (!empty($type->toolproxyid)) {
                $toolproxy[] = $type->id;
                $attributes = array( 'globalTool' => 1, 'toolproxy' => 1);
                $enabledcapabilities = explode("\n", $type->enabledcapability);
                if (!in_array('Result.autocreate', $enabledcapabilities) || in_array('BasicOutcome.url', $enabledcapabilities)) {
                    $attributes['nogrades'] = 1;
                }
                if (!in_array('Person.name.full', $enabledcapabilities) && !in_array('Person.name.family', $enabledcapabilities) &&
                    !in_array('Person.name.given', $enabledcapabilities)) {
                    $attributes['noname'] = 1;
                }
                if (!in_array('Person.email.primary', $enabledcapabilities)) {
                    $attributes['noemail'] = 1;
                }
            } else if ($type->course == $COURSE->id) {
                $attributes = array( 'editable' => 1, 'courseTool' => 1, 'domain' => $type->tooldomain );
            } else if ($id != 0) {
                $attributes = array( 'globalTool' => 1, 'domain' => $type->tooldomain);
            } else {
                $attributes = array();
            }

            if ($id) {
                $config = lticustom_get_type_config($id);
                if (!empty($config['contentitem'])) {
                    $attributes['data-contentitem'] = 1;
                    $attributes['data-id'] = $id;
                } else {
                    $noncontentitemtypes[] = $id;
                }
            }
            $tooltypes->addOption($type->name, $id, $attributes);
        }

        // Add button that launches the content-item selection dialogue.
        // Set contentitem URL.
        $contentitemurl = new moodle_url('/mod/lticustom/contentitem.php');
        $contentbuttonattributes = [
            'data-contentitemurl' => $contentitemurl->out(false)
        ];
        $contentbuttonlabel = get_string('selectcontent','lticustom');
        $contentbutton = $mform->addElement('button', 'selectcontent', $contentbuttonlabel, $contentbuttonattributes);
        // Disable select content button if the selected tool doesn't support content item or it's set to Automatic.
        $allnoncontentitemtypes = $noncontentitemtypes;
        $allnoncontentitemtypes[] = '0'; // Add option value for "Automatic, based on tool URL".
        $mform->disabledIf('selectcontent', 'typeid', 'in', $allnoncontentitemtypes);

        $mform->addElement('text', 'toolurl', get_string('launch_url', 'lticustom'), array('size' => '64'));
        $mform->setType('toolurl', PARAM_URL);
        $mform->addHelpButton('toolurl', 'launch_url', 'lticustom');
        $mform->hideIf('toolurl', 'typeid', 'in', $noncontentitemtypes);

        $mform->addElement('text', 'securetoolurl', get_string('secure_launch_url', 'lticustom'), array('size' => '64'));
        $mform->setType('securetoolurl', PARAM_URL);
        $mform->setAdvanced('securetoolurl');
        $mform->addHelpButton('securetoolurl', 'secure_launch_url', 'lticustom');
        $mform->hideIf('securetoolurl', 'typeid', 'in', $noncontentitemtypes);

        $mform->addElement('hidden', 'urlmatchedtypeid', '', array( 'id' => 'id_urlmatchedtypeid' ));
        $mform->setType('urlmatchedtypeid', PARAM_INT);

        $launchoptions = array();
        $launchoptions[LTI_CUSTOM__LAUNCH_CONTAINER_DEFAULT] = get_string('default','lticustom');
        $launchoptions[LTI_CUSTOM_LAUNCH_CONTAINER_EMBED] = get_string('embed','lticustom');
        $launchoptions[LTI_CUSTOM_LAUNCH_CONTAINER_EMBED_NO_BLOCKS] = get_string('embed_no_blocks', 'lticustom');
        $launchoptions[LTI_CUSTOM_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW] = get_string('existing_window', 'lticustom');
        $launchoptions[LTI_CUSTOM_LAUNCH_CONTAINER_WINDOW] = get_string('new_window', 'lticustom');

        $mform->addElement('select', 'launchcontainer', get_string('launchinpopup','lticustom'), $launchoptions);
        $mform->setDefault('launchcontainer', LTI_CUSTOM__LAUNCH_CONTAINER_DEFAULT);
        $mform->addHelpButton('launchcontainer', 'launchinpopup', 'lticustom');
        $mform->setAdvanced('launchcontainer');

        $mform->addElement('text', 'resourcekey', get_string('resourcekey','lticustom'));
        $mform->setType('resourcekey', PARAM_TEXT);
        $mform->setAdvanced('resourcekey');
        $mform->addHelpButton('resourcekey', 'resourcekey', 'lticustom');
        $mform->setForceLtr('resourcekey');
        $mform->hideIf('resourcekey', 'typeid', 'in', $noncontentitemtypes);

        $mform->addElement('passwordunmask', 'password', get_string('password','lticustom'));
        $mform->setType('password', PARAM_TEXT);
        $mform->setAdvanced('password');
        $mform->addHelpButton('password', 'password', 'lticustom');
        $mform->hideIf('password', 'typeid', 'in', $noncontentitemtypes);

        $mform->addElement('textarea', 'instructorcustomparameters', get_string('custom','lticustom'), array('rows' => 4, 'cols' => 60));
        $mform->setType('instructorcustomparameters', PARAM_TEXT);
        $mform->setAdvanced('instructorcustomparameters');
        $mform->addHelpButton('instructorcustomparameters', 'custom', 'lticustom');
        $mform->setForceLtr('instructorcustomparameters');

        $mform->addElement('text', 'icon', get_string('icon_url', 'lticustom'), array('size' => '64'));
        $mform->setType('icon', PARAM_URL);
        $mform->setAdvanced('icon');
        $mform->addHelpButton('icon', 'icon_url', 'lticustom');
        $mform->hideIf('icon', 'typeid', 'in', $noncontentitemtypes);

        $mform->addElement('text', 'secureicon', get_string('secure_icon_url', 'lticustom'), array('size' => '64'));
        $mform->setType('secureicon', PARAM_URL);
        $mform->setAdvanced('secureicon');
        $mform->addHelpButton('secureicon', 'secure_icon_url', 'lticustom');
        $mform->hideIf('secureicon', 'typeid', 'in', $noncontentitemtypes);

        // Add privacy preferences fieldset where users choose whether to send their data.
        $mform->addElement('header', 'privacy', get_string('privacy','lticustom'));

        $mform->addElement('advcheckbox', 'instructorchoicesendname', '&nbsp;', ' ' . get_string('share_name', 'lticustom'));
        $mform->setDefault('instructorchoicesendname', '1');
        $mform->addHelpButton('instructorchoicesendname', 'share_name', 'lticustom');
        $mform->disabledIf('instructorchoicesendname', 'typeid', 'in', $toolproxy);

        $mform->addElement('advcheckbox', 'instructorchoicesendemailaddr', '&nbsp;', ' ' . get_string('share_email', 'lticustom'));
        $mform->setDefault('instructorchoicesendemailaddr', '1');
        $mform->addHelpButton('instructorchoicesendemailaddr', 'share_email', 'lticustom');
        $mform->disabledIf('instructorchoicesendemailaddr', 'typeid', 'in', $toolproxy);

        $mform->addElement('advcheckbox', 'instructorchoiceacceptgrades', '&nbsp;', ' ' . get_string('accept_grades', 'lticustom'));
        $mform->setDefault('instructorchoiceacceptgrades', '1');
        $mform->addHelpButton('instructorchoiceacceptgrades', 'accept_grades', 'lticustom');
        $mform->disabledIf('instructorchoiceacceptgrades', 'typeid', 'in', $toolproxy);

        // Add standard course module grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();
        $mform->setAdvanced('cmidnumber');

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();

        $editurl = new moodle_url('/mod/lticustom/instructor_edit_tool_type.php',
                array('sesskey' => sesskey(), 'course' => $COURSE->id));
        $ajaxurl = new moodle_url('/mod/lticustom/ajax.php');

        // All these icon uses are incorrect. LTI JS needs updating to use AMD modules and templates so it can use
        // the mustache pix helper - until then LTI will have inconsistent icons.
        $jsinfo = (object)array(
                        'edit_icon_url' => (string)$OUTPUT->image_url('t/edit'),
                        'add_icon_url' => (string)$OUTPUT->image_url('t/add'),
                        'delete_icon_url' => (string)$OUTPUT->image_url('t/delete'),
                        'green_check_icon_url' => (string)$OUTPUT->image_url('i/valid'),
                        'warning_icon_url' => (string)$OUTPUT->image_url('warning', 'lticustom'),
                        'instructor_tool_type_edit_url' => $editurl->out(false),
                        'ajax_url' => $ajaxurl->out(true),
                        'courseId' => $COURSE->id
                  );

        $module = array(
            'name' => 'mod_lticustom_edit',
            'fullpath' => '/mod/lticustom/mod_form.js',
            'requires' => array('base', 'io', 'querystring-stringify-simple', 'node', 'event', 'json-parse'),
            'strings' => array(
                array('addtype', 'lticustom'),
                array('edittype', 'lticustom'),
                array('deletetype', 'lticustom'),
                array('delete_confirmation', 'lticustom'),
                array('cannot_edit', 'lticustom'),
                array('cannot_delete', 'lticustom'),
                array('global_tool_types', 'lticustom'),
                array('course_tool_types', 'lticustom'),
                array('using_tool_configuration', 'lticustom'),
                array('using_tool_cartridge', 'lticustom'),
                array('domain_mismatch', 'lticustom'),
                array('custom_config', 'lticustom'),
                array('tool_config_not_found', 'lticustom'),
                array('tooltypeadded', 'lticustom'),
                array('tooltypedeleted', 'lticustom'),
                array('tooltypenotdeleted', 'lticustom'),
                array('tooltypeupdated', 'lticustom'),
                array('forced_help', 'lticustom')
            ),
        );

        if (!empty($typeid)) {
            $mform->setAdvanced('typeid');
            $mform->setAdvanced('toolurl');
        }

        $PAGE->requires->js_init_call('M.mod_lticustom.editor.init', array(json_encode($jsinfo)), true, $module);
    }

}
