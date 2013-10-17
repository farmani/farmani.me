<?php if (!defined('YII_PATH')) exit('No direct script access allowed!');

/**
 * CTUserIdentity class
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.thankyoumenu.com/
 * @copyright Copyright &copy; 2013
 * @license http://www.thankyoumenu.com/license/
 */
class CTFacebookUserIdentity extends CUserIdentity
{
    const ERROR_STATUS_LOCKED = 3;
    /**
     * @var int unique member id
     */
    public $facebookUserId;
    public $accessToken;
    private $id;
    private $firstName;
    private $lastName;
    private $name;
    private $email;

    /**
     * Constructor.
     * @param string $facebookUserId facebook user id
     * @param string $accessToken password
     */
    public function __construct($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Authenticate a user.
     * The example implementation makes sure if the username and password
     * are both 'demo'.
     * In practical applications, this should be changed to authenticate
     * against some persistent user identity storage (e.g. database).
     * @return boolean whether authentication succeeds.
     */
    public function authenticate()
    {
        Yii::import('application.vendors.facebook.facebook');

        $facebook = new Facebook(array(
            'appId' => Yii::app()->params['facebook']['app_id'],
            'secret' => Yii::app()->params['facebook']['app_secret'],
            'trustForwarded' => true,
        ));

        $facebook->setAccessToken($this->accessToken);
        // Get User ID
        $facebookUser = $facebook->getUser();

        if ($facebookUser) {
            try {
                $userProfile = $facebook->api('/me');
                $record = Yii::app()->db->createCommand()
                    ->select('id, username, email, role, name, last_name, facebook_user_id, active')
                    ->from('users')
                    ->where('facebook_user_id=:facebook_user_id', array(':facebook_user_id' => (int)$facebookUser))
                    ->queryRow();

                if ($record === false) {
                    $user = new Users();
                    $user->facebook_user_id = (int)$userProfile['id'];
                    $user->username = empty($userProfile['username'])?$userProfile['id']:$userProfile['username'];
                    $user->name = $userProfile['first_name'];
                    $user->last_name = $userProfile['last_name'];
                    $user->email = $userProfile['id'].'@facebookmail.com';
                    $user->created_at = time();
                    $user->updated_at = time();
                    $user->role = 'User';
                    $user->active = true;
                    $user->password = Yii::app()->func->generatePassword();
                    if($user->save()){
                        $record['id']=$user->id;
                        $record['name']=$user->name;
                        $record['last_name']=$user->last_name;
                        $record['role']=$user->role;
                        $record['active']=$user->active;
                        $record['username']=$user->username;
                    }
                }

                //if(!(bool)$record['active']){
                if(false){
                    $this->errorCode = self::ERROR_STATUS_LOCKED;
                }else{
                    $this->id = $record['id'];
                    $this->firstName = $record['name'];
                    $this->lastName = $record['last_name'];
                    $this->username = $record['username'];
                    $this->email = $record['email'];

                    $auth = Yii::app()->authManager;
                    if (!$auth->isAssigned($record['role'], $this->id)) {
                        if ($auth->assign($record['role'], $this->id)) {
                            Yii::app()->authManager->save();
                        }
                    }

                    // We add username to the state
                    //$this->setState('name', $this->firstName . ' ' . $this->lastName);
                    //$this->setState('email', $this->email);
                    //$this->setState('username', $this->username);
                    $this->errorCode = self::ERROR_NONE;
                }
            } catch (FacebookApiException $e) {
                Yii::log($e, CLogger::LEVEL_TRACE, 'Components.CTFacebookUserIdentity');
                $this->errorCode = self::ERROR_UNKNOWN_IDENTITY;
            }
            Yii::app()->user->logoutUrl = $facebook->getLogoutUrl();
        }
        return !$this->errorCode;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getEmail()
    {
        return $this->email;
    }
}