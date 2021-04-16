<?php
// Standard GPL and phpdocs
namespace assignsubmission_qrcodea\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

class qrcode_page implements renderable, templatable {

    /** @var string $sometext Some text to show how to pass data to a template. */
    var $tpldata = null;

    public function __construct($tpldata) {
        $this->tpldata = $tpldata;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        return $this->tpldata;
    }
}