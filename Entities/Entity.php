<?php
namespace Entities;

use ReflectionClass;
use JsonSerializable;

class Entity implements JsonSerializable {
    private $attributes;
    public function __construct($array = null) {
        $this->attributes = $this->getPropertiesAttributes();

        if (is_array($array)) {
            $this->set($array, true);
        }
    }

    public function __get($property) {
        return get($property);
    }

    public function get($property) {
        if (property_exists($this, $property)) {
            return $this->{$property};
        }

        return null;
    }

    public function set($data) {
        foreach ($data as $property => $value) {
            if (!property_exists($this, $property)) { continue; }
            $property_type = isset($this->attributes[$property]['type'])? $this->attributes[$property]['type'] : null;

            if ($property_type == 'boolean') {
                $this->{$property} = $value? true : false;
            } else {
                $this->{$property} = $value;
            }
        }
    }

    public function render($property) {
        if (!property_exists($this, $property)) { return null; }

        //Use custom entity render if exist
        $custom_render_method = 'render'.ucfirst($property);
        if (method_exists($this, $custom_render_method)) {
            return $this->$custom_render_method();
        }

        //Default renderer
        $property_type = isset($this->attributes[$property]['type'])? $this->attributes[$property]['type'] : null;

        if ($property_type == 'boolean') {
            return ($this->{$property})? 'true' : 'false';
        } else if ($property_type == 'date') {
            if (strtotime($this->{$property})) {
                return date('Y-m-d H:i:s', strtotime($this->{$property}));
            }
            
        }

        return $this->{$property};
    }

    private function getPropertiesAttributes() {
        $attributes = array();

        $reflect = new ReflectionClass($this);
        $properties = $reflect->getProperties();
        foreach ($properties as $property) {
            $attribute = array();
            $attribute['name'] = $property->getName();

            $doc_comment = $property->getDocComment();
            preg_match_all('/@JsonDB\((\w+)="(\w+)"\)/', $doc_comment, $matches);
            if ($matches) {
                foreach ($matches[1] as $i => $match) {
                    $attribute[$matches[1][$i]] = $matches[2][$i];
                }
            }

            $attributes[$property->getName()] = $attribute;
        }

        return $attributes;
    }

    public function jsonSerialize () {
        return get_object_vars($this);
    }
}