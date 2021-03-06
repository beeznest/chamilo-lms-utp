<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @license see /license.txt
 * @author autogenerated
 */
class TrackCProviders extends \Entity
{
    /**
     * @return \Entity\Repository\TrackCProvidersRepository
     */
     public static function repository(){
        return \Entity\Repository\TrackCProvidersRepository::instance();
    }

    /**
     * @return \Entity\TrackCProviders
     */
     public static function create(){
        return new self();
    }

    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $provider
     */
    protected $provider;

    /**
     * @var integer $counter
     */
    protected $counter;


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
     * Set provider
     *
     * @param string $value
     * @return TrackCProviders
     */
    public function set_provider($value)
    {
        $this->provider = $value;
        return $this;
    }

    /**
     * Get provider
     *
     * @return string 
     */
    public function get_provider()
    {
        return $this->provider;
    }

    /**
     * Set counter
     *
     * @param integer $value
     * @return TrackCProviders
     */
    public function set_counter($value)
    {
        $this->counter = $value;
        return $this;
    }

    /**
     * Get counter
     *
     * @return integer 
     */
    public function get_counter()
    {
        return $this->counter;
    }
}