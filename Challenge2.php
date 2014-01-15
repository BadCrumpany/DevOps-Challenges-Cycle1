<?php

require 'vendor/autoload.php';

use OpenCloud\Rackspace;
use OpenCloud\Compute\Constants\ServerState;
use OpenCloud\Compute\Constants\Network;


function getInput($msg){
fwrite(STDOUT, "$msg: ");
$varin = trim(fgets(STDIN));
return $varin;
}

$username = getInput("Please Enter User Name");

$apikey = getInput("Please Enter API Key");

$sshkey = getInput("What Is The Full Path For Your Public Key Locally?");

echo file_get_contents ($sshkey);

$servername = getInput("What Would You Like Your Server's Name To Be?");

$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
    'username' => $username,
    'apiKey'   => $apikey,
));

$compute = $client->computeService('cloudServersOpenStack', 'DFW');

$server = $compute->server();

$ubuntu = $compute->image('df27d481-63a5-40ca-8920-3d132ed643d9');

$fivetwelveflavor = $compute->flavor('2');

$server->addFile('/root/.ssh/authorized_keys', file_get_contents($sshkey));

while (true) {$numberservers = getInput("How Many Servers Would You Like?");
    if ($numberservers > 3) {echo "Sorry Bro, You Can Only Build 1, 2, or 3 Servers. \n";}
    else {break;}}


$i = 1; while ($i <= $numberservers) { $server->create(array(
        'name'     => $servername .$i,
        'image'    => $ubuntu,
        'flavor'   => $fivetwelveflavor,
       'networks' => array(
            $compute->network(Network::RAX_PUBLIC),
            $compute->network(Network::RAX_PRIVATE)
        )
   ));

$callback = function($server) {
    if (!empty($server->error)) {
        var_dump($server->error);
        exit;
    } else {
        echo sprintf(
            "Waiting on %s/%-12s %4s%% \n",
            $server->name(),
            $server->status(),
            isset($server->progress) ? $server->progress : 0
        );
    }
};

$server->waitFor(ServerState::ACTIVE, 600, $callback);



printf("IP is %s, root password is %s\n",     $server->accessIPv4, $server->adminPass);

    $i++; };

?>