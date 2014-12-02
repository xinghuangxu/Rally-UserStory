<?php

class UserStoryTest extends PHPUnit_Framework_TestCase
{
    private $rally;

    protected function setUp()
    {
        $username = "";
        $password = "";
        $this->rally=Rally::getInstance($username, $password);
    }
    
    public function testGet()
    {
        $subscription=$this->rally->get("subscription","1753949417");
        $this->assertTrue(!empty($subscription));
    }
    
    public function testFindWithId(){
        $id="18715123957";
        $userstory=UserStory::findWithId($id);
        $this->assertNotEmpty($userstory->getName());
        $this->assertNotEmpty($userstory->getId());
    }
    
    public function testGetParent(){
        $id="18715123957";
        $userstory=UserStory::findWithId($id);
        $parent=$userstory->getParent();
        $this->assertNotEmpty($parent->getName());
        $this->assertNotEmpty($parent->getId());
    }
    
    public function testGetReleaseTests(){
        $releaseName = "WSU";
        $userStories=UserStory::findWithReleaseName($releaseName);
        $root=new UserStory(array(
            '_refObjectName'=>"root",
            '_refObjectUUID'=>null
        ));
        foreach($userStories as $userstory){
            $root->addChild($userstory);
        }
        $this->assertTrue(count($root->getChildren())>0);
    }

}
