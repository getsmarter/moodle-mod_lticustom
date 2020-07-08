<?php


namespace mod_lticustom\output;

defined('MOODLE_INTERNAL') || die();

use context_module;

require_once($CFG->dirroot.'/mod/lticustom/lib.php');
require_once($CFG->dirroot.'/mod/lticustom/locallib.php');

class mobile
{
    public static function mobile_lticustom_view($args)
    {
        global $OUTPUT;

        $form  = self::generateData($args);

        $javascript = self::generateJavacript($form);

        return  [
            'templates' => [
                    [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_lticustom/mobile_view_page', array('form' => ($form))),
                    ]
            ],
            'javascript' => $javascript,
            'otherdata' => '',
            'files' => '',
        ];
    }

    public static function generateData($args){

        global $DB;

        $args = (object)$args;

        $cm = get_coursemodule_from_id('lticustom', $args->cmid, 0, false, MUST_EXIST);
        $lti = $DB->get_record('lticustom', array('id' => $cm->instance), '*', MUST_EXIST);
        
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

        $context = context_module::instance($cm->id);

        require_login($course, true, $cm);
        require_capability('mod/lticustom:view', $context);

        $lti->cmid = $cm->id;

        list($endpoint, $params) = lticustom_get_launch_data($lti);

        return self::generateForm($endpoint, $params);

    }


    public static function  generateForm($endpoint, $params){

        $text = "<html><head></head><body>";
        $text .= "<form action=\"" . $endpoint .
            "\" name=\"ltiLaunchForm\" id=\"ltiLaunchForm\" method=\"post\" encType=\"application/x-www-form-urlencoded\">";

        // Contruct html for the launch parameters.
        foreach ($params as $key => $value) {
            $key = htmlspecialchars($key);
            $value = htmlspecialchars($value);
            if ( $key == "ext_submit" ) {
                $text .= "<input type=\"submit\"";
            } else {
                $text .= "<input type=\"hidden\" name=\"{$key}\"";
            }
            $text .= "value='{$value}' />";
        }
        $text .= "</form>";

        $text .="<script type='text/javascript'>document.getElementById('ltiLaunchForm').submit();</script>";
        $text .=  "</body></html>";

        return  base64_encode($text);

    }

    public static function generateJavacript($form)
    {
        global $CFG;
        $javascript =file_get_contents($CFG->dirroot . '/mod/lticustom/mobile.js');
        return  str_replace("[string_form_lticustom]", $form, $javascript);
    }
}
