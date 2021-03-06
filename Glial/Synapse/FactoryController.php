<?php

namespace Glial\Synapse;

use \Glial\Synapse\Controller;
use \Glial\I18n\I18n;

class FactoryController
{

    static $di = array();

    /**

     * (Glial 2.1)<br/>
     * Add a MVC node in a view 
     * @author Aurélien LEQUOY, <aurelien.lequoy@esysteme.com>
     * @param string construct of controller
     * @return boolean Success
     * @package Controller
     * @since 2.1 First time this was introduced.
     * @description create a new MVC and display the output in standard flux
     * @access public
     */
    public static function addNode($controller, $action, $param = array())
    {


        $node = new Controller($controller, $action, json_encode($param));
        $node->setDi(self::$di);
        $node->recursive = true;
        $node->getController();
    }

    /**
     * root controller
     *
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param string construct of controller
     * @return boolean Success
     * @description should be called 1 time by request. This factory create the (main) root MVC used in boot and display the output in standard flux. it's this controller witch will determine the layout.
     * @access public
     * @example \Glial\Synapse\FactoryController::rootNode("class", "function", array('param1','param2'));
     * @package Controller
     * @See Also addNode
     * @since 2.1 First time this was introduced.
     * @version 2.1
     */
    public static function rootNode($controller, $action, $param = array())
    {

        $node = new Controller($controller, $action, json_encode($param));
        $node->setDi(self::$di);

        $node->setRootNode();
        $node->getController();

        if (!empty(self::$di['js'])) {
            $node->setJs(self::$di['js']->getJavascript());
        }
        
        if (!$node->layout_name) {
            $node->display();
            return false;
        } else {

            $node->setLayout();
            return true;
        }
    }

    /**
     * This method inject dependency 
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param string construct of controller
     * @return boolean Success
     * @description should be called 1 time by request. This factory create the (main) root MVC used in boot and display the output in standard flux. it's this controller witch will determine the layout.
     * @access public
     * @example \Glial\Synapse\FactoryController::init($array);
     * @package Controller
     * @See Also addNode
     * @since 2.1.2 First time this was introduced.
     * @version 2.1.3
     */
    public static function setDi(array $di)
    {
        self::$di = $di;
    }

    public static function addDi($name, $object)
    {
        if (empty(self::$di[$name])) {
            self::$di[$name] = $object;
        } else {
            throw new Exception('GLI-019 : This dependency injection already exist !');
        }
    }

}
