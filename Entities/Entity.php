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
        //Transform property_name to PropertyName
        $property_formated = implode('', array_map("ucfirst", explode('_', strtolower($property))));
        $custom_render_method = __FUNCTION__.ucfirst($property_formated);
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

    public function renderRowClass($property) {
        if (!property_exists($this, $property)) { return null; }

        //Use custom entity render if exist
        //Transform property_name to PropertyName
        $property_formated = implode('', array_map("ucfirst", explode('_', strtolower($property))));
        $custom_render_method = __FUNCTION__.ucfirst($property_formated);

        if (method_exists($this, $custom_render_method)) {
            return $this->$custom_render_method();
        }

        return null;
    }

    public static function getPropertiesAttributes() {
        $attributes = array();

        $reflect = new ReflectionClass(get_called_class());
        $properties = $reflect->getProperties();

        foreach ($properties as $property) {
            $attribute = array();
            $attribute['name'] = $property->getName();

            $doc_comment = $property->getDocComment();
            preg_match_all('/@JsonDB\\\([\w-]+)\(([\w-]+)?=?"([\w- ]+)"\)/', $doc_comment, $matches);
            if ($matches) {
                foreach ($matches[1] as $i => $match) {
                    $attribute[$matches[1][$i]] = $matches[3][$i];
                }
            }

            $attributes[$property->getName()] = $attribute;
        }

        return $attributes;
    }

    public function jsonSerialize () {
        $properties = $this->getPropertiesAttributes();
        $data       = array();

        foreach ($properties as $property) {
            #Do not store hidden attributes
            if (isset($property['type']) && $property['type'] == 'hidden') { continue; }
            $data[$property['name']] = $this->{$property['name']};
        }

        return $data;
    }
}