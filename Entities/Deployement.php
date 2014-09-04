<?php
namespace Entities;

class Deployement extends Entity{

    /**
     * @JsonDB\type("date")
     */
    public $date;

    protected $release_manager;

    protected $trunk;

    protected $release_candidate;

    protected $release;

    protected $commiter;

    protected $deployement;

    /**
     * @JsonDB\type("boolean")
     */
    protected $staging;

    /**
     * @JsonDB\type("boolean")
     */
    protected $prod;

    protected $file;

    public function renderProd () {
        return $this->renderBoolean($this->prod);
    }

    public function renderStaging () {
        return $this->renderBoolean($this->staging);
    }

    public function renderRowClassTrunk () {
        return ($this->trunk)? '' : 'danger';
    }

    public function renderRowClassStaging () {
        return $this->renderRowClassBoolean($this->staging);
    }

    public function renderRowClassProd () {
        return $this->renderRowClassBoolean($this->prod);
    }

    public function renderRowClassBoolean ($value) {
        return ($value)? 'success' : 'danger';
    }

    protected function renderBoolean ($value) {
        if ($value) {
            return '<span class="glyphicon glyphicon-ok green"></span>';
        }

        return '<span class="glyphicon glyphicon-remove red"></span>';
    }

}
