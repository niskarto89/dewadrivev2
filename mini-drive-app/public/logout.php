<?php
require_once "../config/config.php";
session_destroy();
header("Location: index.php");
exit;
