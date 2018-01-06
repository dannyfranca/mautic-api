<?php

require_once __DIR__ . '/Config.inc.php';

$segmentName = 'Alunos';

//EXAMPLE
$Mautic = new \Model\Mautic();
$Mautic->setContext('segments'); //set context to segments
$segments = $Mautic->getList("name:{$segmentName}", ['minimal' => true]); //get segments list with a string value of $segmentName in name. (minimal true to not get unecessary data)
$Mautic->setContext('contacts'); //change context to contacts
foreach ($segments as $segment) : //actions for each segment found
    $allData = []; //array to fetch segment data however you want
    $contacts = $Mautic->getList("segment:{$segment['alias']}", ['minimal' => true]); //get list of contacts from segment
    echo "{$segment['name']}   /   alias: {$segment['alias']}<br>"; //Echo just to see things happening in browser
    foreach ($contacts as $contact) : //actions for each contact found
        $data = [//in this example, just getting and organizing the needed data
            'email' => $contact['fields']['all']['email'],
            'firstname' => $contact['fields']['all']['firstname'],
            'lastname' => $contact['fields']['all']['lastname'],
            'phone' => $contact['fields']['all']['phone']
        ];
        array_push($allData, $data); //push contact data into my collection array
    endforeach;
    var_dump($allData); //Do whatever you want with all data from this segment
endforeach;
