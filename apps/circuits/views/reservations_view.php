<h1>
    <label for="res_name"><?= _("Reservation name") . ": "; ?></label>
    <div class="reservation-name"><?=$res_name . " (" . $usr_login . ")"?></div>
</h1>
<div id="tabs-res" class="reservation-tabs" style="position:relative;">
    <div class="tab-overlay fade-overlay"> </div>
    <div id="tabs-1">
        <div id="subtab-map" class="tab_subcontent shadow-box">
            <div id="res_mapCanvas" style="width:950px; height:400px;"></div>    
        </div>
        <div id="subtab-points" class="tab_subcontent" style="float: right; padding-left:2px;">
            <?= $this->element('view_point', array('type' => 'source', 'flow' => $flow)); ?>
            <div id="bandwidth_bar">
                <div id="bandwidth_bar_text">
                    <div style="text-align:center;">
                        <input type="text" id="lb_bandwidth" value="<?php echo $bandwidth . " " . _("Mbps") ?>" disabled="disabled" class="ui-widget ui-spinner-input"/>
                    </div>
                </div>
                <div id="bandwidth_bar_inside" style="width: <?= round($bandwidth * 100 / 1000); ?>%"></div>
            </div>
            <?= $this->element('view_point', array('type' => 'destination', 'flow' => $flow)); ?>
        </div>
        <div style="clear:both;"></div>
    </div>
    <div id="tabs-2" class="tab_content">
        <div class="float-left">
        <?= $this->element('view_timer', compact('timer')); ?>
        <?php //$request?$this->element('view_request', compact('request')):null; ?>
        </div>
        <div class="float-right">
        <?php if ($gris): ?>

            <form method="POST" action="<?php echo $this->buildLink(array('action' => 'cancel', 'param' => "res_id:$res_id,refresh:1")); ?>">    
                <?php if ($refresh): ?>
                    <div class="controls">
                        <input type="button" class="refresh" value="<?php echo _("Refresh") ?>" onclick="griRefreshStatus(<?php echo $res_id; ?>);" />
                        <input type="submit" class="cancel" disabled="disabled" id="cancel_button" value="<?php echo _("Cancel reservations"); ?>" onclick="return confirm('<?php echo _('Cancel the selected reservations?'); ?>')"/>
                    </div>
                <?php endif; ?>
                <?= $this->element('list_gris', compact('gris', 'refresh')); ?>
            </form>
        <?php endif; ?>
        </div>
        <div style="clear:both;"></div>
    </div>
</div>
