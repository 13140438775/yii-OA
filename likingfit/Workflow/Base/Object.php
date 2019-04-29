<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/15
 * Time: 下午5:13
 */
namespace likingfit\Workflow\Base;
class Object{
    public $id;
    public $name = 'default name';
    public $displayName = 'default displayname';
    public $description = 'default desc';
    
    public function __get($property_name)
    {
        if(isset($this->$property_name))
        {
            return($this->$property_name);
        }
        else
        {
            return '';
        }
    }

    public function __set($property_name, $value)
    {
        $this->$property_name = $value;
    }
    
     public function getId(){
         return $this->id;
     }
    
     public function setId($id){
         $this->id = $id;
     }
    
     public function getName() {
         return $this->name;
     }
    
     public function setName($name = '') {
         $this->name = $name;
     }
    
     public function getDescription() {
         return $this->description;
     }
    
     public function setDescription($description) {
         $this->description = $description;
     }
    
     public function getDisplayName() {
         return $this->displayName;
     }
    
     public function setDisplayName($displayName) {
         $this->displayName = $displayName;
     }
    
}