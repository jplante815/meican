<?php

defined('__FRAMEWORK') or die("Invalid access.");

include_once 'libs/controller.php';

include_once 'includes/common.inc';

include_once 'apps/circuits/controllers/flows.php';

include_once 'apps/circuits/models/reservation_info.inc';
include_once 'apps/circuits/models/gri_info.inc';
include_once 'apps/circuits/models/flow_info.inc';
include_once 'apps/circuits/models/timer_info.inc';
include_once 'apps/bpm/models/request_info.inc';
include_once 'apps/circuits/models/oscars.php';

include_once 'apps/domain/models/domain_info.inc';
include_once 'apps/domain/models/topology.inc';
include_once 'includes/nuSOAP/lib/nusoap.php';

class reservations extends Controller {

    public function reservations() {
        $this->app = 'circuits';
        $this->controller = 'reservations';
        $this->defaultAction = 'show';
    }

    public function show() {

        // inicializa variáveis da sessão do wizard
        Common::destroySessionVariable('res_name');
        Common::destroySessionVariable('sel_flow');
        Common::destroySessionVariable('sel_timer');
        Common::destroySessionVariable('res_wizard');

        $res_info = new reservation_info();
        $allReservations = $res_info->fetch();

        if ($allReservations) {
            $reservations = array();

            foreach ($allReservations as $r) {
                $res = new stdClass();
                $res->id = $r->res_id;
                $res->name = $r->res_name;

                $flow = new flow_info();
                $flow->flw_id = $r->flw_id;
                $result = $flow->fetch();
                $f = $result[0];
                $res->flow = $f->flw_name;

                $timer = new timer_info();
                $timer->tmr_id = $r->tmr_id;
                $result = $timer->fetch();
                $t = $result[0];
                $res->timer = $t->tmr_name;

                $reservations[] = $res;
            }
            $this->setAction('show');

            $this->setInlineScript('reservations_init');

            $this->setArgsToBody($reservations);
        } else {
            $this->setAction('empty');

            $args = new stdClass();
            $args->title = _("Reservations");
            $args->message = _("You have no reservation, click the button below to create a new one");
            $this->setArgsToBody($args);
        }

        $this->render();
    }

