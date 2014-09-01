<?php
namespace Entities;

class Deployement {

    /**
     * @JsonDB(type="date")
     */
    protected $date;

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

    public function __construct() {}
}
