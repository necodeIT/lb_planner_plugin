<?php
// This file is part of the local_lbplanner.
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
 * Model for a user
 *
 * @package local_lbplanner
 * @subpackage helpers
 * @copyright 2025 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

namespace local_lbplanner\model;

use core\context\system as context_system;
use core_external\{external_single_structure, external_value};
use user_picture;
use local_lbplanner\enums\{CAPABILITY, CAPABILITY_FLAG, KANBANCOL_TYPE_ORNONE, CAPABILITY_FLAG_ORNONE};
use local_lbplanner\helpers\{plan_helper, user_helper};

/**
 * Model class for model
 */
class user {
    /**
     * @var int $lbpid our userid
     */
    private int $lbpid;

    /**
     * @var int $mdlid moodle's userid
     */
    public int $mdlid;

    /**
     * @var string $theme selected theme
     */
    public string $theme;

    /**
     * @var string $colorblindness the kind of color blindness of a user
     */
    public string $colorblindness;

    /**
     * @var bool $displaytaskcount The display task count the user has selected in the app.
     */
    public bool $displaytaskcount;

    /**
     * @var bool $ekenabled Whether the user wants to see EK or not.
     */
    public bool $ekenabled;

    /**
     * @var bool $showcolumncolors Whether column colors should show in kanban board.
     */
    public bool $showcolumncolors;

    /**
     * @var ?int $defaultcapabilityview Which capability's view to show in app per default.
     * @see CAPABILITY_FLAG_ORNONE
     */
    public ?int $defaultcapabilityview;

    /**
     * @var ?string $automovecompletedtasks what kanban column to move completed tasks to (null → don't move)
     * @see KANBANCOL_TYPE
     */
    public ?string $automovecompletedtasks;

    /**
     * @var ?string $automovesubmittedtasks what kanban column to move submitted tasks to (null → don't move)
     * @see KANBANCOL_TYPE
     */
    public ?string $automovesubmittedtasks;

    /**
     * @var ?string $automoveoverduetasks what kanban column to move overdue tasks to (null → don't move)
     * @see KANBANCOL_TYPE
     */
    public ?string $automoveoverduetasks;

    /**
     * @var ?\stdClass $mdluser the cached moodle user
     */
    private ?\stdClass $mdluser;

    /**
     * @var ?string $pfp the cached pfp
     */
    private ?string $pfp;

    /**
     * @var ?int $planid the cached planid
     */
    private ?int $planid;

    /**
     * @var ?int $capabilitybitmask the cached user capability bitmask
     */
    private ?int $capabilitybitmask;

    /**
     * @var string[] DBMIRRORPROPS properties that are mirrored 1:1 between the DB and this object
     */
    private const DBMIRRORPROPS  = [
        'theme',
        'colorblindness',
        'displaytaskcount',
        'ekenabled',
        'showcolumncolors',
        'defaultcapabilityview',
        'automovecompletedtasks',
        'automovesubmittedtasks',
        'automoveoverduetasks',
    ];

    /**
     * Constructs a new course
     * @param int $lbpid ID of the Eduplanner user
     * @param int $mdlid ID of the moodle user
     * @param string $theme user-chosen theme
     * @param string $colorblindness user's colorblindness
     * @param bool $displaytaskcount user's display task count
     * @param bool $ekenabled whether the user wants to see EK modules
     * @param bool $showcolumncolors whether column colors should show in kanban board
     * @param ?string $automovecompletedtasks what kanban column to move completed tasks to (null → don't move)
     * @param ?string $automovesubmittedtasks what kanban column to move submitted tasks to (null → don't move)
     * @param ?string $automoveoverduetasks what kanban column to move overdue tasks to (null → don't move)
     */
    public function __construct(
        int $lbpid,
        int $mdlid,
        string $theme,
        string $colorblindness,
        bool $displaytaskcount,
        bool $ekenabled,
        bool $showcolumncolors,
        int $defaultcapabilityview,
        ?string $automovecompletedtasks,
        ?string $automovesubmittedtasks,
        ?string $automoveoverduetasks,
    ) {
        global $USER;
        $this->lbpid = $lbpid;
        $this->mdlid = $mdlid;
        foreach (self::DBMIRRORPROPS as $propname) {
            $propname = str_replace('_', '', $propname);
            $this->$propname = $$propname;
        }
        $this->planid = null;
        $this->pfp = null;
        $this->capabilitybitmask = null;

        if ($mdlid === (int) $USER->id) {
            $this->mdluser = $USER;
        } else {
            $this->mdluser = null;
        }
    }

