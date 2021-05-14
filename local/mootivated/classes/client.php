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
 * Client for communicating with the server.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mootivated;
defined('MOODLE_INTERNAL') || die();

use curl;
use coding_exception;
use moodle_exception;
use moodle_url;
use stdClass;

/**
 * Client for communicating with the server.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client {

    /** @var string The host. */
    protected $host;
    /** @var array The headers. */
    protected $headers;

    /**
     * Client.
     *
     * @param string $host The host.
     */
    public function __construct($host) {
        $this->host = $host;
        $this->headers = [
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * Get the host.
     *
     * @return string.
     */
    public function get_host() {
        return $this->host;
    }

    /**
     * Redirect to a dashboard URL.
     *
     * @param string $uri The URI.
     * @param array $params The parameters.
     * @return void
     */
    public function redirect($uri, array $params = []) {
        $url = new moodle_url('https://' . $this->host . '/' . ltrim($uri, '/'), $params);
        redirect($url);
        die();
    }

    /**
     * Sends a request to the server.
     *
     * For now it only supports POST requests, and no custom headers,
     * because we do not need more than that at this stage. It will also encode
     * the data to JSON and expect a JSON response.
     *
     * @param string $uri The URI.
     * @param array $data The data.
     * @param string $method The HTTP method.
     * @return mixed JSON decoded response.
     */
    public function request($uri, $data = null, $method = 'POST') {
        $method = strtoupper($method);
        $url = 'https://' . $this->host . '/' . ltrim($uri, '/');

        $curl = new curl();
        foreach ($this->headers as $key => $value) {
            $curl->setHeader($key . ': ' . $value);
        }

        if ($method == 'POST') {
            $response = $curl->post($url, $data ? json_encode($data) : '');
        } else if ($method == 'GET') {
            $response = $curl->get($url, is_array($data) ? $data : []);
        } else {
            throw new coding_exception('Invalid HTTP method');
        }

        if ($curl->error) {
            throw new client_exception('request_error', $curl, $response);
        } else if ($curl->info['http_code'] >= 300) {
            throw new client_exception('request_failed', $curl, $response);
        }

        $data = json_decode($response);
        if ($data === null) {
            throw new moodle_exception('Failed to decode result from ' . $url, 'local_mootivated', '', null, $response);
        }

        return $data;
    }

}
