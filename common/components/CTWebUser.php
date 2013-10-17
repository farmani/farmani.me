<?php
/**
 * CTWebUser class
 *
 * @author    Ramin Farmani <ramin.farmani@gmail.com>
 * @link      http://www.thankyoumenu.com/
 * @copyright Copyright &copy; 2013
 * @license   http://www.thankyoumenu.com/license/
 */
class CTWebUser extends CWebUser
{
	/**
	 * @var object
	 */
	public $_firstLoginTry = true;
	/**
	 * @var string|array the URL for logout. If using array, the first element should be
	 *      the route to the logout action, and the rest name-value pairs are GET parameters
	 *      to construct the logout URL (e.g. array('/site/logout')). If this property is null,
	 *      a 403 HTTP exception will be raised instead.
	 * @see CController::createUrl
	 */
	public $logoutUrl = array('/users/logout');

	public function init()
	{
		$conf = Yii::app()->session->cookieParams;
		$this->identityCookie = array(
			'path' => $conf['path'],
			'domain' => $conf['domain'],
		);
		parent::init();
	}

	/**
	 * This is here since there is a bug with cookies
	 * that have been saved to a domain name such as
	 * .domain.com so all subdomains can access it as well
	 *
	 * @see http://code.google.com/p/yii/issues/detail?id=856
	 *
	 * @param boolean $destroySession
	 */
	public function logout($destroySession = true)
	{
		if ($this->allowAutoLogin && isset($this->identityCookie['domain'])) {
			$cookies = Yii::app()->getRequest()->getCookies();

			if (null !== ($cookie = $cookies[$this->getStateKeyPrefix()])) {
				$originalCookie = new CHttpCookie($cookie->name, $cookie->value);
				$cookie->domain = $this->identityCookie['domain'];
				$cookies->remove($this->getStateKeyPrefix());
				$cookies->add($originalCookie->name, $originalCookie);
			}

			// Remove Roles
			$assignedRoles = Yii::app()->authManager->getRoles(Yii::app()->user->id);
			if (!empty($assignedRoles)) {
				$auth = Yii::app()->authManager;
				foreach ($assignedRoles as $n => $role) {
					if ($auth->revoke($n, Yii::app()->user->id)) {
						$auth->save();
					}
				}
			}
		}

		parent::logout($destroySession);
	}

	/**
	 * Fetch User role
	 *
	 * @return string - User role
	 */
	public function getRole()
	{
		if (!$this->isGuest) {
			//$user = Yii::app()->db->createCommand()->select('role')->from('users')->where('id=:id', array(':id' => $this->id,))->queryRow();
			//if ($user !== null) {
			//	return $user['role'];
			//}
			$this->getState('role');
		}
		return false;
	}

	/**
	 * Get flash keys
	 *
	 * @return array flash message keys array
	 */
	public function getFlashKeys()
	{
		$counters = $this->getState(self::FLASH_COUNTERS);
		if (!is_array($counters)) {
			return array();
		}
		return array_keys($counters);
	}
}