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

// hide all error
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  include('./include/users.php');

  $adduser = $_GET['add-user'];
  $edituser = $_GET['edit-user'];
  $removeuser = $_GET['remove-user'];

  // add user
  if (isset($_POST['add'])) {
    $nu = strtolower(trim($_POST['nu']));
    $np = $_POST['np'];
    $nc = $_POST['nc'];
    if ($nu != "" && $np != "" && $np == $nc) {
      if (!isset($users[$nu])) {
        $users[$nu] = array('user' => $nu, 'pass' => encrypt($np));
        save_users($users);
        echo "<script>window.location='./admin.php?id=users'</script>";
      } else {
        $error = $_users_exists;
      }
    } elseif ($np != $nc) {
      $error = $_users_not_match;
    } else {
      $error = $_users_empty;
    }
  }

  // edit user
  if (isset($_POST['edit'])) {
    $eu = strtolower(trim($_POST['eu']));
    $np = $_POST['np'];
    $nc = $_POST['nc'];
    if ($eu != "" && $np == $nc && $np != "") {
      $users[$eu] = array('user' => $eu, 'pass' => encrypt($np));
      save_users($users);
      echo "<script>window.location='./admin.php?id=users'</script>";
    } elseif ($np != $nc) {
      $error = $_users_not_match;
    }
  }

  // remove user
  if ($removeuser != "") {
    if (count($users) > 1 && $removeuser != $_SESSION["mikhmon"]) {
      unset($users[$removeuser]);
      save_users($users);
    }
    echo "<script>window.location='./admin.php?id=users'</script>";
  }
}
?>
<script>
  function PassU(id){
    var x = document.getElementById(id);
    if (x.type === 'password') {
      x.type = 'text';
    } else {
      x.type = 'password';
    }
  }
</script>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><i class="fa fa-users"></i> <?= $_admin_users ?> &nbsp; | &nbsp;&nbsp;<i onclick="location.reload;" class="fa fa-refresh pointer " title="Reload data"></i></h3>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-7">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title"><i class="fa fa-list"></i> <?= $_user_list ?></h3>
              </div>
              <div class="card-body">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th><?= $_no ?></th>
                      <th><?= $_user_name ?></th>
                      <th class="text-right"><?= $_action ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $n = 1;
                    foreach ($users as $u) { ?>
                      <tr>
                        <td><?= $n; ?></td>
                        <td><i class="fa fa-user"></i> <?= $u['user']; ?><?php if ($u['user'] == $_SESSION["mikhmon"]) { echo ' <i class="fa fa-star text-yellow" title="Current session"></i>'; } ?></td>
                        <td class="text-right">
                          <a href="./admin.php?id=users&edit-user=<?= $u['user']; ?>" class="btn btn-xs bg-blue"><i class="fa fa-edit"></i> <?= $_edit ?></a>
                          <?php if (count($users) > 1 && $u['user'] != $_SESSION["mikhmon"]) { ?>
                          <a href="javascript:void(0)" onclick="if(confirm('<?= $_users_del_confirm ?> <?= $u['user']; ?>?')){loadpage('./admin.php?id=users&remove-user=<?= $u['user']; ?>')}else{}" class="btn btn-xs bg-danger"><i class="fa fa-remove"></i> <?= $_delete ?></a>
                          <?php } ?>
                        </td>
                      </tr>
                    <?php $n++; } ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="col-5">
            <?php if ($edituser != "") { ?>
            <form autocomplete="off" method="post" action="">
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title"><i class="fa fa-edit"></i> <?= $_edit_user ?> : <?= $edituser; ?></h3>
                </div>
                <div class="card-body">
                  <table class="table table-sm">
                    <tr>
                      <td class="align-middle"><?= $_user_name ?></td>
                      <td><input class="form-control" type="text" name="eu" value="<?= $edituser; ?>" required="1" readonly/></td>
                    </tr>
                    <tr>
                      <td class="align-middle"><?= $_password ?></td>
                      <td>
                        <div class="input-group">
                          <div class="input-group-11 col-box-10">
                            <input class="group-item group-item-l" id="np-e" type="password" name="np" required="1"/>
                          </div>
                          <div class="input-group-1 col-box-2">
                            <div class="group-item group-item-r pd-2p5 text-center align-middle">
                              <input title="Show/Hide Password" type="checkbox" onclick="PassU('np-e')">
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td class="align-middle"><?= $_confirm_password ?></td>
                      <td>
                        <div class="input-group">
                          <div class="input-group-11 col-box-10">
                            <input class="group-item group-item-l" id="nc-e" type="password" name="nc" required="1"/>
                          </div>
                          <div class="input-group-1 col-box-2">
                            <div class="group-item group-item-r pd-2p5 text-center align-middle">
                              <input title="Show/Hide Password" type="checkbox" onclick="PassU('nc-e')">
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td></td>
                      <td class="text-right">
                        <input class="btn bg-blue" type="submit" name="edit" value="<?= $_save ?>"/>
                        <a href="./admin.php?id=users" class="btn bg-grey"><?= $_cancel ?></a>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2"><?= $error; ?></td>
                    </tr>
                  </table>
                </div>
              </div>
            </form>
            <?php } else { ?>
            <form autocomplete="off" method="post" action="">
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title"><i class="fa fa-user-plus"></i> <?= $_add_user ?></h3>
                </div>
                <div class="card-body">
                  <table class="table table-sm">
                    <tr>
                      <td class="align-middle"><?= $_user_name ?></td>
                      <td><input class="form-control" type="text" name="nu" title="Username" placeholder="Username" required="1"/></td>
                    </tr>
                    <tr>
                      <td class="align-middle"><?= $_password ?></td>
                      <td>
                        <div class="input-group">
                          <div class="input-group-11 col-box-10">
                            <input class="group-item group-item-l" id="np" type="password" name="np" title="Password" required="1"/>
                          </div>
                          <div class="input-group-1 col-box-2">
                            <div class="group-item group-item-r pd-2p5 text-center align-middle">
                              <input title="Show/Hide Password" type="checkbox" onclick="PassU('np')">
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td class="align-middle"><?= $_confirm_password ?></td>
                      <td>
                        <div class="input-group">
                          <div class="input-group-11 col-box-10">
                            <input class="group-item group-item-l" id="nc" type="password" name="nc" title="Confirm Password" required="1"/>
                          </div>
                          <div class="input-group-1 col-box-2">
                            <div class="group-item group-item-r pd-2p5 text-center align-middle">
                              <input title="Show/Hide Password" type="checkbox" onclick="PassU('nc')">
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td></td>
                      <td class="text-right">
                        <input class="btn bg-primary" type="submit" name="add" value="<?= $_add ?>"/>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2"><?= $error; ?></td>
                    </tr>
                  </table>
                </div>
              </div>
            </form>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>