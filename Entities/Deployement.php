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

    public function renderProd () {
        return $this->renderBoolean($this->prod);
    }

    public function renderStaging () {
        return $this->renderBoolean($this->staging);
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
