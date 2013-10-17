<?php if (!defined('YII_PATH')) exit('No direct script access allowed!');

/**
 * CTRightsFilter class
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.thankyoumenu.com/
 * @copyright Copyright &copy; 2013
 * @license http://www.thankyoumenu.com/license/
 */
class CTRightsFilter extends CFilter {
    protected $allowedActions = array();
    protected $state='';

    /**
     * Performs the pre-action filtering.
     * @param CFilterChain $filterChain the filter chain that the filter is on.
     * @return boolean whether the filtering process should continue and the action
     * should be executed.
     */
    protected function preFilter($filterChain) {
        // By default we assume that the user is allowed access
        $allow = false;

        $user = Yii::app()->getUser();
        $controller = $filterChain->controller;
        $action = $filterChain->action;

        // Check if the action should be allowed
        if (in_array($action->id, $this->allowedActions) === false) {
            // Initialize the authorization item as an empty string
            $authItem = $this->state . '.';

            // Append the module id to the authorization item name
            // in case the controller called belongs to a module
            if (($module = $controller->getModule()) !== null)
                $authItem .= ucfirst($module->id) . '.';
            else
                $authItem .= 'Default.';

            // Append the controller id to the authorization item name
            $authItem .= ucfirst($controller->id);
            // Check if user has access to the controller
            if ($user->checkAccess($authItem . '.*') !== true) {
                // Append the action id to the authorization item name
                $authItem .= '.' . ucfirst($action->id);
                //Yii::log($authItem, CLogger::LEVEL_INFO,'application');
                // Check if the user has access to the controller action
                if ($user->checkAccess($authItem) === true)
                    $allow = true;
                
            }else
                $allow = true;
        }else
            $allow = true;

        // User is not allowed access, deny access
        if ($allow === false) {
            $controller->accessDenied();
            return $allow;
        }

        // Authorization item did not exist or the user had access, allow access
        return $allow;
    }

    /**
     * Sets the allowed actions.
     * @param array $allowedActions the actions that are always allowed
     */
    public function setAllowedActions($allowedActions) {
        $this->allowedActions = $allowedActions;
    }

    /**
     * Sets the state.
     * @param string $state State of application Backend, Frontend or Api
     */
    public function setState($state='') {
        $this->state = $state;
    }

}
