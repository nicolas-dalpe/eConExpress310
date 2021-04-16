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
 * This file contains a class that provides functions for downloading/displaying a QR code.
 *
 * @package     qtype
 * @subpackage  qrcoded
 * @copyright   2017 T Gunkel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_qrcodea\local;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Symfony\Component\HttpFoundation\Response;
use DOMDocument;
use core\datalib;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/submission/qrcodea/thirdparty/vendor/autoload.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Displays QR code.
 *
 * @package     assign
 * @subpackage  qrcodea
 * @copyright   2021 Knowledge One <nicolas.dalpe@knowledgeone.ca>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qrcodegenerator {
    /**
     * QR code is saved in this file.
     * @var string
     */
    protected $file;

    /**
     * Output file type.
     * 2 - png, 1 - svg
     * @var int
     */
    protected $format;
    protected $defaultformat = 1;

    /**
     * Size of qrcode (downloaded image).
     * Only for png.
     * @var int
     */
    protected $size;
    protected $defaultsize = 300;

    /**
     * Filepath for logo.
     * @var string
     */
    protected $logopath;

    /**
     * Course for which the qrcode is created.
     * @var \stdClass
     */
    protected $course;

    /**
     * The quiz cmid used to generate the QR Code.
     * @var int
     */
    protected $quizcmid;

    /**
     * The assignment id used to generate the QR Code.
     * @var int
     */
    protected $assignmentid;

    /**
     * qrcodegenerator constructor.
     * @param int $format File type 1 for SVG and 2 for PNG
     * @param int $size Image size in pixel.
     * @param int $courseid Course for which the qrcode is created
     * @param int $quizcmid The current quiz cmid.
     * @param int $assignmentid The assignment id the QR Code should link to.
     */
    public function __construct($qrcodeoptions) {
        global $CFG, $DB;

        $this->course = get_course($qrcodeoptions->courseid);

        // Set image format.
        $this->set_format($qrcodeoptions->format);

        // Set the QR Code size.
        $this->set_size($qrcodeoptions->size);

        // Set the asignment cmid.
        $this->set_assignmentid($qrcodeoptions->assignmentid);

        // Get the logo path to be displayed.
        if ($this->get_format() == 1) {
            $this->logopath = $CFG->dirroot . '/mod/assign/submission/qrcodea/pix/concordialogo.svg';
        } else {
            $this->logopath = $CFG->dirroot . '/mod/assign/submission/qrcodea/pix/concordialogo.png';
        }

        // Path of the cached QR Code.
        $this->file = sprintf(
            '%s/assignsubmission_qrcodea/course-%d_assignment-%d_size-%d.%s',
            $CFG->localcachedir,
            $this->course->id,
            $this->get_assignmentid(),
            $this->get_size(),
            ($this->get_format() == 1) ? 'svg' : 'png'
        );
    }

    /**
     * Set the logo format in the center of the QR Code.
     *
     * @param int $format The image format to display. 1 = svg, 2 = png
     */
    public function set_format($format) {

        // Get the int value of the passed format.
        $format = (int)$format;

        if ($format === 1 || $format === 2) {
            $this->format = $format;
        } else {
            $this->format = $this->defaultformat;
        }
    }

    /**
     * Get the logo file format.
     *
     * @return int 0 or 1.
     */
    public function get_format() {
        return $this->format;
    }

    /**
     * Set the QR Code size in pixels.
     *
     * @param int $size The width and height of the QR Code to display.
     */
    public function set_size($size) {

        if (is_numeric($size)) {
            $this->size = (int)$size;
        } else {
            $this->size = $this->defaultsize;
        }
    }

    /**
     * Get the QR Code size.
     *
     * @return int QR Code size.
     */
    public function get_size() {
        return $this->size;
    }

    /**
     * Set the assignment cmid to link to.
     *
     * @param int $assignmentid The assignment cmid.
     */
    public function set_assignmentid($assignmentid) {
        $this->assignmentid = (int)$assignmentid;
    }

    /**
     * Get the assignment cmid.
     */
    public function get_assignmentid() {
        return $this->assignmentid;
    }

    /**
     * Creates the QR code if it doesn't exist.
     */
    public function create_image() {
        global $CFG;

        // Checks if QR code already exists.
        if (file_exists($this->file)) {
            // File exists in cache.
            return;
        }

        // Checks if directory already exists.
        if (!file_exists(dirname($this->file))) {
            // Creates new directory.
            mkdir(dirname($this->file), $CFG->directorypermissions, true);
        }

        // Creates the QR code URL.
        $url = new moodle_url('/mod/assign/index.php', array(
            'id' => $this->get_assignmentid(),
            'action' => 'editsubmission'
        ));

        // Create the QR Code obj.
        $qrcode = new QrCode($url->out(false));
        $qrcode->setSize($this->size);

        // Set advanced options.
        $qrcode->setMargin(10);
        $qrcode->setEncoding('UTF-8');
        $qrcode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH);
        $qrcode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0]);
        $qrcode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255]);

        // Png format.
        if ($this->format == 2) {
            if ($this->logopath !== null) {
                $qrcode->setLogoPath($this->logopath);
                $qrcode->setLogoWidth($this->size * 0.4);
            }
            $qrcode->setWriterByName('png');
            $qrcode->writeFile($this->file);
        } else {
            $qrcode->setWriterByName('svg');
            if ($this->logopath !== null) {
                $qrcodestring = $qrcode->writeString();
                $newqrcode = $this->modify_svg($qrcodestring);
                file_put_contents($this->file, $newqrcode);
            } else {
                $qrcode->writeFile($this->file);
            }
        }
    }

    /**
     * Outputs file headers to initialise the download of the file / display the file.
     * @param bool $download true, if the QR code should be downloaded
     */
    protected function send_headers($download) {
        // Caches file for 1 month.
        header('Cache-Control: public, max-age:2628000');

        if ($this->format == 2) {
            header('Content-Type: image/png');
        } else {
            header('Content-Type: image/svg+xml');
        }

        // Checks if the image is downloaded or displayed.
        if ($download) {
            // Output file header to initialise the download of the file.
            // filename: QR Code-%s.(svg|png), where %s is derived from the course's fullname.
            if ($this->format == 2) {
                header('Content-Disposition: attachment; filename="QR Code-' .
                    clean_param($this->course->fullname, PARAM_FILE) . '.png"');
            } else {
                header('Content-Disposition: attachment; filename="QR Code-' .
                    clean_param($this->course->fullname, PARAM_FILE) . '.svg"');
            }
        }
    }

    /**
     * Outputs (downloads or displays) the QR code.
     * @param bool $download true, if the QR code should be downloaded
     */
    public function output_image($download) {
        $this->create_image();
        // $this->send_headers($download);
        return file_get_contents($this->file);
    }

    /**
     * Inserts logo in the QR code (used for svg QR code).
     * @param string $svgqrcode QR code
     * @return string XML representation of the svg image
     */
    private function modify_svg($svgqrcode) {
        // Loads QR code.
        $xmldoc = new DOMDocument();
        $xmldoc->loadXML($svgqrcode);
        $viewboxcode = $xmldoc->documentElement->getAttribute('viewBox');
        $codewidth = explode(' ', $viewboxcode)[2];

        // Loads logo.
        $xmllogo = new DOMDocument();
        $xmllogo->load($this->logopath);

        $logotargetwidth = $codewidth * 0.4;

        $viewbox = $xmllogo->documentElement->getAttribute('viewBox');
        $viewboxbounds = explode(' ', $viewbox);
        $logowidth = $viewboxbounds[2];
        $logoheight = $viewboxbounds[3];

        // Calculate logo height from width.
        $logotargetheight = $logotargetwidth * ($logoheight / $logowidth);

        // Calculate logo coordinates.
        $logoy = ($codewidth - $logotargetheight) / 2;
        $logox = ($codewidth - $logotargetwidth) / 2;

        $xmllogo->documentElement->setAttribute('width', $logotargetwidth);
        $xmllogo->documentElement->setAttribute('height', $logotargetheight);
        $xmllogo->documentElement->setAttribute('x', $logox);
        $xmllogo->documentElement->setAttribute('y', $logoy);

        $node = $xmldoc->importNode($xmllogo->documentElement, true);

        $xmldoc->documentElement->appendChild($node);

        return $xmldoc->saveXML();
    }

    /**
     * Generates logo file path and hash.
     * @return string file path and hash
     */
    private function get_logo() {
        global $CFG;
        $logo = new \stdClass();

        if ($this->format == 2) {
            $filearea = 'logo_png';
            $filepath = pathinfo(get_config('block_qrcode', 'logofile_png'), PATHINFO_DIRNAME);
            $filename = pathinfo(get_config('block_qrcode', 'logofile_png'), PATHINFO_BASENAME);
        } else {
            $filearea = 'logo_svg';
            $filepath = pathinfo(get_config('block_qrcode', 'logofile_svg'), PATHINFO_DIRNAME);
            $filename = pathinfo(get_config('block_qrcode', 'logofile_svg'), PATHINFO_BASENAME);
        }

        $fs = get_file_storage();
        $file = $fs->get_file(\context_system::instance()->id,
            'block_qrcode',
            $filearea,
            0,
            $filepath,
            $filename);

        if ($file) {
            $logo->hash = $file->get_contenthash();
            $logo->path = $CFG->dataroot . '/filedir/' .
                substr($logo->hash, 0, 2) . '/' .
                substr($logo->hash, 2, 2) . '/' .
                $logo->hash;

            return $logo;
        }
        return null;
    }
}
