<?php

/* For licensing terms, see /license.txt */

/**
 * File containing the declaration of the learnpathList class.
 * @package	chamilo.learnpath
 * @author	Yannick Warnier <ywarnier@beeznest.org>
 */

/**
 * This class is only a learning path list container with several practical methods for sorting the list and
 * provide links to specific paths
 * @uses	Database.lib.php to use the database
 * @uses	learnpath.class.php to generate learnpath objects to get in the list
 */
class learnpathList
{

    public $list = array(); // Holds a flat list of learnpaths data from the database.
    public $ref_list = array(); // Holds a list of references to the learnpaths objects (only filled by get_refs()).
    public $alpha_list = array(); // Holds a flat list of learnpaths sorted by alphabetical name order.
    public $course_code;
    public $user_id;
    public $refs_active = false;

    /**
     * This method is the constructor for the learnpathList. It gets a list of available learning paths from
     * the database and creates the learnpath objects. This list depends on the user that is connected
     * (only displays) items if he has enough permissions to view them.
     * @param	integer		User ID
     * @param	string		Optional course code (otherwise we use api_get_course_id())
     * @param	int			Optional session id (otherwise we use api_get_session_id())
     * @return	void
     */
    function __construct($user_id, $course_code = '', $session_id = null, $order_by = null, $check_publication_dates = false, $getLPFromAllSessions = false)
    {
        $course_info = api_get_course_info($course_code);
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        $tbl_tool = Database::get_course_table(TABLE_TOOL_LIST);

        $this->course_code = $course_code;
        $this->user_id = $user_id;

        $course_id = $course_info['real_id'];

        if (empty($course_id)) {
            return false;
        }

        // Condition for the session.
        if (isset($session_id)) {
            $session_id = intval($session_id);
        } else {
            $session_id = api_get_session_id();
        }
        $condition_session = api_get_session_condition($session_id, true, true);

        if ($getLPFromAllSessions) {
            $condition_session = null;
        }
        $order = "ORDER BY display_order ASC, name ASC";
        if (isset($order_by)) {
            $order = Database::parse_conditions(array('order' => $order_by));
        }
        $now = api_get_utc_datetime();
        $time_conditions = '';

        if ($check_publication_dates) {
            $time_conditions = " AND ( (publicated_on <> '0000-00-00 00:00:00' AND publicated_on < '$now'  AND expired_on <> '0000-00-00 00:00:00'  AND expired_on > '$now' )  OR
                        (publicated_on <> '0000-00-00 00:00:00'  AND publicated_on < '$now'  AND expired_on = '0000-00-00 00:00:00') OR
                        (publicated_on = '0000-00-00 00:00:00'   AND expired_on <> '0000-00-00 00:00:00' AND expired_on > '$now') OR
                        (publicated_on = '0000-00-00 00:00:00'   AND expired_on = '0000-00-00 00:00:00' ))
            ";
        }

        $sql = "SELECT * FROM $lp_table WHERE c_id = $course_id $time_conditions $condition_session $order";

        $res = Database::query($sql);
        $names = array();
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            // Check if published.
            $pub = '';
            // Use domesticate here instead of Database::escape_string because
            // it prevents ' to be slashed and the input (done by learnpath.class.php::toggle_visibility())
            // is done using domesticate()
            $myname = domesticate($row['name']);
            $mylink = 'newscorm/lp_controller.php?action=view&lp_id=' . $row['id'] . '&id_session=' . $session_id;
            $sql2 = "SELECT * FROM $tbl_tool WHERE c_id = $course_id AND (name='$myname' and image='scormbuilder.gif' and link LIKE '$mylink%')";
            //error_log('New LP - learnpathList::__construct - getting visibility - '.$sql2, 0);
            $res2 = Database::query($sql2);
            if (Database::num_rows($res2) > 0) {
                $row2 = Database::fetch_array($res2);
                $pub = $row2['visibility'];
            } else {
                $pub = 'i';
            }
            // Check if visible.
            $vis = api_get_item_visibility(api_get_course_info($course_code), 'learnpath', $row['id'], $session_id);

            if (!empty($row['created_on']) && $row['created_on'] != '0000-00-00 00:00:00') {
                $row['created_on'] = $row['created_on'];
            } else {
                $row['created_on'] = '';
            }
            if (!empty($row['modified_on']) && $row['modified_on'] != '0000-00-00 00:00:00') {
                $row['modified_on'] = $row['modified_on'];
            } else {
                $row['modified_on'] = '';
            }

            if (!empty($row['publicated_on']) && $row['publicated_on'] != '0000-00-00 00:00:00') {
                $row['publicated_on'] = $row['publicated_on'];
            } else {
                $row['publicated_on'] = '';
            }

