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
 * Client exception.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mootivated;
defined('MOODLE_INTERNAL') || die();

use moodle_exception;

/**
 * Client for communicating with the server.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client_exception extends moodle_exception {

    /** @var string Response. */
    public $response;
    /** @var object Response decoded. */
    protected $responsedecoded;

    /**
     * Constructor.
     *
     * @param string $error The error code.
     * @param curl $curl The curl object post request.
     * @param string $response The response from the server.
     */
    public function __construct($error, $curl, $response) {
        $this->response = $response;
        $this->responsedecoded = json_decode($response);
        $debuginfo = json_encode([
            'info' => $curl->info,
            'errno' => $curl->errno,
            'error' => $curl->error,
            'response' => $response,
        ]);
        parent::__construct($error, 'local_mootivated', '', null, $debuginfo);
    }

    /**
     * Get the server error.
     *
     * @return string
     */
    public function get_server_error() {
        return is_object($this->responsedecoded) ? $this->responsedecoded->message : null;
    }

}
