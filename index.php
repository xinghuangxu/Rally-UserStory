<?php

require_once "./bootstrap.php";


$username = "";
$password = "";
Rally::getInstance($username, $password);

$releaseName = "WSU";
$userStories = UserStory::findWithReleaseName($releaseName);
$root = new UserStory(array(
    '_refObjectName' => "root",
    '_refObjectUUID' => null
        ));
foreach ($userStories as $userstory) {
    $root->addChild($userstory);
}
print $root->toString(0);