    public function refresh_status() {
        $this->setAction("ajax");
        $this->setLayout("empty");

        $res_number = Common::POST("count"); // quantidade de reservas listadas
        $res_id = Common::POST("res_id");

        $res_info = new reservation_info();
        $res_info->res_id = ($res_id) ? $res_id : "";
        $reservations = $res_info->fetch();

        // testa se a quantidade de reservas na página é diferente da quantidade de reservas no banco
        if (count($reservations) != $res_number) {
            header('HTTP/1.1 406 Refresh reservations');
            return;
        }

        $statusArray = array();

        $griList = array();
        $control = array(); // vetor bidimensional para fazer a relação de quais gris foram inseridos em griList
        // exemplo:
        // se o gri da posição reservations[0][2] estiver em griList, então control[0][2] será true
        // transforma a lista bidimensional de gris para uma lista unidimensional
        if ($reservations) {
            $ind = 0;

            foreach ($reservations as $res) {
                $statusArray[$ind] = array();
                $control[$ind] = array();

                $req = new request_info();
                $req->resource_id = $res->res_id;
                $req->resource_type = 'reservation_info';

                if ($result = $req->fetch()) {
                    switch ($result[0]->response) {
                        case 'accept': //FINALIZADA NO ODE E já foi enviada ao OSCARS
                            $gri = new gri_info();
                            $gri->res_id = $res->res_id;
                            $gris = $gri->fetch(FALSE);

                            $ind2 = 0;
                            if ($gris) {
                                foreach ($gris as $g) {
                                    switch ($g->status) {
                                        case "FINISHED":
                                        case "CANCELLED":
                                        case "FAILED":
                                            $control[$ind][$ind2] = FALSE;
                                            break;
                                        default:
                                            $griList[] = $g->gri_id;
                                            $control[$ind][$ind2] = TRUE;
                                    }
                                    $ind2++;
                                }
                            }

                            break;
                        case 'reject':
                            $control[$ind][0] = FALSE;
                            $statusArray[$ind][0] = 'REJECTED';
                            break;
                        default: //ESTÁ SENDO EXECUTADA NO ODE
                            $control[$ind][0] = FALSE;
                            $statusArray[$ind][0] = $result[0]->status;
                    }
                }

                $ind++;
            }
        } else {
            $this->setArgsToBody(_("Fail to get reservations"));
            $this->render();
            return;
        }

        $statusResult = array();
        if ($griList) {
            $endpoint = "http://" . Framework::$bridgeIp . "/axis2/services/BridgeOSCARS?wsdl";

            $client = new SoapClient($endpoint, array('cache_wsdl' => 0));
            if ($result = $client->list($griList)) {
                if (is_array($result->return)) {
                    $statusResult = $result->return;
                } else {
                    $statusResult[0] = $result->return;
                }
            } else {
                $this->setArgsToBody(_("Fail to get status"));
                $this->render();
                return;
            }
        }

        $cont = 0;

        $statusList = array();
        $now = time();

        $ind = 0;
        foreach ($reservations as $res) {

            $gri = new gri_info();
            $gri->res_id = $res->res_id;
            $gris = $gri->fetch(FALSE);

            if ($gris) {
                $ind2 = 0;
                $next_gri = $gris[0];

                foreach ($gris as $g) {
                    if ($control[$ind][$ind2]) {
                        $statusArray[$ind][$ind2] = $statusResult[$cont];
                        $cont++;

                        $statusArray[$ind][$ind2] = "PENDING";

                        // atualiza o banco de dados com o novo status (retornado do OSCARS)
                        $gri_tmp = new gri_info();
                        $gri_tmp->gri_id = $g->gri_id;
                        $gri_tmp->status = $statusArray[$ind][$ind2];
                        $gri_tmp->update();
                    } else {
                        $statusArray[$ind][$ind2] = $g->status;
                    }

                    Framework::debug("array", $statusArray);

                    if ($res_id) {
                        $stat = new stdClass();
                        //$statusArray[$ind][$ind2] = "PENDING";
                        $stat->name = $statusArray[$ind][$ind2];
                        $stat->translate = gri_info::translateStatus($statusArray[$ind][$ind2]);
                        Framework::debug("array", $statusArray);
                        $statusList[$ind2] = $stat;
                    } else {
                        $date = new DateTime($g->start);
                        $start = $date->getTimestamp();

                        $date = new DateTime($next_gri->start);
                        $next_start = $date->getTimestamp();

                        $new_diff = $start - $now;
                        $next_diff = $next_start - $now;

                        if (($new_diff > 0) && (($new_diff < $next_diff) || ($next_diff < 0))) {
                            $next_gri = $g;
                        }
                    }

                    $ind2++;
                }

                if ($res_id === FALSE) {
                    $status = $next_gri->status;
                    //$status = "PENDING";

                    $stat = new stdClass();
                    $stat->name = $status;
                    $stat->translate = gri_info::translateStatus($status);
                    $statusList[$ind] = $stat;
                }
            } else {
                if (!$control[$ind][0] && isset($statusArray[$ind][0])) {
                    $stat = new stdClass();
                    $status = $statusArray[$ind][0];
                    $stat->name = $status;
                    $stat->translate = gri_info::translateStatus($status);
                    $statusList[$ind] = $stat;
                }
            }

            $ind++;
        }
        //Framework::debug('status',$statusList);
        $this->setArgsToBody($statusList);
        $this->render();
    }

    public function add_form() {
        //$this->page1();
        $this->reservation_add();
    }

