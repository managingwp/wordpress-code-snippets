<?php
/**
 * test-open-tcp-ports.php
 * Description: Original created to test if ports are open outbound on hosting providers to be able to send tranational email
 * Status: Complete
 */
function testPorts($host, $ports, $timeout) {
    $openPorts = [];
    
    foreach ($ports as $port) {
        $errno = 0;
        $errstr = '';
        $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
        
        if ($connection) {
            $openPorts[] = $port;
            fclose($connection);
        }
    }
    
    return $openPorts;
}

$host = 'hostname';
$ports = [20, 21, 22, 23, 25, 53, 80, 110, 143, 443, 8080]; // List of ports to check
$timeout = 5;

$openPorts = testPorts($host, $ports, $timeout);

if (!empty($openPorts)) {
    echo "Open ports on $host:\n";
    echo implode(', ', $openPorts);
} else {
    echo "No open ports found on $host.";
}
?>
