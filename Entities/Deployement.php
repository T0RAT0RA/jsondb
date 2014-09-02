<?php
namespace Entities;

class Deployement extends Entity{

    /**
     * @JsonDB\type("date")
     * @JsonDB\style(width="135px")
     * @JsonDB\class("text-center")
     */
    public $date;

    /**
     * @JsonDB\style(width="100px")
     */
    protected $release_manager;

    /**
     * @JsonDB\class("text-center")
     */
    protected $trunk;

    /**
     * @JsonDB\class("text-center")
     */
    protected $release_candidate;

    /**
     * @JsonDB\class("text-center")
     */
    protected $release;

    protected $commiter;

    protected $deployement;

    /**
     * @JsonDB\type("boolean")
     * @JsonDB\class("text-center")
     */
    protected $staging;

    /**
     * @JsonDB\type("boolean")
     * @JsonDB\class("text-center")
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
        $html = '<div class="text-center">';
        if ($value) {
            $html .= '<span class="glyphicon glyphicon-ok green"></span>';
        } else { 
            $html .= '<span class="glyphicon glyphicon-remove red"></span>';
        }
        $html .= '</div>';

        return $html;
    }

}
