<?php
/*
 *  Copyright (C) 2018 Laksamadi Guko.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
session_start();
// hide all error
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
  exit;
}

include_once('./include/failover.php');

$data = fo_read($failoverfile);
$ports = isset($data['ports']) ? $data['ports'] : array();
$schedule = isset($data['schedule']) ? $data['schedule'] : array('interval' => 30, 'enabled' => 0);

// fetch interfaces from router
$getiface = $API->comm("/interface/print");
if (!is_array($getiface)) {
  $getiface = array();
}
usort($getiface, function ($a, $b) {
  return strcmp(isset($a['name']) ? $a['name'] : '', isset($b['name']) ? $b['name'] : '');
});

// Build a lookup of the *real* runtime state of each interface on the router.
// Automatic failover runs as a RouterOS script+scheduler and changes the actual
// interface state directly on the router, so the JSON "is_active" flag can go
// stale. The status badge must reflect the true on-router state instead.
$iface_state = [];
foreach ($getiface as $ifc) {
  $ifname = isset($ifc['name']) ? $ifc['name'] : '';
  if ($ifname == '') {
    continue;
  }
  $running  = (isset($ifc['running']) && $ifc['running'] == 'true');
  $disabled = (isset($ifc['disabled']) && $ifc['disabled'] == 'true');
  $iface_state[$ifname] = array('running' => $running, 'disabled' => $disabled);
}

/**
 * A port is considered ACTIVE (green) when it is enabled on the router.
 * Automatic failover switches ports by setting disabled=no / disabled=yes,
 * so we must use the administrative state rather than link carrier.
 */
function fo_port_is_active($ifname, $iface_state) {
  if (isset($iface_state[$ifname])) {
    return !$iface_state[$ifname]['disabled'];
  }
  // Interface not found on router anymore -> treat as inactive.
  return false;
}
?>
<div class="row">
<div class="col-12">
<div class="card">
<div class="card-header">
  <h3><i class="fa fa-random"></i> <?= $_failover ?>
    &nbsp;&nbsp; | &nbsp;&nbsp;<i onclick="location.reload" class="fa fa-refresh pointer" title="Reload data"></i>
  </h3>
</div>
<div class="card-body">

<div class="row">
  <div class="col-6">
    <div class="card">
      <div class="card-header"><h3 class="card-title"><i class="fa fa-server"></i> <?= $_isp_port ?></h3></div>
      <div class="card-body">
        <form class="form-inline mb-3" method="get" action="./">
          <input type="hidden" name="session" value="<?= $session; ?>">
          <input type="hidden" name="fo-add-port" value="1">
          <input type="text" class="form-control mr-1" name="fo-name" placeholder="<?= $_isp_name ?>" required>
          <select class="form-control mr-1" name="fo-interface" required>
            <option value="" disabled selected><?= $_select_interface ?>...</option>
            <?php foreach ($getiface as $i) { ?>
            <option value="<?= $i['name']; ?>"><?= $i['name']; ?><?php if (!empty($i['type'])) echo " (" . $i['type'] . ")"; ?></option>
            <?php } ?>
          </select>
          <button type="submit" class="btn btn-primary"><?= $_add ?></button>
        </form>

        <div class="overflow box-bordered" style="max-height: 50vh">
        <table class="table table-bordered table-hover text-nowrap">
          <thead>
          <tr>
            <th><?= $_name ?></th>
            <th><?= $_interface ?></th>
            <th><?= $_status ?></th>
            <th><?= $_action ?></th>
          </tr>
          </thead>
          <tbody>
          <?php if (count($ports) == 0) { ?>
            <tr><td colspan="4" class="text-center"><?= $_failover_empty ?></td></tr>
          <?php } else { foreach ($ports as $p) { ?>
            <?php $real_active = fo_port_is_active($p['interface'], $iface_state); ?>
            <tr>
              <td><?= htmlspecialchars($p['name']); ?></td>
              <td><?= htmlspecialchars($p['interface']); ?></td>
              <td>
                <?php if ($real_active) { ?>
                  <span class="badge bg-green"><?= $_active ?></span>
                <?php } else { ?>
                  <span class="badge bg-red"><?= $_inactive ?></span>
                <?php } ?>
              </td>
              <td>
                <?php if ($p['is_active'] == 1) { ?>
                  <span class="pointer" title="<?= $_disable_port ?>" onclick="loadpage('./?fo-toggle-port=<?= urlencode($p['interface']); ?>&session=<?= $session; ?>')"><i class="fa fa-lock text-warning"></i></span>
                <?php } else { ?>
                  <span class="pointer" title="<?= $_enable_port ?>" onclick="loadpage('./?fo-toggle-port=<?= urlencode($p['interface']); ?>&session=<?= $session; ?>')"><i class="fa fa-unlock"></i></span>
                <?php } ?>
                &nbsp;&nbsp;
                <span class="pointer text-danger" title="<?= $_remove ?>" onclick="if(confirm('<?= $_failover_remove_confirm ?>')){loadpage('./?fo-remove-port=<?= urlencode($p['interface']); ?>&session=<?= $session; ?>')}"><i class="fa fa-minus-square"></i></span>
              </td>
            </tr>
          <?php } } ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-6">
    <div class="card">
      <div class="card-header"><h3 class="card-title"><i class="fa fa-clock-o"></i> <?= $_failover_schedule ?></h3></div>
      <div class="card-body">
        <form method="get" action="./">
          <input type="hidden" name="session" value="<?= $session; ?>">
          <input type="hidden" name="fo-save-schedule" value="1">
          <div class="mb-3">
            <label class="form-label"><?= $_failover_interval ?> (<?= $_minutes ?>)</label>
            <input type="number" class="form-control" name="fo-interval" value="<?= (int)$schedule['interval']; ?>" min="1" required>
          </div>
          <div class="mb-3">
            <label class="form-label"><?= $_enabled ?></label>
            <select class="form-control" name="fo-enabled">
              <option value="1" <?= ($schedule['enabled'] == 1) ? 'selected' : ''; ?>><?= $_yes ?></option>
              <option value="0" <?= ($schedule['enabled'] != 1) ? 'selected' : ''; ?>><?= $_no ?></option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary"><?= $_save ?></button>
          <a href="./?fo-switch-now=1&session=<?= $session; ?>" class="btn btn-secondary"><?= $_failover_switch_now ?></a>
        </form>
        <p class="text-muted mt-3"><?= $_failover_help ?></p>
      </div>
    </div>
  </div>
</div>

</div>
</div>
</div>
</div>
