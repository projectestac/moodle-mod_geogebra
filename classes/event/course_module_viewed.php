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
 * The mod_geogebra course module viewed event.
 *
 * @package    mod_geogebra
 * @copyright  2014 Pau Ferrer <crazyserver@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_geogebra\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The mod_geogebra course module viewed event class.
 */
class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {

        $this->data['objecttable'] = 'geogebra';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;

    }

    // TODO: Delete after 2.8 upgrade

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return 'User with id ' . $this->userid . ' viewed geogebra activity with instance id ' . $this->objectid;
    }

    /**
     * Return localised event name.
     *
     * @throws \coding_exception
     * @return string
     */
    public static function get_name() {
        return get_string('event_course_module_viewed', 'mod_geogebra');
    }

    /**
     * Get URL related to the action.
     *
     * @throws \moodle_exception
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/geogebra/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {

        // Hack to please the parent class. 'view' was the key used in old add_to_log().
        $this->data['other']['content'] = 'view';
        parent::validate_data();

    }

}
