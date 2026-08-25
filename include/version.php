<?php
if (!isset($_SESSION["mikhmon"])) {
    header("Location:../admin.php?id=login");
  } else {
        $_SESSION["v"] = "3.77 26-08-2026";
    
    }
