<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     local_qrsub
 * @category    string
 * @copyright   2021 KnowledgeOne <nicolas.dalpe@knowledgeone.ca>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'QR Submission';
$string['quiznavigation'] = 'Exam state';

$string['responserequired'] = 'Text answer ';
$string['attachments'] = 'Nb. file(s) allowed: ';
$string['attachmentsrequired'] = 'Nb. file(s) required: ';
$string['completed'] = 'Complete';
$string['incomplete'] = 'Incomplete';
$string['instruction_qrcode'] = 'Scan this QR code to upload your files.';

$string['qrcodesize'] = 'QR Code size';
$string['qrcodesize_help'] = 'Size of the QR Code in pixel.';

$string['qrcodeformata'] = 'Preferred logo format.';
$string['qrcodeformata_help'] = 'The preferred logo format.';

$string['qrcodelogosvg'] = 'Logo SVG file';
$string['qrcodelogosvg_help'] = 'Logo image file to be displayed in the QR Code (SVG Format).';

$string['qrcodelogopng'] = 'Logo PNG file';
$string['qrcodelogopng_help'] = 'Logo image file to be displayed in the QR Code (PNG Format).';

// Attempt start page.
$string['non_hybrid_finished'] = 'Non Hybrid question(s) attempt finished';
$string['hybrid_upload'] = 'Upload in progress';
