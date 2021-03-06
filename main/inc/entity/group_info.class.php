<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @license see /license.txt
 * @author autogenerated
 */
class GroupInfo extends \CourseEntity
{
    /**
     * @return \Entity\Repository\GroupInfoRepository
     */
     public static function repository(){
        return \Entity\Repository\GroupInfoRepository::instance();
    }

    /**
     * @return \Entity\GroupInfo
     */
     public static function create(){
        return new self();
    }

    /**
     * @var integer $c_id
     */
    protected $c_id;

    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var integer $category_id
     */
    protected $category_id;

    /**
     * @var text $description
     */
    protected $description;

    /**
     * @var integer $max_student
     */
    protected $max_student;

    /**
     * @var boolean $doc_state
     */
    protected $doc_state;

    /**
     * @var boolean $calendar_state
     */
    protected $calendar_state;

    /**
     * @var boolean $work_state
     */
    protected $work_state;

    /**
     * @var boolean $announcements_state
     */
    protected $announcements_state;

    /**
     * @var boolean $forum_state
     */
    protected $forum_state;

    /**
     * @var boolean $wiki_state
     */
    protected $wiki_state;

    /**
     * @var boolean $chat_state
     */
    protected $chat_state;

    /**
     * @var string $secret_directory
     */
    protected $secret_directory;

    /**
     * @var boolean $self_registration_allowed
     */
    protected $self_registration_allowed;

    /**
     * @var boolean $self_unregistration_allowed
     */
    protected $self_unregistration_allowed;

    /**
     * @var integer $session_id
     */
    protected $session_id;


    /**
     * Set c_id
     *
     * @param integer $value
     * @return GroupInfo
     */
    public function set_c_id($value)
    {
        $this->c_id = $value;
        return $this;
    }

    /**
     * Get c_id
     *
     * @return integer 
     */
    public function get_c_id()
    {
        return $this->c_id;
    }

    /**
     * Set id
     *
     * @param integer $value
     * @return GroupInfo
     */
    public function set_id($value)
    {
        $this->id = $value;
        return $this;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $value
     * @return GroupInfo
     */
    public function set_name($value)
    {
        $this->name = $value;
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * Set category_id
     *
     * @param integer $value
     * @return GroupInfo
     */
    public function set_category_id($value)
    {
        $this->category_id = $value;
        return $this;
    }

    /**
     * Get category_id
     *
     * @return integer 
     */
    public function get_category_id()
    {
        return $this->category_id;
    }

    /**
     * Set description
     *
     * @param text $value
     * @return GroupInfo
     */
    public function set_description($value)
    {
        $this->description = $value;
        return $this;
    }

    /**
     * Get description
     *
     * @return text 
     */
    public function get_description()
    {
        return $this->description;
    }

    /**
     * Set max_student
     *
     * @param integer $value
     * @return GroupInfo
     */
    public function set_max_student($value)
    {
        $this->max_student = $value;
        return $this;
    }

    /**
     * Get max_student
     *
     * @return integer 
     */
    public function get_max_student()
    {
        return $this->max_student;
    }

    /**
     * Set doc_state
     *
     * @param boolean $value
     * @return GroupInfo
     */
    public function set_doc_state($value)
    {
        $this->doc_state = $value;
        return $this;
    }

    /**
     * Get doc_state
     *
     * @return boolean 
     */
    public function get_doc_state()
    {
        return $this->doc_state;
    }

    /**
     * Set calendar_state
     *
     * @param boolean $value
     * @return GroupInfo
     */
    public function set_calendar_state($value)
    {
        $this->calendar_state = $value;
        return $this;
    }

    /**
     * Get calendar_state
     *
     * @return boolean 
     */
    public function get_calendar_state()
    {
        return $this->calendar_state;
    }

    /**
     * Set work_state
     *
     * @param boolean $value
     * @return GroupInfo
     */
    public function set_work_state($value)
    {
        $this->work_state = $value;
        return $this;
    }

    /**
     * Get work_state
     *
     * @return boolean 
     */
    public function get_work_state()
    {
        return $this->work_state;
    }

    /**
     * Set announcements_state
     *
     * @param boolean $value
     * @return GroupInfo
     */
    public function set_announcements_state($value)
    {
        $this->announcements_state = $value;
        return $this;
    }

    /**
     * Get announcements_state
     *
     * @return boolean 
     */
    public function get_announcements_state()
    {
        return $this->announcements_state;
    }

    /**
     * Set forum_state
     *
     * @param boolean $value
     * @return GroupInfo
     */
    public function set_forum_state($value)
    {
        $this->forum_state = $value;
        return $this;
    }

    /**
     * Get forum_state
     *
     * @return boolean 
     */
    public function get_forum_state()
    {
        return $this->forum_state;
    }

    /**
     * Set wiki_state
     *
     * @param boolean $value
     * @return GroupInfo
     */
    public function set_wiki_state($value)
    {
        $this->wiki_state = $value;
        return $this;
    }

    /**
     * Get wiki_state
     *
     * @return boolean 
     */
    public function get_wiki_state()
    {
        return $this->wiki_state;
    }

    /**
     * Set chat_state
     *
     * @param boolean $value
     * @return GroupInfo
     */
    public function set_chat_state($value)
    {
        $this->chat_state = $value;
        return $this;
    }

    /**
     * Get chat_state
     *
     * @return boolean 
     */
    public function get_chat_state()
    {
        return $this->chat_state;
    }

    /**
     * Set secret_directory
     *
     * @param string $value
     * @return GroupInfo
     */
    public function set_secret_directory($value)
    {
        $this->secret_directory = $value;
        return $this;
    }

    /**
     * Get secret_directory
     *
     * @return string 
     */
    public function get_secret_directory()
    {
        return $this->secret_directory;
    }

    /**
     * Set self_registration_allowed
     *
     * @param boolean $value
     * @return GroupInfo
     */
    public function set_self_registration_allowed($value)
    {
        $this->self_registration_allowed = $value;
        return $this;
    }

    /**
     * Get self_registration_allowed
     *
     * @return boolean 
     */
    public function get_self_registration_allowed()
    {
        return $this->self_registration_allowed;
    }

    /**
     * Set self_unregistration_allowed
     *
     * @param boolean $value
     * @return GroupInfo
     */
    public function set_self_unregistration_allowed($value)
    {
        $this->self_unregistration_allowed = $value;
        return $this;
    }

    /**
     * Get self_unregistration_allowed
     *
     * @return boolean 
     */
    public function get_self_unregistration_allowed()
    {
        return $this->self_unregistration_allowed;
    }

    /**
     * Set session_id
     *
     * @param integer $value
     * @return GroupInfo
     */
    public function set_session_id($value)
    {
        $this->session_id = $value;
        return $this;
    }

    /**
     * Get session_id
     *
     * @return integer 
     */
    public function get_session_id()
    {
        return $this->session_id;
    }
}