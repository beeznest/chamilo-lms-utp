<?php
/**
 * Chamilo-OpenMeetings integration plugin library, defining methods to connect
 * to OpenMeetings from Chamilo by calling its web services
 * @package chamilo.plugin.openmeetings
 */
/**
 * Initialization
 */
namespace Chamilo\Plugin\OpenMeetings;
include_once __DIR__.'/session.class.php';
include_once __DIR__.'/room.class.php';
include_once __DIR__.'/user.class.php';
/**
 * Open Meetings-Chamilo connector class
 */
class OpenMeetings
{

    public $url;
    public $user;
    public $pass;
    public $api;
    public $user_complete_name = null;
    public $protocol = 'http://';
    public $debug = false;
    public $logout_url = null;
    public $plugin_enabled = false;
    public $sessionId = "";
    public $roomName = '';
    public $chamiloCourseId;
    public $chamiloSessionId;
    public $externalType;

    /**
     * Constructor (generates a connection to the API and the Chamilo settings
     * required for the connection to the videoconference server)
     */
    function __construct()
    {
        // initialize video server settings from global settings
        $plugin = \OpenMeetingsPlugin::create();

        $om_plugin = $plugin->get('tool_enable');
        $om_host   = $plugin->get('host');
        $om_user   = $plugin->get('user');
        $om_pass   = $plugin->get('pass');
        global $_configuration;
        $accessUrl = api_get_access_url($_configuration['access_url']);
        $this->externalType = substr($accessUrl['url'],strpos($accessUrl['url'],'://')+3,-1);
        if (strcmp($this->externalType,'localhost') == 0) {
            $this->externalType = substr(api_get_path(WEB_PATH),strpos(api_get_path(WEB_PATH),'://')+3,-1);
        }
        $this->externalType = 'chamilolms.'.$this->externalType;

        $this->table = \Database::get_main_table('plugin_openmeetings');

        if ($om_plugin) {
            $user_info = api_get_user_info();
            $this->user_complete_name = $user_info['complete_name'];
            $this->user = $om_user;
            $this->pass = $om_pass;
            $this->url = $om_host;

            // Setting OM api
            define('CONFIG_OPENMEETINGS_USER', $this->user);
            define('CONFIG_OPENMEETINGS_PASS', $this->pass);
            define('CONFIG_OPENMEETINGS_SERVER_URL', $this->url);

            $this->gateway = new \OpenMeetingsGateway($this->url, $this->user, $this->pass);
            $this->plugin_enabled = $om_plugin;
            // The room has a name composed of C + course ID + '-' + session ID
            $this->chamiloCourseId = api_get_course_int_id();
            $this->chamiloSessionId = api_get_session_id();
            $this->roomName = 'C'.$this->chamiloCourseId.'-'.$this->chamiloSessionId;
            $return = $this->gateway->loginUser();
            if ($return == 0) {
                $msg = 'Could not initiate session with server through OpenMeetingsGateway::loginUser()';
                error_log(__FILE__.'+'.__LINE__.': '.$msg);
                die($msg);
            }
            $this->sessionId = $this->gateway->sessionId;
        }
    }
    /**
     * Checks whether a user is teacher in the current course
     * @return bool True if the user can be considered a teacher in this course, false otherwise
     */
    function isTeacher()
    {
        return api_is_course_admin() || api_is_coach() || api_is_platform_admin();
    }
    /**
     * Login the user with OM Server. This generates a session ID that is
     * specific to the current user, but that does not require specific user data
     *
     * It is similar to opening a PHP session. In fact, the session ID is kept
     * inside the $_SESSION['openmeetings_session'] session variable
     * @return bool True if the user is correct and false when is incorrect
     * @deprecated loginUser now called at object instanciation
     */
    /**
    function loginUser()
    {
        try {
            //Verifying if there is already an active session
            if (empty($_SESSION['openmeetings_session'])) {
                // Login user returns either 0 or >0, depending on the results
                // Technically, as long as the SOAP user has been configured in OpenMeetings and OpenMeetings is on, this should always succeed.
                if ($this->gateway->loginUser()) {
                    $this->sessionId = $_SESSION['openmeetings_session'] = $this->gateway->session_id;
                    return true;
                } else {
                    error_log('loginUser did not succeed');
                    return false;
                }
            } else {
                $this->sessionId = $_SESSION['openmeetings_session'];
                return true;
            }
        } catch (SoapFault $e) {
            error_log(__FILE__.'+'.__LINE__.' Warning: We have detected some problems. Fault: '.$e->faultstring);
            return false;
        }
    }
    */
    /*
     * Creating a Room for the meeting
    * @return bool True if the user is correct and false when is incorrect
    */
    function createMeeting($params)
    {
        //$id = \Database::insert($this->table, $params);
        // First, try to see if there is an active room for this course and session
        $roomId = null;
        $meetingData = \Database::select('*', $this->table, array('where' => array('c_id = ?' => $this->chamiloCourseId, ' AND session_id = ? ' => $this->chamiloSessionId)), 'first');
        if ($meetingData != false && count($meetingData) > 0) {
            //error_log(print_r($meetingData,1));
            //error_log('Found previous room reference - reusing');
            // There has been a room in the past for this course. It should
            // still be on the server, so update (instead of creating a new one)
            // This fills the following attributes: status, name, comment, chamiloCourseId, chamiloSessionId
            $room = new Room();
            $room->loadRoomId($meetingData['room_id']);
            $roomArray = (array)$room;
            $roomArray['SID'] = $this->sessionId;
            $roomId = $this->gateway->updateRoomWithModeration($room);
            if ($roomId != $meetingData['room_id']) {
                $msg = 'Something went wrong: the updated room ID ('.$roomId.') is not the same as the one we had ('.$meetingData['room_id'].')';
                error_log($msg);
                die($msg);
            }

        } else {
            //error_log('Found no previous room - creating');
            $room = new Room();
            $room->SID = $this->sessionId;
            $room->name = $this->roomName;
            $room->roomtypes_id = $room->roomtypes_id;
            $room->comment = urlencode(get_lang('Course').': ' . $params['meeting_name'] . ' Plugin for Chamilo');
            $room->numberOfPartizipants = $room->numberOfPartizipants;
            $room->ispublic = $room->getString('isPublic');
            $room->appointment = $room->getString('appointment');
            $room->isDemoRoom = $room->getString('isDemoRoom');
            $room->demoTime = $room->demoTime;
            $room->isModeratedRoom = $room->getString('isModeratedRoom');
            $roomId = $this->gateway->createRoomWithModAndType($room);
        }

        if (!empty($roomId)) {
            /*
            // Find the biggest room_id so far, and create a new one
            if (empty($roomId)) {
                $roomData = \Database::select('MAX(room_id) as room_id', $this->table, array(), 'first');
                $roomId = $roomData['room_id'] + 1;
            }*/

            $params['status'] = '1';
            $params['meeting_name'] = $room->name;
            $params['created_at'] = api_get_utc_datetime();
            $params['room_id'] = $roomId;
            $params['c_id'] = api_get_course_int_id();
            $params['session_id'] = api_get_session_id();

            $id = \Database::insert($this->table, $params);

            $this->joinMeeting($id);
        } else {
            return -1;
        }
    }
    /**
     * Returns a meeting "join" URL
     * @param string The name of the meeting (usually the course code)
     * @return mixed The URL to join the meeting, or false on error
     * @todo implement moderator pass
     * @assert ('') === false
     * @assert ('abcdefghijklmnopqrstuvwxyzabcdefghijklmno') === false
     */
    function joinMeeting($meetingId)
    {
        if (empty($meetingId)) {
            return false;
        }
        $meetingData = \Database::select('*', $this->table, array('where' => array('id = ? AND status = 1 ' => $meetingId)), 'first');

        if (empty($meetingData)) {
            if ($this->debug) error_log("meeting does not exist: $meetingId ");
            return false;
        }
        $params = array( 'room_id' => $meetingData['room_id'] );

        $returnVal = $this->setUserObjectAndGenerateRoomHashByURLAndRecFlag( $params );
        //$urlWithoutProtocol = str_replace("http://",  CONFIG_OPENMEETINGS_SERVER_URL);
        //$imgWithoutProtocol = str_replace("http://", $_SESSION['_user']['avatar'] );

        $iframe = $this->url . "/?" .
                "secureHash=" . $returnVal;

        printf("<iframe src='%s' width='%s' height = '%s' />", $iframe, "100%", 640);
    }
    /**
     * Checks if the videoconference server is running.
     * Function currently disabled (always returns 1)
     * @return bool True if server is running, false otherwise
     * @assert () === false
     */
    function isServerRunning()
    {
        // Always return true for now as this requires the openmeetings object
        // to have been instanciated and this includes a loginUser() which
        // connects to the server
        return true;
    }
     /**
     * Gets the password for a specific meeting for the current user
     * @return string A moderator password if user is teacher, or the course code otherwise
     */
    function getMeetingUserPassword()
    {
        if ($this->isTeacher()) {
            return $this->getMeetingModerationPassword();
        } else {
            return api_get_course_id();
        }
    }
    /**
     * Generated a moderator password for the meeting
     * @return string A password for the moderation of the video conference
     */
    function getMeetingModerationPassword()
    {
        return api_get_course_id().'mod';
    }
    /**
     * Get information about the given meeting
     * @param array ...?
     * @return mixed Array of information on success, false on error
     * @assert (array()) === false
     */
    function getMeetingInfo($params)
    {
        try {
            $result = $this->api->getMeetingInfoArray($params);
            if ($result == null) {
                if ($this->debug) error_log(__FILE__.'+'.__LINE__." Failed to get any response. Maybe we can't contact the OpenMeetings server.");
            } else {
                return $result;
            }
        } catch (Exception $e) {
            if ($this->debug) error_log(__FILE__.'+'.__LINE__.' Caught exception: ', $e->getMessage(), "\n");
        }
        return false;
    }