    /**
     * Takes data from DB and makes a new user obj out of it
     *
     * @param object $obj the DB object to get data from
     * @return user a representation of this user and its data
     */
    public static function from_db(object $obj): self {
        $vars = get_object_vars($obj);
        // Rename the two properties that are different in the DB from in this object.
        $vars['lbpid'] = $vars['id'];
        $vars['mdlid'] = $vars['userid'];
        unset($vars['id']);
        unset($vars['userid']);
        // Just throw the whole assarr in the constructor. Surely nothing bad will happen.
        return new self(...$vars);
    }

    /**
     * Takes moodle user obj and makes new user obj out of it
     *
     * @param object $obj the moodleuser object to get data from
     * @return user a representation of this user and its data
     */
    public static function from_mdlobj(object $obj): self {
        $newobj = user_helper::get_user($obj->id);
        $newobj->set_mdluser($obj);
        return $newobj;
    }

    /**
     * Mark the object as freshly created and sets the new ID
     * @param int $lbpid the new ID after inserting into the DB
     * @throws \AssertionError
     */
    public function set_fresh(int $lbpid): void {
        assert($this->lbpid === 0);
        assert($lbpid !== 0);
        $this->lbpid = $lbpid;
    }

    /**
     * Sets colorblindness
     * @param string $cbn colorblindness
     */
    public function set_colorblindness(string $cbn): void {
        // TODO: remove in favour of setting member directly.
        $this->colorblindness = $cbn;
    }

    /**
     * Sets user theme
     * @param string $theme theme
     */
    public function set_theme(string $theme): void {
        // TODO: remove in favour of setting member directly.
        $this->theme = $theme;
    }

    /**
     * sets the associated moodle user (for caching)
     * @param \stdClass $mdluser
     */
    public function set_mdluser(\stdClass $mdluser): void {
        global $USER;
        if ($this->mdluser !== null) {
            if ($this->mdluser->id !== $USER->id) {
                throw new \coding_exception('tried to set cached mdluser twice');
            }
        }
        $this->mdluser = $mdluser;
    }

    /**
     * gets the associated moodle user
     * @return \stdClass mdluser
     */
    public function get_mdluser(): \stdClass {
        if ($this->mdluser === null) {
            $this->mdluser = user_helper::get_mdluser($this->mdlid);
        }

        return $this->mdluser;
    }

    /**
     * sets the associated plan ID (for caching)
     * @param int $planid
     */
    public function set_planid(int $planid): void {
        if ($this->planid !== null) {
            throw new \coding_exception('tried to set cached planid twice');
        }
        $this->planid = $planid;
    }

    /**
     * gets the associated plan ID
     * @return int planid
     */
    public function get_planid(): int {
        if ($this->planid === null) {
            $this->planid = plan_helper::get_plan_id($this->mdlid);
        }

        return $this->planid;
    }

    /**
     * gets the associated profile picture
     * @return string pfp
     */
    public function get_pfp(): string {
        if ($this->pfp === null) {
            global $PAGE;
            $userpicture = new user_picture($this->get_mdluser());
            $userpicture->size = 1; // Size f1.
            $this->pfp = $userpicture->get_url($PAGE)->out(false);
        }

        return $this->pfp;
    }

    /**
     * gets the user's capability bitmask
     * @return int
     */
    public function get_capabilitybitmask(): int {
        if ($this->capabilitybitmask === null) {
            $context = context_system::instance();
            $this->capabilitybitmask = 0;
            foreach (CAPABILITY::cases() as $case) {
                if (has_capability($case->value, $context, $this->mdlid, false)) {
                    $this->capabilitybitmask |= CAPABILITY::to_flag($case->value);
                }
            }
        }

        return $this->capabilitybitmask;
    }

    /**
     * Prepares data for the DB endpoint.
     * doesn't set ID if it's 0
     *
     * @return object a representation of this user and its data
     */
    public function prepare_for_db(): object {
        $obj = new \stdClass();

        $obj->userid = $this->mdlid;

        foreach (self::DBMIRRORPROPS as $propname) {
            $phppropname = str_replace('_', '', $propname);
            $obj->$propname = $this->$phppropname;
        }

        if ($this->lbpid !== 0) {
            $obj->id = $this->lbpid;
        }

        return $obj;
    }

    /**
     * Prepares shortened data for the API endpoint.
     *
     * @return array a shortened representation of this user and its data
     */
    public function prepare_for_api_short(): array {
        $mdluser = $this->get_mdluser();
        $capabilitybm = $this->get_capabilitybitmask();

        $data = [
            'userid' => $this->mdlid,
            'username' => $mdluser->username,
            'firstname' => $mdluser->firstname,
            'lastname' => $mdluser->lastname,
            'profileimageurl' => $this->get_pfp(),
            'capabilities' => $capabilitybm,
        ];

        if ($capabilitybm & CAPABILITY_FLAG::STUDENT && strlen($mdluser->address) > 0) {
            $data['vintage'] = $mdluser->address;
        }

        return $data;
    }

