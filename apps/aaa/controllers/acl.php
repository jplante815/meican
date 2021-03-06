<?php

defined ('__MEICAN') or die ("Invalid access.");

include_once 'libs/meican_controller.php';
include_once 'libs/auth.php';

include_once 'apps/aaa/models/group_info.php';
include_once 'apps/aaa/models/user_info.php';
include_once 'apps/aaa/models/aros_acos.php';
include_once 'apps/aaa/models/aros.php';
include_once 'apps/aaa/models/acos.php';

include_once 'apps/bpm/models/request_info.php';

include_once 'apps/circuits/models/flow_info.php';
include_once 'apps/circuits/models/reservation_info.php';
include_once 'apps/circuits/models/timer_info.php';

include_once 'apps/topology/models/device_info.php';
include_once 'apps/topology/models/domain_info.php';
include_once 'apps/topology/models/network_info.php';
include_once 'apps/topology/models/urn_info.php';


class acl extends MeicanController {

    public $modelClass = 'aros_acos';

    public function acl() {
        $this->app = 'aaa';
        $this->controller = 'acl';
        $this->defaultAction = 'show';
        $this->addScriptForLayout(array('acl'));
    }

    protected function renderEmpty(){
        $this->set(array(
            'title' => _("Access Control List"),
            'message' => _("You can't see any access control, click the button below to add one"),
            'link' => false
            ));
        parent::renderEmpty();
    }

    public function show() {
        if ($allRights = $this->makeIndex(array('useACL' => false))) {
            $rights = array();
            
            foreach ($allRights as $r) {
                $right = new stdClass();
            
                $right->id = $r->perm_id;
                
                $right->create = (empty($r->create)) ? "-" : (($r->create == "allow") ? _("Allow") : _("Deny"));
                $right->read = (empty($r->read)) ? "-" : (($r->read == "allow") ? _("Allow") : _("Deny"));
                $right->update = (empty($r->update)) ? "-" : (($r->update == "allow") ? _("Allow") : _("Deny"));
                $right->delete = (empty($r->delete)) ? "-" : (($r->delete == "allow") ? _("Allow") : _("Deny"));

                $grp_manager = new Aros();
                $grp_manager->aro_id = $r->aro_id;
                $result_mger = $grp_manager->fetch(FALSE);
                
                $aro = $result_mger[0];
                $canAccessARO = FALSE;
                
                $aro_obj_descr = "-";
                if (empty($aro->obj_id)) {
                    $aro_obj_descr = "void";
                    $canAccessARO = TRUE;
                } else {
                    $model = new $aro->model;
                    if (is_a($model, "Model")) {
                        $pk = $model->getPrimaryKey();
                        $model->$pk = $aro->obj_id;
                        if ($displayName = $model->fetchList()) {
                            $aro_obj_descr = $displayName;
                            $canAccessARO = TRUE;
                        }
                    }
                }
                
                $right->aro_obj_id = $aro->obj_id;
                $right->aro_obj = $aro_obj_descr;
                $right->aro_model = $aro->model;
                
                $grp_managed = new Acos();
                $grp_managed->aco_id = $r->aco_id;
                $result_mged = $grp_managed->fetch(FALSE);
                
                $aco = $result_mged[0];
                $canAccessACO = FALSE;
                
                $aco_obj_descr = "-";
                $aco_obj_id = $aco->obj_id;
                if (empty($aco->obj_id)) {
                    $aco_obj_descr = "void";
                    $aco_obj_id = "NULL";
                    $canAccessACO = TRUE;
                } else {
                    $model = new $aco->model;
                    if (is_a($model, "Model")) {
                        $pk = $model->getPrimaryKey();
                        $model->$pk = $aco->obj_id;
                        if ($displayName = $model->fetchList()) {
                            $aco_obj_descr = $displayName;
                            $canAccessACO = TRUE;
                        }
                    }
                }
                
                $right->aco_model = $aco->model;
                $right->aco_obj = $aco_obj_descr;
                $right->aco_obj_id = $aco_obj_id;

                $right->editable = ($result_mger && $result_mged) ? TRUE : FALSE;
                //$right->editable=TRUE;
                $right->model = ($r->model) ? $r->model : _("All");
                
                if ($canAccessARO && $canAccessACO)
                    $rights[] = $right;
            }
            
            $this->setArgsToBody($rights);
            
            $aros = get_tree_models(new Aros());
            $acos = get_tree_models(new Acos());
            
            $this->setArgsToScript(array(
                "allow_desc_string" => _("Allow"),
                "deny_desc_string" => _("Deny"),
                "str_delete_acl" => _("Delete ACL?"),
                "str_acl_deleted" => _("Access control deleted"),
                "str_acl_not_deleted" => _("Fail to delete access control"),
                "str_all" => _("All"),
                "confirmMessage" => _("Save modifications?"),
                "fillMessage" => _("Please fill in all the fields"),
                "str_error_match_objs" => _("Tree objects do not match"),
                "str_error_fetch_objs" => _("Error to fetch objects"),
                "aros" => $aros,
                "acos" => $acos,
            ));
            
            /** 
             * @todo : verificar essa função
             */
            $this->setInlineScript('acl_init');
        }
        $this->render('show');
    }
    
    public function get_aros_acos() {
        $aros = get_tree_models(new Aros());
        $acos = get_tree_models(new Acos());
        
        if ($aros && $acos) {
            $args = new stdClass();
            $args->aros = $aros;
            $args->acos = $acos;
        } else
            $args = FALSE;
        $this->renderJson($args);
    }