            if (!empty($row['expired_on']) && $row['expired_on'] != '0000-00-00 00:00:00') {
                $row['expired_on'] = $row['expired_on'];
            } else {
                $row['expired_on'] = '';
            }
            $this->list[$row['id']] = array(
                'lp_type' => $row['lp_type'],
                'lp_session' => $row['session_id'],
                'lp_name' => stripslashes($row['name']),
                'lp_desc' => stripslashes($row['description']),
                'lp_path' => $row['path'],
                'lp_view_mode' => $row['default_view_mod'],
                'lp_force_commit' => $row['force_commit'],
                'lp_maker' => stripslashes($row['content_maker']),
                'lp_proximity' => $row['content_local'],
                //'lp_encoding'     => $row['default_encoding'],
                'lp_encoding' => api_get_system_encoding(), // Chamilo 1.8.8: We intend always to use the system encoding.
                'lp_visibility' => $vis,
                'lp_published' => $pub,
                'lp_prevent_reinit' => $row['prevent_reinit'],
                'seriousgame_mode' => $row['seriousgame_mode'],
                'lp_scorm_debug' => $row['debug'],
                'lp_display_order' => $row['display_order'],
                'lp_preview_image' => stripslashes($row['preview_image']),
                'autolaunch' => $row['autolunch'],
                'session_id' => $row['session_id'],
                'created_on' => $row['created_on'],
                'modified_on' => $row['modified_on'],
                'publicated_on' => $row['publicated_on'],
                'expired_on' => $row['expired_on']
            );
            $names[$row['name']] = $row['id'];
        }
        $this->alpha_list = asort($names);
    }

    /**
     * Gets references to learnpaths for all learnpaths IDs kept in the local list.
     * This applies a transformation internally on list and ref_list and returns a copy of the refs list
     * @return	array	List of references to learnpath objects
     */
    function get_refs()
    {
        foreach ($this->list as $id => $dummy) {
            $this->ref_list[$id] = new learnpath($this->course_code, $id, $this->user_id);
        }
        $this->refs_active = true;
        return $this->ref_list;
    }

    /**
     * Gets a table of the different learnpaths we have at the moment
     * @return	array	Learnpath info as [lp_id] => ([lp_type]=> ..., [lp_name]=>...,[lp_desc]=>...,[lp_path]=>...)
     */
    function get_flat_list()
    {
        return $this->list;
    }

    /**
     *  Gets a list of lessons  of the given course_code and session_id
     *  This functions doesn't need user_id
     *  @param string Text code of the course
     *  @param int  Id of session
     *  @return array List of lessons with lessons id as keys
     */
    static function get_course_lessons($course_code, $session_id = null)
    {
        $tbl_course_lp = Database::get_course_table(TABLE_LP_MAIN);
        $course = api_get_course_info($course_code);
        $sessionCondition = null;
        if (!empty($session_id)) {
            $sessionCondition = api_get_session_condition(
                $session_id,
                true,
                false
            );
        }
        $sql = "SELECT * FROM $tbl_course_lp
                WHERE c_id = %s $sessionCondition ";  //TODO AND session_id = %s ?
        $sql_query = sprintf($sql, $course['real_id']);
        $result = Database::query($sql_query);

        $lessons = array();
        while ($row = Database::fetch_array($result)) {
            if (api_get_item_visibility($course, 'learnpath', $row['id'], $session_id)) {
                $lessons[$row['id']] = $row;
            }
        }
        return $lessons;
    }

    /**
     * Gets a table of the different learnpaths we have at the moment
     * group by units (it works just for UTP)
     * @return	array	Learnpath info as [lp_id] => ([lp_type]=> ..., [lp_name]=>...,[lp_desc]=>...,[lp_path]=>...)
     */
    function getLearnpathListTable()
    {
        $tblField = Database::get_main_table(TABLE_MAIN_LP_FIELD);
        $tblFieldOpt = Database::get_main_table(TABLE_MAIN_LP_FIELD_OPTIONS);
        $tblFieldVal = Database::get_main_table(TABLE_MAIN_LP_FIELD_VALUES);
        $tblCourse = Database::get_main_table(TABLE_MAIN_COURSE);

        $units = array();
        foreach ($this->list as $lpId => $lp) {
            $sql = "SELECT lo.option_value
                    FROM $tblFieldVal lv
                    INNER JOIN $tblField lf ON lf.id =  lv.field_id
                                               AND lf.field_variable = 'Unidad'
                    INNER JOIN $tblFieldOpt lo ON lv.field_value = lo.id
                                               AND lf.id = lo.field_id
                    INNER JOIN $tblCourse c ON c.id = lv.c_id
                    WHERE lv.lp_id = {$lpId}
                    AND c.code = '{$this->course_code}'
                    GROUP BY lo.option_value";

            $res = Database::query($sql);
            while ($row = Database::fetch_array($res, 'ASSOC')) {
                $units[$row['option_value']] = $lp;
                $units[$row['option_value']]['lp_id'] = $lpId;
            }
        }

        return $units;
    }

}
