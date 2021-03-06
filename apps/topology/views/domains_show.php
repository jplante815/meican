<?php
$domains = $this->passedArgs;
?>

<h1><?php echo _("Domains"); ?></h1>

<form method="POST" action="<?php echo $this->buildLink(array('action' => 'delete')); ?>">
    <?php echo $this->element('controls', array('app' => 'init')); ?>
    <table class="list">

        <thead>
            <tr>
                <th></th>
                <th></th>
                <th><?php echo _("Name"); ?></th>
                <th><?php echo _("OSCARS URL"); ?></th>
                <th><?php echo _("OSCARS Version"); ?></th>
                <th><?php echo _("Topology ID"); ?></th>
                <th><?php echo _("ODE IP"); ?></th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($domains as $d): ?>
                <tr>
                    <td>
                        <input type="checkbox" name="del_checkbox[]" value="<?php echo $d->id; ?>"/>
                    </td>
                    <td>
                        <a href="<?php echo $this->buildLink(array('action' => 'edit', 'param' => "dom_id:$d->id")); ?>">
                            <img class="edit" src="<?php echo $this->url(''); ?>webroot/img/edit_1.png"/>
                        </a>
                    </td>                
                    <td>
                        <?php echo $d->descr; ?>
                    </td>
                    <td>
                        <?php echo $d->idc_url; ?>
                    </td>
                    <td>
                        <?php echo $d->dom_version; ?>
                    </td>
                    <td>
                        <?php echo $d->topology_id; ?>
                    </td>
                    <td>
                        <?php echo $d->ode_ip; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>

    </table>

</form>