<?php
session_start();
session_destroy();

// Redirect to kiosk home
header("Location: ../index.html");
exit;
