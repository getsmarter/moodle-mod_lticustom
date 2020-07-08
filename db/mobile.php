<?php

/**
 * The mod_lticustom mobile app compatibility.
 *
 * @package    mod_lticustom
 * @copyright  2020 GetSmarter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

$addons = array(
    "mod_lticustom" => array(
        'handlers' => array(
            'lticustom' => array(
                'displaydata' => array(
                    'icon' => $CFG->wwwroot . '/mod/lticustom/pix/icon.svg',
                    'class' => '',
                ),
                'delegate' => 'CoreCourseModuleDelegate',
                'method' => 'mobile_lticustom_view',
            )
        ),
    ),
    'lang' => array()
);

