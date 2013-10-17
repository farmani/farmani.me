<?php if (!defined('YII_PATH')) exit('No direct script access allowed!');

/**
 * CTDbAuthManager class
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.thankyoumenu.com/
 * @copyright Copyright &copy; 2013
 * @license http://www.thankyoumenu.com/license/
 */
class CTDbAuthManager extends CDbAuthManager {

    public function init() {

        // Run the parent
        parent::init();

        // Run only if we are not guests
        if (!Yii::app()->user->isGuest) {
            // Assign a role to the member only if we didn't assign one yet
            if (!$this->isAssigned(Yii::app()->user->role, Yii::app()->user->id))
                if ($this->assign(Yii::app()->user->role, Yii::app()->user->id))
                    $this->save();
        }
    }

}