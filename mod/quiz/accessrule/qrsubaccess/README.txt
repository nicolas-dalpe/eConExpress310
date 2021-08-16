The Honesty check quiz access rule
https://moodle.org/plugins/quizaccess_qrsubaccess

This quiz access rule was created by Tim Hunt at the Open University.

If you install this plugin, there is a new option on the quiz settings form, and
if the teacher turns that on, then when a student tries to start a quiz attempt,
they will see a statement about plagiarism and cheating, and they will have to
agree that they will be good before they are allowed to start the quiz attempt.

To install using git, type this command in the root of your Moodle install
    git clone git://github.com/moodleou/moodle-quizaccess_qrsubaccess.git mod/quiz/accessrule/qrsubaccess
    echo '/mod/quiz/accessrule/qrsubaccess/' >> .git/info/exclude

Alternatively, download the zip from
    https://github.com/moodleou/moodle-quizaccess_qrsubaccess/zipball/master
unzip it into the mod/quiz/accessrule folder, and then rename the new
folder to qrsubaccess.

Once installed you need to go to the Site administration -> Notifications page
to let the plugin install itself.
