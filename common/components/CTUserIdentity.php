<?php if (!defined('YII_PATH')) {
	exit('No direct script access allowed!');
}

/**
 * CTUserIdentity class
 *
 * @author    Ramin Farmani <ramin.farmani@gmail.com>
 * @link      http://www.thankyoumenu.com/
 * @copyright Copyright &copy; 2013
 * @license   http://www.thankyoumenu.com/license/
 */
class CTUserIdentity extends CUserIdentity
{
	const ERROR_STATUS_PENDING = 3;
	const ERROR_STATUS_LOCKED = 4;
	/**
	 * @var int unique member id
	 */
	private $id;
	private $role;
	private $name;
	private $email;
	public $username;
	public $password;
	public $errors = array();

	/**
	 * Constructor.
	 *
	 * @param string $username username or email address
	 * @param string $password password
	 */
	public function __construct($username, $password)
	{
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * Authenticate a user.
	 * The example implementation makes sure if the username and password
	 * are both 'demo'.
	 * In practical applications, this should be changed to authenticate
	 * against some persistent user identity storage (e.g. database).
	 *
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate()
	{
		if ((bool)strpos($this->username, '@')) {
			$record = Yii::app()->db->createCommand()
				->select('id, username, password, email, role, name, last_name')
				->from('users')
				->where('email=:email', array(':email' => $this->username))
				->queryRow();
		} else {
			$record = Yii::app()->db->createCommand()
				->select('id, username, password, email, role, name, last_name')
				->from('users')
				->where('username=:username', array(':username' => $this->username))
				->queryRow();
		}
		if ($record === false) {
			$this->errorCode = self::ERROR_USERNAME_INVALID;
			$this->errorMessage = Yii::t('app', 'Sorry, But we can\'t find a member with these login information.');
			$this->errors = array_merge(
				(bool)strpos($this->username, '@') ?
					array('email' => $this->errorMessage) : array('username' => $this->errorMessage), $this->errors
			);
		} else if (!CPasswordHelper::verifyPassword($this->password, $record['password'])) {
			$this->errorCode = self::ERROR_PASSWORD_INVALID;
			$this->errorMessage = Yii::t('app', 'Sorry, But the password did not match the one in our records.');
			$this->errors = array_merge(array('password' => $this->errorMessage), $this->errors);
		} else {
			$this->id = $record['id'];
			$this->role = $record['role'];
			$this->username = $record['username'];
			$this->email = $record['email'];
			$this->name = $record['name'] . $record['last_name'];

			$auth = Yii::app()->authManager;
			if (!$auth->isAssigned($record['role'], $this->id)) {
				if ($auth->assign($record['role'], $this->id)) {
					Yii::app()->authManager->save();
				}
			}


			// We add username to the state
			$this->setState('role', $this->role);
			$this->setState('username', $this->username);
			$this->setState('email', $this->email);
			$this->errorCode = self::ERROR_NONE;
		}
		return !$this->errorCode;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getRole()
	{
		return $this->role;
	}

	public function getEmail()
	{
		return $this->email;
	}

	public function getUsername()
	{
		return $this->username;
	}

	public function getName()
	{
		return $this->name;
	}
}