    public function reservation_add() {
        $this->setInlineScript('reservations_add_init');
        $this->addScript('reservations_add');
        $this->addScript('map');
        $this->setAction('add');

        $min = 100;
        $max = 1000;
        $div = 100;
        $warn = 0.7;

//        $domain = new domain_info();
//        $domains = $domain->fetch(FALSE);
//
//        $domToMapArray = array();
//        foreach ($domains as $d) {
//            $domain = new stdClass();
//            $domain->id = $d->dom_id;
//            $domain->name = $d->dom_descr;
//            $endpoint = "http://{$d->dom_ip}/" . Framework::$systemDirName . "/main.php?app=domain&services&wsdl";
//            if ($ws = new nusoap_client($endpoint, array('cache_wsdl' => 0))) {
//                if ($temp = $ws->call('getURNDetails', array())) {
//                    $domain->networks = $temp;
//                    $domToMapArray[] = $domain;
//                }
//            }
//        }

        //if ($domToMapArray) {
            $this->setArgsToScript(array(
                "band_min" => $min,
                "band_max" => $max,
                "band_div" => $div,
                "band_warning" => $warn,
                "flash_nameReq" => _("A name is required"),
                "flash_bandInv" => _("Invalid value for bandwidth"),
                "flash_sourceReq" => _("A source is required"),
                "flash_srcVlanInv" => _("Invalid value for source VLAN"),
                "flash_srcVlanReq" => _("Source VLAN type required"),
                "flash_destReq" => _("A destination is required"),
                "flash_dstVlanInv" => _("Invalid value for destination VLAN"),
                "flash_dstVlanReq" => _("Destination VLAN type required"),
                "flash_timerReq" => _("Timer is required"),
                "domain_string" => _("Domain"),
                "domains_string" => _("Domains"),
                "network_string" => _("Network"),
                "networks_string" => _("Networks"),
                "device_string" => _("Device"),
                "devices_string" => _("Devices"),
                "from_here_string" => _("From Here"),
                "to_here_string" => _("To Here"),
                "cluster_information_string" => _("Information about cluster"),
                "warning_string" => _("Authorization from Network Administrator will be required.")
            //    "domains" => $domToMapArray
            ));
        //}


        $this->render();
    }

    public function page1() {
        Common::setSessionVariable('res_wizard', TRUE);

        $reservationName = NULL;
        $selectedFlow = NULL;
        if (Common::hasSessionVariable('res_name'))
            $reservationName = Common::getSessionVariable('res_name');
        else {
            $reservationName = "Default reservation name";
            Common::setSessionVariable('res_name', $reservationName);
        }

        if (Common::hasSessionVariable('sel_flow')) {
            $selectedFlow = Common::getSessionVariable('sel_flow');
        } else {
            $selectedFlow = NULL;
        }

        $flow_info = new flow_info();
        $allFlows = $flow_info->fetch();

        if ($allFlows) {

            $domains = array();
            foreach ($allFlows as $f) {
                if (array_search($f->src_dom, $domains) === FALSE)
                    $domains[] = $f->src_dom;
                if (array_search($f->dst_dom, $domains) === FALSE)
                    $domains[] = $f->dst_dom;
            }

            $urn_string_array = array();

            foreach ($domains as $d) {
                $ind = 0;
                $urn_string_array[$d] = array();
                foreach ($allFlows as $f) {
                    $urn_string_array[$d][$ind] = ($d == $f->src_dom) ? $f->src_urn_string : NULL;
                    $ind++;
                    $urn_string_array[$d][$ind] = ($d == $f->dst_dom) ? $f->dst_urn_string : NULL;
                    $ind++;
                }
            }

            $urnData = array();
            foreach ($urn_string_array as $dom_id => $urn_array) {
                $domain = new domain_info();
                $domain->dom_id = $dom_id;
                $dom = $domain->fetch(FALSE);
                $endpoint = "http://{$dom[0]->dom_ip}/" . Framework::$systemDirName . "/main.php?app=domain&services&wsdl";

                $ws = new nusoap_client($endpoint, array('cache_wsdl' => 0));
                $urnData[] = $ws->call('getURNsInfo', array('urn_string_list' => $urn_array));
            }

            $urnInfoMerge = array();
            foreach ($urnData as $uD) {
                foreach ($uD as $ind => $urn_str) {
                    if ($urn_str)
                        $urnInfoMerge[$ind] = $urn_str;
                    elseif (!isset($urnInfoMerge[$ind]))
                        $urnInfoMerge[$ind] = NULL;
                }
            }

            $ind = 0;
            $flows = array();

            foreach ($allFlows as $f) {
                $flow = new stdClass();
                $flow->id = $f->flw_id;
                $flow->name = $f->flw_name;
                $flow->bandwidth = $f->bandwidth;

                $domain = new domain_info();
                $domain->dom_id = $f->src_dom;
                $res_dom = $domain->fetch(FALSE);

                $flow->source->domain = $res_dom[0]->dom_descr;
                $flow->source->vlan = $f->src_vlan;

                if ($urnInfoMerge[$ind]) {
                    $flow->source->network = $urnInfoMerge[$ind]['net_descr'];
                    $flow->source->device = $urnInfoMerge[$ind]['dev_descr'];
                    $flow->source->port = $urnInfoMerge[$ind]['port_number'];
                }

                $ind++;

                $domain = new domain_info();
                $domain->dom_id = $f->dst_dom;
                $res_dom = $domain->fetch(FALSE);

                $flow->dest->domain = $res_dom[0]->dom_descr;
                $flow->dest->vlan = $f->dst_vlan;

                if ($urnInfoMerge[$ind]) {
                    $flow->dest->network = $urnInfoMerge[$ind]['net_descr'];
                    $flow->dest->device = $urnInfoMerge[$ind]['dev_descr'];
                    $flow->dest->port = $urnInfoMerge[$ind]['port_number'];
                }

                $ind++;

                $flow->editable = TRUE;
                $flow->deletable = FALSE;
                $flow->selectable = TRUE;

                $flows[] = $flow;
            }
            $args = new stdClass();

            $args->res_name = $reservationName;
            $args->sel_flow = $selectedFlow;
            $args->flows = $flows;

            $this->setArgsToBody($args);
        } else {
            $args = new stdClass();

            $args->res_name = $reservationName;
            $args->sel_flow = $selectedFlow;

            $args->link = array('app' => 'circuits', 'controller' => 'flows', 'action' => 'add_options');
            $args->message = _("You have no flow, click the button below to create a new one");

            $this->setArgsToBody($args);
        }


        $this->setAction('page1');
        $this->render();
    }