    /**
     * Returns the shortened data structure of a user for the API.
     *
     * @return external_single_structure The shortened data structure of a user for the API.
     */
    public static function api_structure_short(): external_single_structure {
        return new external_single_structure(
            [
                'userid' => new external_value(PARAM_INT, 'The id of the user'),
                'username' => new external_value(PARAM_TEXT, 'The username of the user'),
                'firstname' => new external_value(PARAM_TEXT, 'The firstname of the user'),
                'lastname' => new external_value(PARAM_TEXT, 'The lastname of the user'),
                'profileimageurl' => new external_value(PARAM_URL, 'The url of the profile image'),
                'vintage' => new external_value(PARAM_TEXT, 'The vintage of the user', VALUE_DEFAULT),
                'capabilities' => new external_value(PARAM_INT, 'The capabilities of the user represented as a bitmask value'),
            ]
        );
    }

    /**
     * Prepares full data for the API endpoint.
     *
     * @return array a full representation of this user and its data
     */
    public function prepare_for_api(): array {
        $mdluser = $this->get_mdluser();
        return array_merge(
            $this->prepare_for_api_short(),
            [
                'theme' => $this->theme,
                'ekenabled' => $this->ekenabled,
                'planid' => $this->get_planid(),
                'colorblindness' => $this->colorblindness,
                'displaytaskcount' => $this->displaytaskcount,
                'showcolumncolors' => $this->showcolumncolors,
                'defaultcapabilityview' => $this->defaultcapabilityview,
                'automovecompletedtasks' => $this->automovecompletedtasks ?? KANBANCOL_TYPE_ORNONE::NONE,
                'automovesubmittedtasks' => $this->automovesubmittedtasks ?? KANBANCOL_TYPE_ORNONE::NONE,
                'automoveoverduetasks' => $this->automoveoverduetasks ?? KANBANCOL_TYPE_ORNONE::NONE,
                'email' => $mdluser->email,
            ]
        );
    }

    /**
     * Returns the full data structure of a user for the API.
     *
     * @return external_single_structure The full data structure of a user for the API.
     */
    public static function api_structure(): external_single_structure {
        return new external_single_structure(
            [
                'userid' => new external_value(PARAM_INT, 'The id of the user'),
                'username' => new external_value(PARAM_TEXT, 'The username of the user'),
                'firstname' => new external_value(PARAM_TEXT, 'The firstname of the user'),
                'lastname' => new external_value(PARAM_TEXT, 'The lastname of the user'),
                'theme' => new external_value(PARAM_TEXT, 'The theme the user has selected'),
                'ekenabled' => new external_value(PARAM_BOOL, 'Whether the user wants to see EK modules'),
                'profileimageurl' => new external_value(PARAM_URL, 'The url of the profile image'),
                'planid' => new external_value(PARAM_INT, 'The id of the plan the user is assigned to'),
                'colorblindness' => new external_value(PARAM_TEXT, 'The colorblindness of the user'),
                'displaytaskcount' => new external_value(PARAM_BOOL, 'Whether the user has the taskcount enabled'),
                'showcolumncolors' => new external_value(PARAM_BOOL, 'Whether column colors should show in kanban board'),
                'defaultcapabilityview' => new external_value(
                    PARAM_INT,
                    'Which capability\'s view to show in app per default ' . CAPABILITY_FLAG_ORNONE::format()
                ),
                'automovecompletedtasks' => new external_value(
                    PARAM_TEXT,
                    'The kanban column to move a task to if completed ' . KANBANCOL_TYPE_ORNONE::format()
                ),
                'automovesubmittedtasks' => new external_value(
                    PARAM_TEXT,
                    'The kanban column to move a task to if submitted ' . KANBANCOL_TYPE_ORNONE::format()
                ),
                'automoveoverduetasks' => new external_value(
                    PARAM_TEXT,
                    'The kanban column to move a task to if overdue ' . KANBANCOL_TYPE_ORNONE::format()
                ),
                'capabilities' => new external_value(PARAM_INT, 'The capabilities of the user represented as a bitmask value'),
                'vintage' => new external_value(PARAM_TEXT, 'The vintage of the user', VALUE_DEFAULT),
                'email' => new external_value(PARAM_TEXT, 'The email address of the user'),
            ]
        );
    }
}
