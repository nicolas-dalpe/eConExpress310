<?php

/**
 * This function extends the navigation with the tool items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass        $course     The course to object for the tool
 * @param context         $context    The context of the course
 */
function local_loginas_extend_navigation_course($navigation, $course, $context) {
    global $PAGE;
    if (has_capability('moodle/course:view', $context)) {

        $url = new moodle_url(
            '/local/loginas/index.php',
            array('id' => $course->id, 'redirect' => $PAGE->url)
        );

        $name = get_string('nav_menu_item', 'local_loginas');

        $navigation->add($name, $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/user', ''));
    }
}

/**
 * This function extends the user navigation.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $user The user object
 * @param context_user $usercontext The user context
 * @param stdClass $course The course object
 * @param context_course $coursecontext The context of the course
 */
function local_loginas_extend_navigation_user($navigation, $user, $usercontext, $course, $coursecontext) {
    global $PAGE, $USER;

    // $context = context_user::instance($USER->id);

    if (has_capability('moodle/course:view', $usercontext)) {

        $url = new moodle_url(
            '/local/loginas/index.php',
            array('id' => $course->id, 'redirect' => $PAGE->url)
        );

        $name = get_string('nav_menu_item', 'local_loginas');

        $navigation->add($name, $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/user', ''));
    }
}