    public function page2() {
        $selectedTimer = NULL;
        if (Common::hasSessionVariable('res_wizard') && Common::hasSessionVariable('sel_flow') && Common::hasSessionVariable('res_name')) {
            $selectedFlow = Common::getSessionVariable('sel_flow');
            $reservationName = Common::getSessionVariable('res_name');
        } else {
            $this->setFlash(_("Not enough arguments to reservation, going back to step 1 and 2..."), "warning");
            $this->page1();
            return;
        }

        if (Common::hasSessionVariable('sel_timer')) {
            $selectedTimer = Common::getSessionVariable('sel_timer');
        } else {
            $selectedTimer = NULL;
        }

        $timer_info = new timer_info();
        $allTimers = $timer_info->fetch();

        if ($allTimers) {
            $timers = array();

            foreach ($allTimers as $t) {
                $tim = new timer_info();
                $tim->tmr_id = $t->tmr_id;
                $timer = $tim->getTimerDetails();

                $timer->editable = TRUE;
                $timer->deletable = FALSE;
                $timer->selectable = TRUE;

                $timers[] = $timer;
            }

            $args = new stdClass();

            $args->sel_timer = $selectedTimer;
            $args->timers = $timers;

            $this->setArgsToBody($args);
        } else {
            $args = new stdClass();

            $args->sel_timer = $selectedTimer;

            $args->link = array('app' => 'circuits', 'controller' => 'timers', 'action' => 'add_form');
            $args->message = _("You have no timer, click the button below to create a new one");

            $this->setArgsToBody($args);
        }


        $this->setAction('page2');
        $this->render();
    }