    /**
     * @param array $params Array of parameters
     * @return mixed
     */
    function setUserObjectAndGenerateRecordingHashByURL( $params )
    {
        $username = $_SESSION['_user']['username'];
        $firstname = $_SESSION['_user']['firstname'];
        $lastname = $_SESSION['_user']['lastname'];
        $userId = $_SESSION['_user']['user_id'];
        $systemType = 'chamilo';
        $room_id = $params['room_id'];

        $urlWsdl = $this->url . "/services/UserService?wsdl";
        $omServices = new \SoapClient( $urlWsdl );
        $objRec = new User();

        $objRec->SID = $this->sessionId;
        $objRec->username = $username;
        $objRec->firstname = $firstname;
        $objRec->lastname = $lastname;
        $objRec->externalUserId = $userId;
        $objRec->externalUserType = $systemType;
        $objRec->recording_id = $recording_id;

        $orFn = $omServices->setUserObjectAndGenerateRecordingHashByURL( $objRec );
        return $orFn->return;
     }

    /**
     * @param Array $params Array of parameters
     * @return mixed
     */
    function setUserObjectAndGenerateRoomHashByURLAndRecFlag( $params )
    {

        $username = $_SESSION['_user']['username'];
        $firstname = $_SESSION['_user']['firstname'];
        $lastname = $_SESSION['_user']['lastname'];
        $profilePictureUrl = $_SESSION['_user']['avatar'];
        $email = $_SESSION['_user']['mail'];
        $userId = $_SESSION['_user']['user_id'];
        $systemType = 'Chamilo';
        $room_id = $params['room_id'];
        $becomeModerator = ( $this->isTeacher() ? 1 : 0 );
        $allowRecording = 1; //Provisional

        $urlWsdl = $this->url . "/services/UserService?wsdl";
        $omServices = new \SoapClient( $urlWsdl );
        $objRec = new User();

        $objRec->SID = $this->sessionId;
        $objRec->username = $username;
        $objRec->firstname = $firstname;
        $objRec->lastname = $lastname;
        $objRec->profilePictureUrl = $profilePictureUrl;
        $objRec->email = $email;
        $objRec->externalUserId = $userId;
        $objRec->externalUserType = $systemType;
        $objRec->room_id = $room_id;
        $objRec->becomeModeratorAsInt = $becomeModerator;
        $objRec->showAudioVideoTestAsInt = 1;
        $objRec->allowRecording = $allowRecording;

        $rcFn = $omServices->setUserObjectAndGenerateRoomHashByURLAndRecFlag( $objRec );

        return $rcFn->return;
    }

