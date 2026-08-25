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
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
  exit;
}

include_once('./include/failover.php');

$addport = $_GET['fo-add-port'];
$toggleport = $_GET['fo-toggle-port'];
$removeport = $_GET['fo-remove-port'];
$saveschedule = $_GET['fo-save-schedule'];
$switchnow = $_GET['fo-switch-now'];

$data = fo_read($failoverfile);

function fo_interface_id($API, $name) {
  $res = $API->comm("/interface/print", array("?name" => $name));
  if (isset($res[0]['.id'])) {
    return $res[0]['.id'];
  }
  return false;
}

function fo_set_port_state($API, $name, $enabled) {
  $id = fo_interface_id($API, $name);
  if ($id === false) {
    return false;
  }
  $API->comm("/interface/set", array(
    ".id" => $id,
    "disabled" => $enabled ? "no" : "yes",
  ));
  return true;
}

function fo_interval_string($minutes) {
  $d = intval($minutes / 1440);
  $h = intval(($minutes % 1440) / 60);
  $m = intval($minutes % 60);
  $s = "";
  if ($d > 0) { $s .= $d . "d"; }
  if ($h > 0) { $s .= $h . "h"; }
  if ($m > 0) { $s .= $m . "m"; }
  return ($s == "") ? "1m" : $s;
}

function fo_build_rotation_script($data) {
  $ports = isset($data['ports']) ? array_values($data['ports']) : array();
  if (count($ports) < 2) {
    return '';
  }
  $n = count($ports);
  $parts = array();
  $parts[] = ':global foidx';
  $parts[] = ':if ([:len $foidx] = 0) do={ :set foidx 0 }';
  $idx = 0;
  foreach ($ports as $p) {
    $iface = addslashes($p['interface']);
    $parts[] = ':if ($foidx = ' . $idx . ') do={ /interface set [find name="' . $iface . '"] disabled=no } else={ /interface set [find name="' . $iface . '"] disabled=yes }';
    $idx++;
  }
  $parts[] = ':set foidx ($foidx + 1)';
  $parts[] = ':if ($foidx >= ' . $n . ') do={ :set foidx 0 }';
  return implode('; ', $parts);
}

if ($addport != "") {
  $pname = trim($_GET['fo-name']);
  $piface = trim($_GET['fo-interface']);
  if ($pname != "" && $piface != "") {
    $dup = false;
    foreach ($data['ports'] as $p) {
      if ($p['interface'] == $piface) {
        $dup = true;
        break;
      }
    }
    if (!$dup) {
      $data['ports'][] = array('name' => $pname, 'interface' => $piface, 'is_active' => 1);
      fo_set_port_state($API, $piface, true);
    }
  }
  fo_write($failoverfile, $data);
  echo "<script>window.location='./?failover=ports&session=" . $session . "'</script>";
} elseif ($toggleport != "") {
  foreach ($data['ports'] as $k => $p) {
    if ($p['interface'] == $toggleport) {
      $enable = ($p['is_active'] == 1) ? false : true;
      fo_set_port_state($API, $toggleport, $enable);
      $data['ports'][$k]['is_active'] = $enable ? 1 : 0;
      break;
    }
  }
  fo_write($failoverfile, $data);
  echo "<script>window.location='./?failover=ports&session=" . $session . "'</script>";
} elseif ($removeport != "") {
  foreach ($data['ports'] as $k => $p) {
    if ($p['interface'] == $removeport) {
      unset($data['ports'][$k]);
    }
  }
  $data['ports'] = array_values($data['ports']);
  fo_write($failoverfile, $data);
  echo "<script>window.location='./?failover=ports&session=" . $session . "'</script>";
} elseif ($saveschedule != "") {
  $interval = intval($_GET['fo-interval']);
  if ($interval <= 0) {
    $interval = 30;
  }
  $enabled = ($_GET['fo-enabled'] == "1") ? 1 : 0;
  $data['schedule']['interval'] = $interval;
  $data['schedule']['enabled'] = $enabled;
  $data['schedule']['last_switch'] = date("Y-m-d H:i:s");
  fo_write($failoverfile, $data);

  $scriptname = "MikhmonFailover-" . $session;
  $scriptsource = fo_build_rotation_script($data);

  $res = $API->comm("/system/script/print", array("?name" => $scriptname));
  if (count($res) > 0) {
    $sid = $res[0]['.id'];
    $API->comm("/system/script/set", array(
      ".id" => $sid,
      "source" => $scriptsource,
    ));
  } else {
    $API->comm("/system/script/add", array(
      "name" => $scriptname,
      "source" => $scriptsource,
    ));
  }

  $schres = $API->comm("/system/scheduler/print", array("?name" => $scriptname));
  if ($enabled == 1 && count($data['ports']) >= 2) {
    $intervalStr = fo_interval_string($interval);
    if (count($schres) > 0) {
      $scid = $schres[0]['.id'];
      $API->comm("/system/scheduler/set", array(
        ".id" => $scid,
        "interval" => $intervalStr,
        "on-event" => $scriptname,
        "disabled" => "no",
      ));
    } else {
      $API->comm("/system/scheduler/add", array(
        "name" => $scriptname,
        "interval" => $intervalStr,
        "on-event" => $scriptname,
      ));
    }
  } else {
    if (count($schres) > 0) {
      $scid = $schres[0]['.id'];
      $API->comm("/system/scheduler/set", array(
        ".id" => $scid,
        "disabled" => "yes",
      ));
    }
  }

  echo "<script>window.location='./?failover=ports&session=" . $session . "'</script>";
} elseif ($switchnow != "") {
  $ports = array_values($data['ports']);
  if (count($ports) >= 2) {
    $activeIdx = 0;
    foreach ($ports as $k => $p) {
      if ($p['is_active'] == 1) {
        $activeIdx = $k;
        break;
      }
    }
    $nextIdx = ($activeIdx + 1) % count($ports);
    foreach ($ports as $k => $p) {
      $enable = ($k == $nextIdx);
      fo_set_port_state($API, $p['interface'], $enable);
      $data['ports'][$k]['is_active'] = $enable ? 1 : 0;
    }
    $data['schedule']['last_switch'] = date("Y-m-d H:i:s");
  }
  fo_write($failoverfile, $data);
  echo "<script>window.location='./?failover=ports&session=" . $session . "'</script>";
} else {
  echo "<script>window.location='./?failover=ports&session=" . $session . "'</script>";
}