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

$servername = getInput("What Would You Like Your Server's Name To Be?");

$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
    'username' => $username,
    'apiKey'   => $apikey,
));

$compute = $client->computeService('cloudServersOpenStack', 'DFW');

$ubuntu = $compute->image('df27d481-63a5-40ca-8920-3d132ed643d9');

$fivetwelveflavor = $compute->flavor('2');


$server = $compute->server();

try {
    $response = $server->create(array(
        'name'     => $servername,
        'image'    => $ubuntu,
        'flavor'   => $fivetwelveflavor,
	   'networks' => array(
            $compute->network(Network::RAX_PUBLIC),
            $compute->network(Network::RAX_PRIVATE)
        )
   ));

} catch (\Guzzle\Http\Exception\BadResponseException $e) {

    // No! Something failed. Let's find out:

    $responseBody = (string) $e->getResponse()->getBody();
    $statusCode   = $e->getResponse()->getStatusCode();
    $headers      = $e->getResponse()->getHeaderLines();

    echo sprintf("Status: %s\nBody: %s\nHeaders: %s", $statusCode, $responseBody, implode(', ', $headers));
}

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

?>