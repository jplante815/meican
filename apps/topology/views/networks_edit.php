<?php $args = $this->passedArgs; ?>

<h1><?php echo _("Edit network"); ?></h1>

<form method="POST" onsubmit="validateNetworkForm();" action="<?php echo $this->buildLink(array("action" => "update", "param" => "net_id:".$args->network->net_id)); ?>">
    <div style="width: 38%">
        <?php $this->addElement('network_form', $args); ?>

        <div class="controls">
            <input class="save" type="submit" value="<?php echo _('Save'); ?>">
            <input class="cancel" type="button" value="<?php echo _('Cancel'); ?>" onclick="redir('<?php echo $this->buildLink(array('action' => 'show')); ?>');">
        </div>
    </div>
</form>