<?php
require_once __DIR__ . '/../includes/functions.php';

session_unset();
session_destroy();
session_start();
$_SESSION['success'] = 'Anda telah berhasil keluar.';
redirect('/index.php');
