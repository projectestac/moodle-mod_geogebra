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
 * mod_geogebra data generator.
 *
 * @package mod_geogebra
 * @category test
 * @copyright 2018 Salva Valldeoriola <svallde2@xtec.cat>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * mod_geogebra data generator class.
 *
 * @package mod_geogebra
 * @category test
 * @copyright 2018 Salva Valldeoriola <svallde2@xtec.cat>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_geogebra_generator extends testing_module_generator {
    /**
     * Creates a geogebra instance.
     *
     * @param array|stdClass|null $record The instance data, may contain a 'url' field.
     * @param array|null $options The options passed to the module generator.
     * @return stdClass The created instance.
     */
    public function create_instance($record = null, ?array $options = null) {
        global $DB;

        $record = (object)(array)$record;

        if (!isset($record->timemodified)) {
            $record->timemodified = time();
        }
        if (!isset($record->option)) {
            $record->option = array();
        } else if (!is_array($record->option)) {
            $record->option = preg_split('/\s*,\s*/', trim($record->option), -1, PREG_SPLIT_NO_EMPTY);
        }

        // Remember the requested url: geogebra_add_instance() passes the record to
        // geogebra_before_add_or_update(), which overwrites the url with an empty string whenever the
        // instance is created without a form, as it is the case here. Passing the url in the record
        // alone would therefore silently have no effect.
        $url = $record->url ?? null;

        $instance = parent::create_instance($record, (array)$options);

        // Apply the url to the created instance. Writing it to the database directly is on purpose:
        // it is the same way a restored backup ends up with a url which never passed the form
        // validation, which is exactly what the tests using this generator need to cover.
        if ($url !== null) {
            $DB->set_field('geogebra', 'url', $url, ['id' => $instance->id]);
            $instance->url = $url;
        }

        return $instance;
    }
}
