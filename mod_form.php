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

/**
 * The main geogebra configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod
 * @subpackage geogebra
 * @copyright  2011 Departament d'Ensenyament de la Generalitat de Catalunya
 * @author     Sara Arjona Téllez <sarjona@xtec.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot.'/course/moodleform_mod.php';
require_once 'locallib.php';

/**
 * Module instance settings form
 */
class mod_geogebra_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {

        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('name'), ['size'=>'64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_RAW_TRIMMED);
        }
 
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        $mform->addElement('header', 'timing', get_string('timing', 'geogebra'));
        $mform->addElement('date_time_selector', 'timeavailable', get_string('availabledate', 'geogebra'), ['optional'=>true]);
        $mform->addElement('date_time_selector', 'timedue', get_string('duedate', 'geogebra'), ['optional'=>true]);

        // Adding the rest of geogebra settings, spreeading all them into this fieldset
        $mform->addElement('header', 'header_geogebra', get_string('contentheader', 'geogebra'));

        $mform->addElement('select', 'filetype', get_string('filetype', 'geogebra'), geogebra_get_file_types());
        $mform->addHelpButton('filetype', 'filetype', 'geogebra');
        $mform->addElement('text', 'geogebraurl', get_string('geogebraurl', 'geogebra'), ['size'=>60]);
        $mform->setType('geogebraurl', PARAM_RAW);
        $mform->addHelpButton('geogebraurl', 'geogebraurl', 'geogebra');
        $mform->disabledIf('geogebraurl', 'filetype', 'eq', GEOGEBRA_FILE_TYPE_LOCAL);

        $mform->addElement('filemanager', 'geogebrafile', get_string('geogebrafile', 'geogebra'), ['optional'=>false], geogebra_get_filemanager_options());
        $mform->addHelpButton('geogebrafile', 'urledit', 'geogebra');
        $mform->disabledIf('geogebrafile', 'filetype', 'noteq', GEOGEBRA_FILE_TYPE_LOCAL);

        $mform->addElement('text', 'seed', get_string('seed', 'geogebra'), ['size' => '2']);
        $mform->setType('seed', PARAM_INT);
        $mform->setDefault('seed', '0');
        $mform->addHelpButton('seed', 'seed', 'geogebra');
        $mform->setAdvanced('seed');

        $mform->addElement('text', 'urlggb', get_string('urlggb', 'geogebra'), ['size' => '60']);
        $mform->setType('urlggb', PARAM_RAW);
        $mform->setDefault('urlggb', '');
        $mform->addHelpButton('urlggb', 'urlggb', 'geogebra');
        $mform->setAdvanced('urlggb');

        $options = get_string_manager()->get_list_of_translations();
        $mform->addElement('select', 'language', get_string('language', 'geogebra'), $options);

        // Grading.
        $this->standard_grading_coursemodule_elements();

        $options = [-1 => get_string('unlimited'), 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 10 => 10];
        $mform->addElement('select', 'maxattempts', get_string('maxattempts', 'geogebra'), $options);
        $mform->setDefault('maxattempts', '-1');

        $options = [
            GEOGEBRA_AVERAGE_GRADE => get_string('average', 'geogebra'),
            GEOGEBRA_HIGHEST_GRADE => get_string('highestattempt', 'geogebra'),
            GEOGEBRA_LOWEST_GRADE => get_string('lowestattempt', 'geogebra'),
            GEOGEBRA_FIRST_GRADE => get_string('firstattempt', 'geogebra'),
            GEOGEBRA_LAST_GRADE => get_string('lastattempt', 'geogebra'),
        ];

        $mform->addElement('select', 'grademethod', get_string('grademethod', 'geogebra'), $options);
        $mform->setDefault('grademethod', '-1');

        $mform->addElement('advcheckbox', 'autograde', get_string('autograde', 'geogebra'));
        $mform->setDefault('autograde', 0);

        // Options.
        $mform->addElement('header', 'optionsheader', get_string('appearance'));

        $mform->addElement('text', 'width', get_string('width', 'geogebra'), ['size'=>'5']);
        $mform->setType('width', PARAM_INT);
        $mform->setDefault('width', '');
        $mform->addHelpButton('width', 'width', 'geogebra');

        $mform->addElement('text', 'height', get_string('height', 'geogebra'), ['size'=>'5']);
        $mform->setType('height', PARAM_INT);
        $mform->setDefault('height', '600');
        $mform->addHelpButton('height', 'height', 'geogebra');

        $functionalityoptionsgrp = [];
        $functionalityoptionsgrp[] = &$mform->createElement('checkbox', 'enableRightClick', '', get_string('enableRightClick', 'geogebra'));
        $functionalityoptionsgrp[] = &$mform->createElement('checkbox', 'enableLabelDrags', '', get_string('enableLabelDrags', 'geogebra'));
        $functionalityoptionsgrp[] = &$mform->createElement('checkbox', 'showResetIcon', '', get_string('showResetIcon', 'geogebra'));

