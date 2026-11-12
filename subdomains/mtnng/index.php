<?php
$ts = (string) round(microtime(true) * 1000);
header('Location: /nc/?time_stamp=' . $ts, true, 302);
exit;

