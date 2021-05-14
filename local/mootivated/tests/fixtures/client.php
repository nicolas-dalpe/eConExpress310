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
 * Fake client.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Fake client.
 *
 * The sole purpose of this is to mock the response.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_mootivated_test_client extends \local_mootivated\client {

    /** @var callable The testing responder. */
    protected $responder;

    /**
     * Client.
     *
     * @param string $host The host.
     */
    public function __construct(callable $responder) {
        $this->responder = $responder;
    }

    /**
     * Get the host.
     *
     * @return string.
     */
    public function get_host() {
        return 'api.example.local';
    }

    /**
     * Sends a request to the server.e.
     *
     * @param string $uri The URI.
     * @param array $data The data.
     * @param string $method The method.
     * @return mixed JSON decoded response.
     */
    public function request($uri, $data = null, $method = 'POST') {
        return call_user_func($this->responder, $uri, $data, $method);
    }

}
