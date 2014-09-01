<?php
namespace Entities;

class Deployement extends Entity{

    /**
     * @JsonDB(type="date")
     */
    public $date;

    protected $release_manager;

    protected $trunk;

    protected $release_candidate;

    protected $release;

    protected $commiter;

    protected $deployement;
    
    /**
     * @JsonDB(type="boolean")
     */
    protected $staging;

    /**
     * @JsonDB(type="boolean")
     */
    protected $prod;

    protected $file;

}