        $mform->addGroup($functionalityoptionsgrp, 'functionalityoptionsgrp', get_string('functionalityoptionsgrp', 'geogebra'), "<br>", false);
        $mform->setDefault('enableRightClick', 0);
        $mform->setDefault('enableLabelDrags', 0);
        $mform->setDefault('showResetIcon', 0);
        $mform->setAdvanced('functionalityoptionsgrp');

        $options = [
            0 => get_string('useBrowserForJS_geogebra', 'geogebra'),
            1 => get_string('useBrowserForJS_html', 'geogebra'),
        ];

        $mform->addElement('select', 'useBrowserForJS', get_string('useBrowserForJS', 'geogebra'), $options);
        $mform->setDefault('useBrowserForJS', '1');

        $interfaceoptionsgrp = [];
        $interfaceoptionsgrp[] = &$mform->createElement('checkbox', 'showMenuBar', '', get_string('showMenuBar', 'geogebra'));
        $interfaceoptionsgrp[] = &$mform->createElement('checkbox', 'showToolBar', '', get_string('showToolBar', 'geogebra'));
        $interfaceoptionsgrp[] = &$mform->createElement('checkbox', 'showToolBarHelp', '', get_string('showToolBarHelp', 'geogebra'));
        $interfaceoptionsgrp[] = &$mform->createElement('checkbox', 'showAlgebraInput', '', get_string('showAlgebraInput', 'geogebra'));

        $mform->addGroup($interfaceoptionsgrp, 'interfaceoptionsgrp', get_string('interfaceoptionsgrp', 'geogebra'), "<br>", false);
        $mform->setDefault('showMenuBar', 0);
        $mform->setDefault('showToolBar', 0);
        $mform->setDefault('showToolBarHelp', 0);
        $mform->setDefault('showAlgebraInput', 0);
        $mform->setAdvanced('interfaceoptionsgrp');

        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();

        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }

    function data_preprocessing(&$values) {

        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('geogebrafile');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_geogebra', 'content', 0, geogebra_get_filemanager_options());
            $values['geogebrafile'] = $draftitemid;
        }

    }

    public function validation($data, $files) {

        global $USER;

        $errors = parent::validation($data, $files);

        // Check open and close times are consistent.
        if ($data['timeavailable'] !== 0 && $data['timedue'] !== 0 && $data['timedue'] < $data['timeavailable']) {
            $errors['timedue'] = get_string('closebeforeopen', 'geogebra');
        }

        $type = $data['filetype'];

        if ($type === GEOGEBRA_FILE_TYPE_LOCAL) {
            $usercontext = context_user::instance($USER->id);
            $fs = get_file_storage();
            if (!$files = $fs->get_area_files($usercontext->id, 'user', 'draft', $data['geogebrafile'], 'sortorder, id', false)) {
                $errors['geogebrafile'] = get_string('required');
            } else {
                $file = reset($files);
                $filename = $file->get_filename();
                if (!geogebra_is_valid_file($filename)) {
                    $errors['geogebrafile'] = get_string('invalidgeogebrafile', 'geogebra');
                }
            }
        } else if ($type === GEOGEBRA_FILE_TYPE_EXTERNAL) {
            $reference = $data['geogebraurl'];
            if (!geogebra_is_valid_external_url($reference)) {
                $errors['geogebraurl'] = get_string('invalidurl', 'geogebra');
            }
        }

        return $errors;

    }

    function set_data($values) {

        $values = (array)$values;

        if (isset($values['url'])) {
            // Need to translate the "url" field
            if (geogebra_is_valid_external_url($values['url'])) {
                $values['filetype'] = GEOGEBRA_FILE_TYPE_EXTERNAL;
                $values['geogebraurl'] = $values['url'];
            } else {
                $values['filetype'] = GEOGEBRA_FILE_TYPE_LOCAL;
                $values['geogebrafile'] = $values['url'];
            }

            // Load attributes
            parse_str($values['attributes'], $attributes);
            $values['enableRightClick'] = $attributes['enableRightClick'] ?? 0;
            $values['enableLabelDrags'] = $attributes['enableLabelDrags'] ?? 0;
            $values['showResetIcon'] = $attributes['showResetIcon'] ?? 0;
            $values['showMenuBar'] = $attributes['showMenuBar'] ?? 0;
            $values['showToolBar'] = $attributes['showToolBar'] ?? 0;
            $values['showToolBarHelp'] = $attributes['showToolBarHelp'] ?? 0;
            $values['showAlgebraInput'] = $attributes['showAlgebraInput'] ?? 0;
            $values['language'] = $attributes['language'] ?? 0;
            $values['useBrowserForJS'] = $attributes['useBrowserForJS'] ?? 0;
        }

        unset($values['url']);

        $this->data_preprocessing($values);
        parent::set_data($values);

    }

    function completion_rule_enabled($data) {
        return !empty($data['completionsubmit']);
    }

}
