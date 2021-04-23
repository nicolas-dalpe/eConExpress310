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
 * This file defines the admin settings for this plugin
 *
 * @package   assignsubmission_qrcodea
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 require_once($CFG->dirroot . '/mod/assign/submission/qrcodea/lib.php');


// Note: This is on by default.
$settings->add(new admin_setting_configcheckbox('assignsubmission_qrcodea/default',
                   new lang_string('default', 'assignsubmission_qrcodea'),
                   new lang_string('default_help', 'assignsubmission_qrcodea'), 0));

$settings->add(new admin_setting_configtext('assignsubmission_qrcodea/maxfiles',
                   new lang_string('maxfiles', 'assignsubmission_qrcodea'),
                   new lang_string('maxfiles_help', 'assignsubmission_qrcodea'), 20, PARAM_INT));

$settings->add(new admin_setting_filetypes('assignsubmission_qrcodea/filetypes',
                   new lang_string('defaultacceptedfiletypes', 'assignsubmission_qrcodea'),
                   new lang_string('acceptedfiletypes_help', 'assignsubmission_qrcodea'), ''));

if (isset($CFG->maxbytes)) {

    $maxbytes = get_config('assignsubmission_qrcodea', 'maxbytes');
    $settings->add(
        new admin_setting_configselect(
            'assignsubmission_qrcodea/maxbytes',
            new lang_string('maximumsubmissionsize', 'assignsubmission_qrcodea'),
            new lang_string('configmaxbytes', 'assignsubmission_qrcodea'),
            $CFG->maxbytes,
            get_max_upload_sizes($CFG->maxbytes, 0, 0, $maxbytes)
        )
    );
}

$settings->add(
    new admin_setting_configselect(
        'assignsubmission_qrcodea/maxbytes',
        new lang_string('maximumsubmissionsize', 'assignsubmission_qrcodea'),
        new lang_string('configmaxbytes', 'assignsubmission_qrcodea'),
        $CFG->maxbytes,
        get_max_upload_sizes($CFG->maxbytes, 0, 0, $maxbytes)
    )
);

// Set the QR Code size in pixel.
$settings->add(
    new admin_setting_configtext('assignsubmission_qrcodea/qrcode_size',
        new lang_string('qrcodesize', 'assignsubmission_qrcodea'),
        new lang_string('qrcodesize_help', 'assignsubmission_qrcodea'),
        300,
        PARAM_INT
    )
);

// Set the prefered file format.
$settings->add(
    new admin_setting_configselect('assignsubmission_qrcodea/qrcode_format',
        new lang_string('qrcodeformata', 'assignsubmission_qrcodea'),
        new lang_string('qrcodeformata_help', 'assignsubmission_qrcodea'),
        1,
        array('1' => 'SVG', '2' => 'PNG')
    )
);

// Set the SVG logo file.
$svglogo = new admin_setting_configstoredfile(
    'assignsubmission_qrcodea/qrcode_logo_svg',
    new lang_string('qrcodelogosvg', 'assignsubmission_qrcodea'),
    new lang_string('qrcodelogosvg_help', 'assignsubmission_qrcodea'),
    'assignsubmission_qrcodea_logo_svg',
    0,
    array('maxfiles' => 1, 'accepted_types' => ['.svg'])
);

// Callback to empty the QR Code cache when the logo is updated.
$svglogo->set_updatedcallback('assignsubmission_qrcodea_reset_qrcode_cache');
$settings->add($svglogo);

// Set the PNG logo file.
$pnglogo = new admin_setting_configstoredfile(
    'assignsubmission_qrcodea/qrcode_logo_png',
    new lang_string('qrcodelogopng', 'assignsubmission_qrcodea'),
    new lang_string('qrcodelogopng_help', 'assignsubmission_qrcodea'),
    'assignsubmission_qrcodea_logo_png',
    0,
    array('maxfiles' => 1, 'accepted_types' => ['.png'])
);

// Callback to empty the QR Code cache when the logo is updated.
$pnglogo->set_updatedcallback('assignsubmission_qrcodea_reset_qrcode_cache');
$settings->add($pnglogo);

