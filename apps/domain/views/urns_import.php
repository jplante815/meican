<?php

$urns = $this->passedArgs->urns;
$networks = $this->passedArgs->networks;
$domain = $this->passedArgs->domain;

?>

<h1><?php echo _("Importing Topology URNs (Uniform Resource Name)"); ?></h1>

<h2><?php echo _("Domain")." $domain->descr"; ?></h2>

<table id="urn_table" class="list">

    <thead>
        <tr>
            <th rowspan="2"></th>
            <th rowspan="2"><?php echo _("Network"); ?></th>
            <th rowspan="2"><?php echo _("Device"); ?></th>
            <th rowspan="2"><?php echo _("Port"); ?></th>
            <th rowspan="2"><?php echo _("URN Value"); ?></th>
            <th colspan="4"><?php echo _("Link Settings"); ?></th>
        </tr>
        
        <tr>
            <th><?php echo _("VLAN Values"); ?></th>
            <th><?php echo _("Maximum Capacity"); ?></th>
            <th><?php echo _("Minimum Capacity"); ?></th>
            <th><?php echo _("Granularity"); ?></th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($urns as $u): ?>
            <tr id="line<?php echo $u->id; ?>">
                <td class="edit">
                    <img class="delete" src="layouts/img/delete.png" onclick="deleteURNLine('<?php echo $u->id; ?>');"/>
                </td>

                <td>
                    <select id="network<?php echo $u->id; ?>" onchange="changeNetworkURN('<?php echo $domain->id; ?>', this);" >
                        <option value="-1"/>
                        <?php foreach ($networks as $n): ?>
                            <option value="<?php echo $n->id; ?>"><?php echo $n->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>

                <td><select style="display:none" id="device<?php echo $u->id; ?>"/></td>

                <td><?php echo $u->port; ?></td>
                <td><?php echo $u->name; ?></td>
                <td><?php echo $u->vlan; ?></td>
                <td><?php echo $u->max_capacity; ?></td>
                <td><?php echo $u->min_capacity; ?></td>
                <td><?php echo $u->granularity; ?></td>

            </tr>
        <?php endforeach; ?>
    </tbody>

</table>

<div class="controls">
    <input class="save" id="save_button" type="button"  value="<?php echo _("Save"); ?>" onclick="saveURN();"/>
    <input class="cancel" id="cancel_button" type="button" value="<?php echo _("Cancel"); ?>" onClick="redir('<?php echo $this->buildLink(array('action' => 'show')); ?>');"/>
</div>