    public function singleDelete() {
        if ($perm_id = Common::POST('perm_id')) {
            $acc = new aros_acos();
            $acc->perm_id = $perm_id;
            $result = $acc->delete();
            $this->setArgsToBody($result);
        } else
            $result = FALSE;
        $this->renderJson($result);
    }
    
    public function delete() {
        $del_accs = Common::POST("del_checkbox");

        if ($del_accs) {
            $count = 0;
            foreach ($del_accs as $perm_id) {
                $acc = new aros_acos();
                $acc->perm_id = $perm_id;
                if ($acc->delete())
                    $count++;
            }
            switch ($count) {
                case 0:
                    $this->setFlash(_("No access control was deleted"), "warning");
                    break;
                case 1:
                    $this->setFlash(_("One access control was deleted"), "success");
                    break;
                default:
                    $this->setFlash("$count " . _("access controls were deleted"), "success");
                    break;
            }
        }

        $this->show();
    }

    public function update() {
        $updated = NULL;
        $added = NULL;

        $updated = $this->modify(Common::POST("acl_editArray"));
        $added = $this->add(Common::POST("acl_newArray"));
        
        if ($updated || $added)
            $this->setFlash(_("ACL updated"), 'success');

        $this->show();
    }

    private function add($accessData) {
        $cont = 0;
        
        if ($accessData) {
            foreach ($accessData as $acl) {

                $aro = new Aros();
                $aro->model = $acl[0];
                $aro->obj_id = $acl[1];
                $aros = $aro->fetch(FALSE);

                foreach ($aros as $a) {
                    $aco = new Acos();
                    $aco->model = $acl[2];
                    $aco->obj_id = ($acl[3] === "NULL") ? NULL : $acl[3];
                    $acos = $aco->fetch(FALSE);

                    $aros_acos = new aros_acos();
                    $aros_acos->aro_id = $a->aro_id;

                    $aros_acos->aco_id = $acos[0]->aco_id;

                    $aros_acos->model = ($acl[4] == "all") ? NULL : $acl[4];

                    $aros_acos->create = ($acl[5] == -1) ? NULL : $acl[5];
                    $aros_acos->read = ($acl[6] == -1) ? NULL : $acl[6];
                    $aros_acos->update = ($acl[7] == -1) ? NULL : $acl[7];
                    $aros_acos->delete = ($acl[8] == -1) ? NULL : $acl[8];

                    if ($aros_acos->insert())
                        $cont++;
                }
            }
        }
        
        return $cont;
    }

    private function modify($accessData) {
        $cont = 0;

        if ($accessData) {
            foreach ($accessData as $acl) {

                $aro = new Aros();
                $aro->model = $acl[1];
                $aro->obj_id = $acl[2];
                $aros = $aro->fetch(FALSE);

                foreach ($aros as $a) {
                    $aco = new Acos();
                    $aco->model = $acl[3];
                    $aco->obj_id = ($acl[4] === "NULL") ? NULL : $acl[4];
                    $acos = $aco->fetch(FALSE);

                    $aros_acos = new aros_acos();
                    $aros_acos->perm_id = $acl[0];
                    $aros_acos->aro_id = $a->aro_id;

                    $aros_acos->aco_id = $acos[0]->aco_id;

                    $aros_acos->model = ($acl[5] == "all") ? NULL : $acl[5];

                    $aros_acos->create = ($acl[6] == -1) ? NULL : $acl[6];
                    $aros_acos->read = ($acl[7] == -1) ? NULL : $acl[7];
                    $aros_acos->update = ($acl[8] == -1) ? NULL : $acl[8];
                    $aros_acos->delete = ($acl[9] == -1) ? NULL : $acl[9];

                    if ($aros_acos->update())
                        $cont++;
                }
            }
        }
        
        return $cont;
    }

}

function get_tree_models($tree_object) {
    if (!is_a($tree_object, "TreeModel")) {
        return FALSE;
    }

    $models = array();

    if ($nodes = $tree_object->fetch(FALSE)) {
        foreach ($nodes as $node) {

            $model = new stdClass();
            $model->id = $node->model;
            $model->name = $node->model;
            $model->objs = array();
            
            $obj = new stdClass();
            $canAccessObj = FALSE;

            if (empty($node->obj_id)) {
                $obj->id = "NULL";
                $obj->name = "void";
                $model->objs[] = $obj;
                $canAccessObj = TRUE;
            } else {
                $obj_model = new $node->model;
                if (is_a($obj_model, "Model")) {
                    $pk = $obj_model->getPrimaryKey();
                    $obj_model->$pk = $node->obj_id;
                    if ($displayName = $obj_model->fetchList()) {
                        $obj->id = $node->obj_id;
                        $obj->name = $displayName;
                        $model->objs[] = $obj;
                        $canAccessObj = TRUE;
                    }
                }
            }

            $modelInArray = FALSE;

            foreach ($models as $model_index => $m) {
                if ($m->id == $model->id) {
                    $modelInArray = TRUE;
                    $objInModel = FALSE;
                    foreach ($m->objs as $o) {
                        if ($o->id == $obj->id) {
                            $objInModel = TRUE;
                        }

                        break;
                    }

                    if (!$objInModel && $canAccessObj)
                        array_push($models[$model_index]->objs, $obj);

                    break;
                }
            }

            if (!$modelInArray) {
                array_push($models, $model);
            }
        }
    }

    return $models;
}

?>