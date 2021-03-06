<?php

include_once 'libs/Model/tree_model.php';

class Acos extends TreeModel {

     function Acos($obj_id="", $model="",$parent_id="") {
        $this->setTableName("acos");
        $this->addAttribute("aco_id","INTEGER",true,false,false);
        $this->addAttribute("obj_id","INTEGER");
        $this->addAttribute("model","VARCHAR");
        $this->addAttribute("lft","INTEGER");
        $this->addAttribute("rgt","INTEGER");
        $this->addAttribute("parent_id","INTEGER");

        $this->obj_id = $obj_id;
        $this->model = $model;
        $this->parent_id = $parent_id;
    }

    public function getAcoGroupByModel($strAcos, $model) {
        $sql = "SELECT DISTINCT obj_id FROM acos WHERE aco_id IN ($strAcos) AND model='$model' AND obj_id IS NOT NULL";
        $result = $this->querySql($sql, $this->getTableName());
        return $result;
    }
    
    public function hasAnyAcoOfModel($strAcos, $model) {
        $sql = "SELECT DISTINCT aco_id FROM acos WHERE aco_id IN ($strAcos) AND model='$model'";
        if ($this->querySql($sql, $this->getTableName()))
            return TRUE;
        else
            return FALSE;
    }

    public function getVoid($strAcos, $model) {
        $sql = "SELECT DISTINCT * FROM acos WHERE aco_id IN ($strAcos) AND model='$model' AND obj_id IS NULL";
        $result = $this->querySql($sql, $this->getTableName());
        return $result;
    }
    
}

?>
