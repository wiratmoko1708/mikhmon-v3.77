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

// Failover per-session config storage.
// Each session stores its own ISP ports and schedule settings.

$failoverfile = './include/failover-' . strtolower($session) . '.json';

function fo_read($file) {
  if (!file_exists($file)) {
    return array('ports' => array(), 'schedule' => array('interval' => 30, 'enabled' => 0, 'last_switch' => ''));
  }
  $raw = file_get_contents($file);
  $data = json_decode($raw, true);
  if (!is_array($data)) {
    $data = array('ports' => array(), 'schedule' => array('interval' => 30, 'enabled' => 0, 'last_switch' => ''));
  }
  if (!isset($data['ports']) || !is_array($data['ports'])) {
    $data['ports'] = array();
  }
  if (!isset($data['schedule']) || !is_array($data['schedule'])) {
    $data['schedule'] = array('interval' => 30, 'enabled' => 0, 'last_switch' => '');
  }
  return $data;
}

function fo_write($file, $data) {
  file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}