<?php
namespace Entities;

use ReflectionClass;
use JsonSerializable;

class Entity implements JsonSerializable{
    public function __construct($array = null) {
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
        $attributes = $this->getPropertiesAttributes();

        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $property_type = isset($attributes[$key]['type'])? $attributes[$key]['type'] : null;

                if ($property_type == 'boolean') {
                    $this->{$key} = $value? true : false;
                } else {
                    $this->{$key} = $value;
                }
            }
        }
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