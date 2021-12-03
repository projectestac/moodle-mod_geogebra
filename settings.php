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
 * GeoGebra grade form
 *
 * @package    mod
 * @subpackage geogebra
 * @copyright  2021 Departament d'Educació de la Generalitat de Catalunya
 * @author     Toni Ginard
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(
        new admin_setting_heading(
            'geogebra/configintro',
            '',
            get_string('configintro', 'geogebra')
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'geogebra/deployggb',
            get_string('deployggb', 'geogebra'),
            get_string('deployggb_desc', 'geogebra'),
            '//www.geogebra.org/apps/deployggb.js'
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'geogebra/fflate',
            get_string('fflate', 'geogebra'),
            get_string('fflate_desc', 'geogebra'),
            '//unpkg.com/fflate'
        )
    );

}