    /**
     * Gets all the course meetings saved in the plugin_openmeetings table
     * @return array Array of current open meeting rooms
     */
    function getCourseMeetings()
    {
        $newMeetingsList = array();
        $openMeeting = false;
        $item = array();
        $meetingsList = \Database::select('*', $this->table, array('where' => array('c_id = ? ' => api_get_course_int_id(), ' AND session_id = ? ' => api_get_session_id())));

        //error_log(__FILE__.'+'.__LINE__.' Meetings found: '.print_r($meetingsList,1));

        $urlWsdl = $this->url . "/services/RoomService?wsdl";
        $omServices = new \SoapClient($urlWsdl);
        $room = new Room();
        /*
        try {
            $rooms = $this->gateway->getRoomsWithCurrentUsersByType();
            //$rooms = $omServices->getRoomsPublic(array(
                //'SID' => $this->sessionId,
                //'start' => 0,
                //'max' => 10,
                //'orderby' => 'name',
                //'asc' => 'true',
                //'externalRoomType' => 'chamilo',
                //'roomtypes_id' => 'chamilo',
                //)
            //);
        } catch (SoapFault $e) {
            error_log(__FILE__.'+'.__LINE__.' '.$e->faultstring);
            //error_log($rooms->getDebug());
            return false;
        }
        */
        $room->SID = $this->sessionId;
        //error_log(__FILE__.'+'.__LINE__.' Meetings found: '.print_r($room->SID,1));

        foreach ($meetingsList as $meetingDb) {
            //$room->rooms_id = $meetingDb['room_id'];
            //error_log(__FILE__.'+'.__LINE__.' Meetings found: '.print_r($meetingDb['room_id'],1));
            $remoteMeeting = array();
            $meetingDb['created_at'] = api_get_local_time($meetingDb['created_at']);
            $meetingDb['closed_at'] = (!empty($meetingDb['closed_at'])?api_get_local_time($meetingDb['closed_at']):'');
            // Fixed value for now
            $meetingDb['participantCount'] = 40;

            // The following code is currently commented because the web service
            // says this is not allowed by the SOAP user.
            /*
            try {
                // Get the conference room object from OpenMeetings server - requires SID and rooms_id to be defined
                $objRoomId = $this->gateway->getRoomById($meetingDb['room_id']);
                if (empty($objRoomId->return)) {
                    error_log(__FILE__.'+'.__LINE__.' Emptyyyyy ');
                    //\Database::delete($this->table, "id = {$meetingDb['id']}");
                    // Don't delete expired rooms, just mark as closed
                    \Database::update($this->table, array('status' => 0, 'closed_at' => api_get_utc_datetime()), array('id = ? ' => $meetingDb['id']));
                    continue;
                }
                //$objCurUs = $omServices->getRoomWithCurrentUsersById($objCurrentUsers);
            } catch  (SoapFault $e) {
                error_log(__FILE__.'+'.__LINE__.' '.$e->faultstring);
                exit;
            }
            //if( empty($objCurUs->returnMeetingID) ) continue;

            $current_room = array(
                'roomtype' => $objRoomId->return->roomtype->roomtypes_id,
                'meetingName' => $objRoomId->return->name,
                'meetingId' => $objRoomId->return->meetingID,
                'createTime' => $objRoomId->return->rooms_id,
                'showMicrophoneStatus' => $objRoomId->return->showMicrophoneStatus,
                'attendeePw' => $objRoomId->return->attendeePW,
                'moderatorPw' => $objRoomId->return->moderators,
                'isClosed' => $objRoomId->return->isClosed,
                'allowRecording' => $objRoomId->return->allowRecording,
                'startTime' => $objRoomId->return->startTime,
                'endTime' => $objRoomId->return->updatetime,
                'participantCount' => count($objRoomId->return->currentusers),
                'maxUsers' => $objRoomId->return->numberOfPartizipants,
                'moderatorCount' => count($objRoomId->return->moderators)
            );
                // Then interate through attendee results and return them as part of the array:
            if (!empty($objRoomId->return->currentusers)) {
                    foreach ($objRoomId->return->currentusers as $a)
                      $current_room[] = array(
                                'userId' => $a->username,
                                'fullName' => $a->firstname . " " . $a->lastname,
                                'isMod' => $a->isMod
                      );
            }

            $remoteMeeting = $current_room;
            */

            if (empty( $remoteMeeting )) {
            /*
                error_log(__FILE__.'+'.__LINE__.' Empty remote Meeting for now');
                if ($meetingDb['status'] == 1 && $this->isTeacher()) {
                    $this->endMeeting($meetingDb['id']);
                }
            */
            } else {
                $remoteMeeting['add_to_calendar_url'] = api_get_self().'?action=add_to_calendar&id='.$meetingDb['id'].'&start='.api_strtotime($meetingDb['startTime']);
            }
            $remoteMeeting['end_url'] = api_get_self().'?action=end&id='.$meetingDb['id'];

            //$record_array = array();

//            if ($meetingDb['record'] == 1) {
//                $recordingParams = array(
//                    'meetingId' => $meetingDb['id'],        //-- OPTIONAL - comma separate if multiple ids
//                );
//
//                $records = $this->api->getRecordingsWithXmlResponseArray($recordingParams);
//                if (!empty($records)) {
//                    $count = 1;
//                    if (isset($records['message']) && !empty($records['message'])) {
//                        if ($records['messageKey'] == 'noRecordings') {
//                            $record_array[] = get_lang('NoRecording');
//                        } else {
//                            //$record_array[] = $records['message'];
//                        }
//                    } else {
//                        foreach ($records as $record) {
//                            if (is_array($record) && isset($record['recordId'])) {
//                                $url = Display::url(get_lang('ViewRecord'), $record['playbackFormatUrl'], array('target' => '_blank'));
//                                if ($this->is_teacher()) {
//                                    $url .= Display::url(Display::return_icon('link.gif',get_lang('CopyToLinkTool')), api_get_self().'?action=copy_record_to_link_tool&id='.$meetingDb['id'].'&record_id='.$record['recordId']);
//                                    $url .= Display::url(Display::return_icon('agenda.png',get_lang('AddToCalendar')), api_get_self().'?action=add_to_calendar&id='.$meetingDb['id'].'&start='.api_strtotime($meetingDb['created_at']).'&url='.$record['playbackFormatUrl']);
//                                    $url .= Display::url(Display::return_icon('delete.png',get_lang('Delete')), api_get_self().'?action=delete_record&id='.$record['recordId']);
//                                }
//                                //$url .= api_get_self().'?action=publish&id='.$record['recordID'];
//                                $count++;
//                                $record_array[] = $url;
//                            } else {
//
//                            }
//                        }
//                    }
//                }
//                //var_dump($record_array);
//                $item['show_links']  = implode('<br />', $record_array);
//
//            }
//
             //$item['created_at'] = api_convert_and_format_date($meetingDb['created_at']);
//            //created_at
//
//            $item['publish_url'] = api_get_self().'?action=publish&id='.$meetingDb['id'];
//            $item['unpublish_url'] = api_get_self().'?action=unpublish&id='.$meetingDb['id'];
//
            //if ($meetingDb['status'] == 1) {
//                $joinParams = array(
//                    'meetingId' => $meetingDb['id'],        //-- REQUIRED - A unique id for the meeting
//                    'username' => $this->user_complete_name,    //-- REQUIRED - The name that will display for the user in the meeting
//                    'password' => $pass,            //-- REQUIRED - The attendee or moderator password, depending on what's passed here
//                    'createTime' => '',            //-- OPTIONAL - string. Leave blank ('') unless you set this correctly.
//                    'userID' => '',            //    -- OPTIONAL - string
//                    'webVoiceConf' => ''    //    -- OPTIONAL - string
//                );
//                $returnVal = $this->setUserObjectAndGenerateRoomHashByURLAndRecFlag( array('room_id' => $meetingDb['id']) );
//                $joinUrl = CONFIG_OPENMEETINGS_SERVER_URL . "?" .
//                           "secureHash=" . $returnVal;
//
//                $item['go_url'] = $joinUrl;
            //}
            $item = array_merge($item, $meetingDb, $remoteMeeting);
            //error_log(__FILE__.'+'.__LINE__.'  Item: '.print_r($item,1));
            $newMeetingsList[] = $item;
        } //end foreach $meetingsList
        return $newMeetingsList;
    }

    /**
     * Send a command to the OpenMeetings server to close the meeting
     * @param $meetingId
     * @return int
     */
    function endMeeting($meetingId)
    {
        try {
            $urlWsdl = $this->url . "/services/RoomService?wsdl";
            $ws = new \SoapClient( $urlWsdl );
            $room = new Room($meetingId);
            $room->SID = $this->sessionId;
            $room->room_id = intval($meetingId);
            $room->status = false;
            $roomClosed = $ws->closeRoom($room);
            if ($roomClosed > 0) {
                //error_log(__FILE__.'+'.__LINE__.' Closing returned '.print_r($roomClosed,1));
                \Database::update($this->table, array('status' => 0, 'closed_at' => api_get_utc_datetime()), array('id = ? ' => $meetingId));
            }
            //error_log(__FILE__.'+'.__LINE__.' Finished closing');
        } catch (SoapFault $e) {
            error_log(__FILE__.'+'.__LINE__.' Warning: We have detected some problems: Fault: '.$e->faultstring);
            exit;
            return -1;
        }
    }
}