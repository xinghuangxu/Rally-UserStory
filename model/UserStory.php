<?php

class UserStory
{

    const API_NAME = "HierarchicalRequirement";

    private static $map = array();
    private $parent;
    private $children;
    private $data;

    public function getName()
    {
        return $this->data['_refObjectName'];
    }

    public function getId()
    {
        return $this->data['_refObjectUUID'];
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getParent()
    {
        if ($this->data['HasParent'] == 1) {
            if (!$this->parent) {
                $this->parent = UserStory::findWithId($this->data['Parent']['_refObjectUUID']);
            }
            return $this->parent;
        }
        return null;
    }

    public function __construct($data)
    {
        $this->data = $data;
        $this->children=array();
    }

    public static function findWithReleaseName($releaseName)
    {
        $result = array();
        $stories = Rally::getInstance()->find('userstory', "(Release.Name contains {$releaseName})", '', 'ScheduleState,Iteration,HasParent,Parent,Release,c_ArchitecturalTopicID');
        foreach ($stories as $story) {
            $result[] = UserStory::findWithId($story['_refObjectUUID']);
        }
        return $result;
    }

    public static function findWithId($id)
    {
        if (array_key_exists($id, self::$map)) {
            return self::$map[$id];
        }
        $story = Rally::getInstance()->get(UserStory::API_NAME, $id);
        self::$map[$id] = new UserStory($story[UserStory::API_NAME]);
        return self::$map[$id];
    }

    public function addChild(UserStory $child)
    {
        $parentId = $child->getParent()->getId();
        while ($parentId !== $this->getId()) {
            $parent = UserStory::findWithId($parentId);
            $parent->addChild($child);
            $child = $parent;
            $parentId = $child->getParent()?$child->getParent()->getId():null;
        }
        if(!array_key_exists($child->getId(), $this->children)){
            $this->children[$child->getId()]=$child;
        }
    }
    
    public function toString($indent){
        $str='<pre>'.  str_repeat(' ', $indent*3).$indent.'.'.$this->getName()."</pre>";
        $indent++;
        foreach($this->children as $child){
            $str.=$child->toString($indent);
        }
        return $str;
    }

}