    public function page3() {
        $reservationName = NULL;
        $selectedFlow = NULL;
        $selectedTimer = NULL;
        if (Common::hasSessionVariable('res_wizard') && Common::hasSessionVariable('sel_timer') && Common::hasSessionVariable('sel_flow') && Common::hasSessionVariable('res_name')) {
            $selectedTimer = Common::getSessionVariable('sel_timer');
            $selectedFlow = Common::getSessionVariable('sel_flow');
            $reservationName = Common::getSessionVariable('res_name');
        } else {
            $this->setFlash(_("Not enough arguments to reservation, going back to step 3..."), "warning");
            $this->page2();
            return;
        }

        $flow_info = new flow_info();
        $flow_info->flw_id = $selectedFlow;
        $flow = $flow_info->getFlowDetails();

        if (!$flow) {
            $this->setFlash(_("Flow not found or could not get endpoints information"), "fatal");
            $this->page1();
            return;
        }

        $timer_info = new timer_info();
        $timer_info->tmr_id = $selectedTimer;
        $timer = $timer_info->getTimerDetails();

        if (!$timer) {
            $this->setFlash(_("Timer not found"), "fatal");
            $this->page2();
            return;
        }


        $args = new stdClass();
        $args->flow = $flow;
        $args->timer = $timer;
        $args->res_name = $reservationName;

        $this->setAction('page3');
        $this->setArgsToBody($args);
        $this->render();
    }

    public function submit() {
        $selectedTimer = NULL;
        $selectedFlow = NULL;
        $reservationName = NULL;
        if (Common::hasSessionVariable('res_wizard') && Common::hasSessionVariable('sel_timer') && Common::hasSessionVariable('sel_flow') && Common::hasSessionVariable('res_name')) {
            $selectedTimer = Common::getSessionVariable('sel_timer');
            $selectedFlow = Common::getSessionVariable('sel_flow');
            $reservationName = Common::getSessionVariable('res_name');
        } else {
            $this->setFlash(_("Not enough arguments to reservation, going back to step 4..."), "warning");
            //$this->page3();
            $this->add_form();
            return;
        }

        $reservation = new reservation_info();
        $reservation->res_name = $reservationName;
        $reservation->flw_id = $selectedFlow;
        $reservation->tmr_id = $selectedTimer;

        if ($res = $reservation->insert()) {
            $reservation->res_id = $res->res_id;
            if ($reservation->sendForAuthorization()) {
                $this->setFlash(_('Reservation submitted'), 'success');
            }

            $this->view(array("res_id" => $res->res_id));
        } else {
            $this->setFlash(_('Fail to save reservation on database'), 'error');
            $this->page3();
        }
    }

    public function view($res_id_array) {
        $resId = NULL;
        if (array_key_exists('res_id', $res_id_array)) {
            $resId = $res_id_array['res_id'];
        } else {
            $this->setFlash(_("Invalid index"), "fatal");
            $this->show();
            return;
        }

        $res_info = new reservation_info();
        $res_info->res_id = $resId;
        $result = $res_info->fetch();

        if ($result === FALSE) {
            $this->setFlash(_("Reservation not found"), "fatal");
            $this->show();
            return;
        } else {
            $reservation = $result[0];
        }

        $flow_info = new flow_info();
        $flow_info->flw_id = $reservation->flw_id;
        $flow = $flow_info->getFlowDetails();

        if (!$flow) {
            $this->setFlash(_("Flow not found or could not get endpoints information"), "fatal");
            $this->show();
            return;
        }

        $timer_info = new timer_info();
        $timer_info->tmr_id = $reservation->tmr_id;
        $timer = $timer_info->getTimerDetails();

        if (!$timer) {
            $this->setFlash(_("Timer not found"), "fatal");
            $this->show();
            return;
        }


        $req = new request_info();
        $req->resource_id = $reservation->res_id;
        $req->resource_type = 'reservation_info';
        $req->answerable = 'no';
        $result = $req->fetch();

        $status = array();
        $gris = array();

        if ($result[0]->response == 'accept') {
            $request->response = 'accept';
            $request->message = $result[0]->message;

            $gri = new gri_info();
            $gri->res_id = $reservation->res_id;
            $allGris = $gri->fetch(FALSE);

            $dateFormat = "d/m/Y";
            //$dateFormat = "M j, Y";

            $hourFormat = "H:i";
            //$hourFormat = "g:i a";

            if ($allGris) {
                foreach ($allGris as $g) {
                    $gri = new stdClass();
                    $gri->id = $g->gri_id;
                    $gri->status = gri_info::translateStatus($g->status);

                    $status[] = $g->status;

                    $start = new DateTime($g->start);
                    $finish = new DateTime($g->finish);

                    $gri->start = $start->format("$dateFormat $hourFormat");
                    $gri->finish = $finish->format("$dateFormat $hourFormat");

                    $gris[] = $gri;
                }
            }
        } elseif ($result[0]->response == 'reject') {
            $request->response = 'reject';
            $request->message = $result[0]->message;
        }
        $request->status = $result[0]->status;

        $this->setArgsToScript(array(
            "reservation_id" => $reservation->res_id,
            "status_array" => $status,
            "src_lat_network" => $flow->source->latitude,
            "src_lng_network" => $flow->source->longitude,
            "dst_lat_network" => $flow->dest->latitude,
            "dst_lng_network" => $flow->dest->longitude,
            "domain_string" => _("Domain"),
            "domains_string" => _("Domains"),
            "network_string" => _("Network"),
            "networks_string" => _("Networks"),
            "device_string" => _("Device"),
            "devices_string" => _("Devices"),
            "from_here_string" => _("From Here"),
            "to_here_string" => _("To Here"),
            "cluster_information_string" => _("Information about cluster")
        ));

        $this->setInlineScript('reservations_view');
        $this->addScript('reservation_map');

        $args = new stdClass();
        $args->gris = $gris;
        $args->flow = $flow;
        $args->timer = $timer;
        $args->res_name = $reservation->res_name;
        $args->res_id = $reservation->res_id;
        $args->request = $request;

        $this->setAction('view');
        $this->setArgsToBody($args);
        $this->render();
    }

