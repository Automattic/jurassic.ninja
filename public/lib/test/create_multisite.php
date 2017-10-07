<?php

require_once __DIR__ . '/../stuff.php';

$data = create_wordpress( 'php5.6', false, true, false, true );
print_r($data);
