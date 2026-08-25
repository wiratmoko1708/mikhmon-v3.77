<?php
function save_users($users) {
  $content = "<?php\n";
  $content .= "if (substr(\$_SERVER[\"REQUEST_URI\"], -8) == \"users.php\") { header(\"Location:./\"); };\n\n";
  foreach ($users as $u) {
    $content .= "\$users['" . $u['user'] . "'] = array('user' => '" . $u['user'] . "', 'pass' => '" . $u['pass'] . "');\n";
  }
  file_put_contents('./include/users.php', $content);
}