    public function update_name() {
        Common::setSessionVariable('res_name', Common::POST('name'));
    }

    public function update_flow($flw_id = NULL) {
        if ($flw_id)
            Common::setSessionVariable('sel_flow', $flw_id);
        else
            Common::setSessionVariable('sel_flow', Common::POST('flow'));
    }

    public function update_timer($tmr_id = NULL) {
        if ($tmr_id)
            Common::setSessionVariable('sel_timer', $tmr_id);
        else
            Common::setSessionVariable('sel_timer', Common::POST('timer'));
    }

    public function cancel($res_id_array) {
        $cancel_reservations = Common::POST('cancel_checkbox');

        if ($cancel_reservations) {

            $cont = 0;

            $endpoint = "http://" . Framework::$bridgeIp . "/axis2/services/BridgeOSCARS?wsdl";
            $client = new SoapClient($endpoint, array('cache_wsdl' => 0));
            if ($result = $client->cancel($cancel_reservations)) {
                foreach ($result->return as $val) {
                    if ($val == 0)
                        $cont++;
                }
            }

            sleep(3);

            switch ($cont) {
                case 0:
                    $this->setFlash(_("No reservation was cancelled"), "warning");
                    break;
                case 1:
                    $this->setFlash(_("One reservation was cancelled"), "success");
                    break;
                default:
                    $this->setFlash("$cont " . _("reservations were cancelled"), "success");
                    break;
            }
        }

        $this->view($res_id_array);
    }

    public function delete() {
        $del_reservations = Common::POST("del_checkbox");

        $endpoint = "http://" . Framework::$bridgeIp . "/axis2/services/BridgeOSCARS?wsdl";
        $client = new SoapClient($endpoint, array('cache_wsdl' => 0));

        if ($del_reservations) {
            foreach ($del_reservations as $resId) {
                $gris_to_cancel = array();

                $reservation = new reservation_info();
                $reservation->res_id = $resId;

                $gri = new gri_info();
                $gri->res_id = $resId;
                if ($gris = $gri->fetch(FALSE)) {
                    foreach ($gris as $g) {
                        $gris_to_cancel[] = $g->gri_id;
                    }
                }

                if ($tmp = $reservation->fetch()) {
                    $result = $tmp[0];

                    if ($client->cancel($gris_to_cancel)) {
                        if ($reservation->delete())
                            $this->setFlash(_("Reservation") . " '$result->res_name' " . _("deleted"), 'success');
                    } else
                        $this->setFlash(_("Reservation") . " '$result->res_name' " . _("could not be cancelled"), 'error');
                }
            }
        }

        $this->show();
    }

}

?>
