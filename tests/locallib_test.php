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

namespace mod_geogebra;

use advanced_testcase;
use GuzzleHttp\Handler\MockHandler;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/geogebra/locallib.php');

/**
 * Tests for the internal library of the geogebra module.
 *
 * @package    mod_geogebra
 * @copyright  2026 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversFunction('geogebra_is_valid_external_url')]
#[CoversFunction('geogebra_print_content')]
final class locallib_test extends advanced_testcase {
    /**
     * Tests that a non http(s) url is never passed to the HTTP client when the activity is rendered.
     *
     * The form rejects such values, but they can still end up in the database, for example by
     * restoring a crafted backup, so the check has to happen at render time as well.
     */
    public function test_print_content_does_not_request_non_http_urls(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // A mock handler without queued responses fails the test as soon as a request is sent.
        \core\di::set(\core\http_client::class, new \core\http_client(['mock' => new MockHandler()]));

        $course = $this->getDataGenerator()->create_course();
        $geogebra = $this->getDataGenerator()->create_module('geogebra', [
            'course' => $course->id,
            'url' => 'file:///etc/passwd',
        ]);

        ob_start();
        geogebra_print_content($geogebra, \core\context\module::instance($geogebra->cmid));
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    /**
     * Tests that a url which is blocked by the curl security helper is not rendered.
     */
    public function test_print_content_does_not_render_blocked_urls(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $CFG->curlsecurityblockedhosts = '127.0.0.1';
        // A mock handler without queued responses fails the test as soon as a request is actually sent.
        \core\di::set(\core\http_client::class, new \core\http_client(['mock' => new MockHandler()]));

        $course = $this->getDataGenerator()->create_course();
        $geogebra = $this->getDataGenerator()->create_module('geogebra', [
            'course' => $course->id,
            'url' => 'http://127.0.0.1/latest/meta-data/',
        ]);

        ob_start();
        geogebra_print_content($geogebra, \core\context\module::instance($geogebra->cmid));
        $output = ob_get_clean();

        $this->assertSame('', $output);
        $this->assertStringNotContainsString('ggbBase64', $output);
        $this->assertDebuggingCalledCount(2);
    }

    /**
     * Tests that only absolute http(s) URLs are accepted as external references.
     *
     * @param string $url The reference stored in the activity.
     * @param bool $expected The expected result.
     */
    #[DataProvider('geogebra_is_valid_external_url_provider')]
    public function test_geogebra_is_valid_external_url(string $url, bool $expected): void {
        $this->assertSame($expected, geogebra_is_valid_external_url($url));
    }

    /**
     * Data provider for {@see test_geogebra_is_valid_external_url}.
     *
     * @return array[] The test cases.
     */
    public static function geogebra_is_valid_external_url_provider(): array {
        return [
            'https_url' => [
                'url' => 'https://example.com/files/activity.ggb',
                'expected' => true,
            ],
            'http_url' => [
                'url' => 'http://example.com/files/activity.ggb',
                'expected' => true,
            ],
            'uppercase_scheme' => [
                'url' => 'HTTPS://example.com/files/activity.ggb',
                'expected' => true,
            ],
            'url_with_query_string' => [
                'url' => 'https://example.com/download.php?id=42&format=ggb',
                'expected' => true,
            ],
            'geogebra_material_download_url' => [
                'url' => 'https://www.geogebra.org/material/download/format/file/id/fwsepww3',
                'expected' => true,
            ],
            'file_scheme' => [
                'url' => 'file:///var/www/moodledata/secret.txt',
                'expected' => false,
            ],
            'ftp_scheme' => [
                'url' => 'ftp://example.com/activity.ggb',
                'expected' => false,
            ],
            'scheme_relative_url' => [
                'url' => '//example.com/activity.ggb',
                'expected' => false,
            ],
            'filename_containing_www' => [
                'url' => 'wwwactivity.ggb',
                'expected' => false,
            ],
            'plain_filename' => [
                'url' => 'activity.ggb',
                'expected' => false,
            ],
            'empty_url' => [
                'url' => '',
                'expected' => false,
            ],
            'scheme_without_host' => [
                'url' => 'http://',
                'expected' => false,
            ],
            'scheme_embedded_in_path' => [
                'url' => 'activity.ggb?redirect=https://example.com',
                'expected' => false,
            ],
        ];
    }

    /**
     * Tests that URLs of the form geogebra.org/m/<id> are rejected and a warning is shown.
     *
     * @param string $url The reference stored in the activity.
     */
    #[DataProvider('geogebra_is_valid_external_url_material_page_provider')]
    public function test_geogebra_is_valid_external_url_material_page(string $url): void {
        $this->resetAfterTest();

        $this->assertFalse(geogebra_is_valid_external_url($url));

        $notifications = \core\notification::fetch();
        $this->assertCount(1, $notifications);
        $this->assertStringContainsString(get_string('invalidurl', 'geogebra'), $notifications[0]->get_message());
    }

    /**
     * Data provider for {@see test_geogebra_is_valid_external_url_material_page}.
     *
     * @return array[] The test cases.
     */
    public static function geogebra_is_valid_external_url_material_page_provider(): array {
        return [
            'without_subdomain' => ['url' => 'https://geogebra.org/m/abcd1234'],
            'with_www' => ['url' => 'https://www.geogebra.org/m/abcd1234'],
            'http_scheme' => ['url' => 'http://www.geogebra.org/m/abcd1234'],
        ];
    }
}
