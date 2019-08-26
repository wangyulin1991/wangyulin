<?php
namespace org\mission;

class StepFactory
{
    static private $instance;
    static public function getInstance($stepType)
    {
        if(!self::$instance){
            try{
                require_once "lib/".$stepType.'.php';
                $class = "org\mission\lib\\".$stepType;
                self::$instance = $class::getInstance();
            }catch (\Exception $e){
                self::$instance = false;
            }
        }
        return self::$instance;
    }
}