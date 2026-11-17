<?php
require __DIR__ . '/includes/auth.php';
if (user()) logout();
redirect('login.php');
