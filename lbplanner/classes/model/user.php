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
 * Model for a course
 *
 * @package local_lbplanner
 * @subpackage helpers
 * @copyright 2024 NecodeIT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lbplanner\model;

use coding_exception;
use core_user;
use external_single_structure;
use external_value;
use local_lbplanner\helpers\plan_helper;
use local_lbplanner\helpers\user_helper;
use user_picture;

/**
 * Model class for course
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
     * @var string $lang user language
     */
    public string $lang;

    /**
     * @var string $colorblindness the kind of color blindness of a user
     */
    public string $colorblindness;

    /**
     * @var int $displaytaskcount The display task count the user has selected in the app.
     */
    public int $displaytaskcount;

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
     * Constructs a new course
     */
    public function __construct(
        int $lbpid,
        int $mdlid,
        string $theme,
        string $lang,
        string $colorblindness,
        int $displaytaskcount
    ) {
        global $USER;
        $this->lbpid = $lbpid;
        $this->mdlid = $mdlid;
        $this->set_theme($theme);
        $this->set_lang($lang);
        $this->set_colorblindness($colorblindness);
        $this->set_displaytaskcount($displaytaskcount);
        $this->planid = null;
        $this->pfp = null;

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
        return new self($obj->id, $obj->userid, $obj->theme, $obj->language, $obj->colorblindness, $obj->displaytaskcount);
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
     * Sets display task count
     * @param int $count display task count
     * @throws \coding_exception if $count <= 0
     */
    public function set_displaytaskcount(int $count): void {
        if ($count <= 0) {
            throw new \coding_exception('User\'s Display Task Count cannot be <= 0');
        }
        $this->displaytaskcount = $count;
    }

    /**
     * Sets colorblindness
     * @param string $cbn colorblindness
     */
    public function set_colorblindness(string $cbn): void {
        $this->colorblindness = $cbn;
    }

    /**
     * Sets user language
     * @param string $lang language
     * @throws \coding_exception if $lang isn't ISO 639-1 conformant
     */
    public function set_lang(string $lang): void {
        if (preg_match('/^[a-z]{2}$/', $lang) !== 1) {
            throw new \coding_exception('Incorrect language format - must be ISO 639-1, e.g. en or de');
        }
        $this->lang = $lang;
    }

    /**
     * Sets user theme
     * @param string $theme theme
     */
    public function set_theme(string $theme): void {
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
     * Prepares data for the DB endpoint.
     * doesn't set ID if it's 0
     *
     * @return object a representation of this course and its data
     */
    public function prepare_for_db(): object {
        $obj = new \stdClass();

        $obj->userid = $this->mdlid;
        $obj->theme = $this->theme;
        $obj->language = $this->lang;
        $obj->colorblindness = $this->colorblindness;
        $obj->displaytaskcount = $this->displaytaskcount;

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
        return [
            'userid' => $this->mdlid,
            'username' => $mdluser->username,
            'firstname' => $mdluser->firstname,
            'lastname' => $mdluser->lastname,
            'profileimageurl' => $this->get_pfp(),
            'vintage' => $mdluser->address,
        ];
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
                'vintage' => new external_value(PARAM_TEXT, 'The vintage of the user'),
            ]
        );
    }

    /**
     * Prepares full data for the API endpoint.
     *
     * @param bool $access whether the full-access data should be prepared
     * @return array a full representation of this user and its data
     */
    public function prepare_for_api(): array {
        $mdluser = $this->get_mdluser();
        return [
            'userid' => $mdluser->id,
            'username' => $mdluser->username,
            'firstname' => $mdluser->firstname,
            'lastname' => $mdluser->lastname,
            'theme' => $this->theme,
            'lang' => $this->lang,
            'profileimageurl' => $this->get_pfp(),
            'planid' => $this->get_planid(),
            'colorblindness' => $this->colorblindness,
            'displaytaskcount' => $this->displaytaskcount,
            'capabilities' => user_helper::get_user_capability_bitmask($this->mdlid),
            'vintage' => $mdluser->address,
        ];
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
                'lang' => new external_value(PARAM_TEXT, 'The language the user has selected'),
                'profileimageurl' => new external_value(PARAM_URL, 'The url of the profile image'),
                'planid' => new external_value(PARAM_INT, 'The id of the plan the user is assigned to'),
                'colorblindness' => new external_value(PARAM_TEXT, 'The colorblindness of the user'),
                'displaytaskcount' => new external_value(PARAM_INT, 'If the user has the taskcount-enabled 1-yes 0-no'),
                'capabilities' => new external_value(PARAM_INT, 'The capabilities of the user represented as a bitmask value'),
                'vintage' => new external_value(PARAM_TEXT, 'The vintage of the user'),
            ]
        );
    }
}
