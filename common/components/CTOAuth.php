<?php
/**
 * CTOAuth.php class
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.thankyoumenu.com/
 * @copyright Copyright &copy; 2013
 * @license http://www.thankyoumenu.com/license/
 */
class CTOAuth extends CApplicationComponent
{
    const TOKEN_REQUEST = 0; //try to get a request token
    const TOKEN_ACCESS	= 1; //try to get an access token
    const TOKEN_VERIFY	= 2; //try to verify an access token so an API call can be made

    private $provider;

    public function __construct($mode)
    {
        $this->provider = new OAuthProvider();
        $this->provider->consumerHandler(array($this,'consumerHandler'));
        $this->provider->timestampNonceHandler(array($this,'timestampNonceHandler'));
        // no access token needed for this URL only
        $this->provider->setRequestTokenPath('/v1/oauth/requestToken');
        if ($mode == self::TOKEN_REQUEST) {
            $this->provider->isRequestTokenEndpoint(true);
            //enforce the presence of these parameters
            $this->provider->addRequiredParameter("oauth_callback");
            $this->provider->addRequiredParameter("scope");
        } else if ($mode == self::TOKEN_ACCESS) {
            $this->provider->tokenHandler(array($this,'checkRequestToken'));
        } else if ($mode == self::TOKEN_VERIFY) {
            $this->provider->tokenHandler(array($this,'checkAccessToken'));
        }
    }

    /**
     * Uses OAuthProvider->checkOAuthRequest() which initiates the callbacks and checks the signature
     *
     * @return bool|string
     */
    public function checkOAuthRequest()
    {
        try {
            $this->provider->checkOAuthRequest();
        } catch (Exception $Exception) {
            return OAuthProvider::reportProblem($Exception);
        }
        return true;
    }

    /**
     * Wrapper around CTOAuth::generateToken to add sha1 hashing at one place
     * @static
     * @return string
     */
    public static function generateToken()
    {
        $token = OAuthProvider::generateToken(40, true);
        return Yii::app()->func->hash($token);
    }

    /**
     * Generates and outputs a request token
     * @throws CException
     */
    public function outputRequestToken()
    {
        $token 			= CTOAuth::generateToken();
        $tokenSecret 	= CTOAuth::generateToken();
        $requestToken 	= new Tokens();

        $requestToken->consumer_key = $this->provider->consumer_key;
        $requestToken->token        = $token;
        $requestToken->token_secret = $tokenSecret;
        $requestToken->callback_url = Yii::app()->request->getParam('oauth_callback');
        $requestToken->timestamp    = time();
        $requestToken->scope        = $_GET['scope'];

        if(!$requestToken->save())
            CVarDumper::dump($requestToken->errors,10,true);

        echo "oauth_token=$token&oauth_token_secret=$tokenSecret&oauth_callback_confirmed=true";
    }

    /**
     * Tests if the provided RequestToken meets the RFC specs and if so creates and outputs an AccessToken
     *
     * @throws CException
     */
    public function outputAccessToken()
    {
        $token 			= CTOAuth::generateToken();
        $tokenSecret 	= CTOAuth::generateToken();
        $accessToken 	= new AccessToken();
        $requestToken	= Tokens::model()->findByAttributes(array('token'=>$this->provider->token));

        $accessToken->token         = $token;
        $accessToken->token_secret  = $tokenSecret;
        $accessToken->timestamp     = time();
        $accessToken->consumer_key  = $this->provider->consumer_key;
        $accessToken->user_id       = $requestToken->user_id;
        $accessToken->scope         = $requestToken->scope;

        if(!$accessToken->save())
            CVarDumper::dump($requestToken->errors,10,true);

        //The access token was saved. This means the request token that was exchanged for it can be deleted.
        try {
            $requestToken->delete();
        } catch (CDbException $Exception) {
            throw new CException($Exception->getMessage());
        }

        //all is well, output token
        echo "oauth_token=$token&oauth_token_secret=$tokenSecret";
    }

    /**
     * Returns the user Id for the currently authorized user
     *
     * @throws CException
     * @return integer
     */
    public function getUserId()
    {
        $accessToken = AccessToken::model()->findByAttributes(array('token'=>$this->provider->token));
        if($accessToken === false)
            throw new CException("Couldn't find a user id corresponding with current token information");

        return $accessToken->user_id;
    }

    /**
     * Checks if the nonce is valid and, if so, stores it in the DataStore.
     * Used as a callback function
     *
     * @param $provider
     * @return integer
     */
    public static function timestampNonceHandler($provider)
    {
        // Timestamp is off too much (5 min), refuse token
        $now = time();
        if ($now - $provider->timestamp > 300)
            return OAUTH_BAD_TIMESTAMP;


        $row = Yii::app()->db->createCommand()
            ->from('consumers_nonce')
            ->where('consumers_nonce.nonce=:nonce', array(':nonce' => $provider->nonce))
            ->queryRow();
        if ($row !== false)
            return OAUTH_BAD_NONCE;

        $consumersNonce = new ConsumersNonce();

        $consumersNonce->consumer_key = $provider->consumer_key;
        $consumersNonce->nonce = ($provider->nonce);
        $consumersNonce->timestamp = time();

        if(!$consumersNonce->save())
            return OAUTH_BAD_NONCE;

        return OAUTH_OK;
    }

    /**
     * Checks if the provided consumer key is valid and sets the corresponding
     * consumer secret. Used as a callback function.
     *
     * @static
     * @param $provider
     * @return integer
     */
    public static function consumerHandler($provider)
    {
        $row = Yii::app()->db->createCommand()
            ->from('consumers')
            ->where('consumers.consumer_key=:consumer_key', array(':consumer_key' => $provider->consumer_key))
            ->queryRow();
        if ($row === false)
            return OAUTH_CONSUMER_KEY_UNKNOWN;
        elseif(!(bool)$row['active'])
            return OAUTH_CONSUMER_KEY_REFUSED;

        $provider->consumer_secret = $row['consumer_secret'];
        return OAUTH_OK;
    }

    /**
     * Checks if there is token information for the provided token and sets the secret if it can be found.
     *
     * @static
     * @param $provider
     * @return integer
     */
    public static function checkRequestToken($provider)
    {
        // Ideally this function should rethrow exceptions, but the internals of PECL's OAuth class
        // Expect one of the OAUTH constants to be returned. When left out an exception is thrown, negating
        // out exception thrown here.

        //Token can not be loaded, reject it.
        $row = Yii::app()->db->createCommand()
            ->from('tokens')
            ->where('tokens.token=:token', array(':token' => $provider->token))
            ->queryRow();
        if ($row === false)
            return OAUTH_TOKEN_REJECTED;

        //The consumer must be the same as the one this request token was originally issued for
        if ($row['consumer_key'] != $provider->consumer_key) {
            return OAUTH_TOKEN_REJECTED;
        }

        //Check if the verification code is correct.
        if ($row['verifier'] != Yii::app()->request->getParam('oauth_verifier')) {
            return OAUTH_VERIFIER_INVALID;
        }

        $provider->token_secret = $row['token_secret'];
        return OAUTH_OK;
    }

    /**
     * Checks if there is token information for the provided access token and sets the secret if it can be found.
     *
     * @static
     * @param $provider
     * @return integer
     */
    public static function checkAccessToken($provider)
    {
        // Ideally this function should rethrow exceptions, but the internals of PECL's OAuth class
        // Expect one of the OAUTH constants to be returned. When left out an exception is thrown, negating
        // out exception thrown here.

        $row = Yii::app()->db->createCommand()
            ->from('access_token')
            ->where('access_token.token=:token', array(':token' => $provider->token))
            ->queryRow();
        if ($row === false)
            return OAUTH_TOKEN_REJECTED;

        //The consumer must be the same as the one this request token was originally issued for
        if ($row['consumer_key'] != $provider->consumer_key) {
            return OAUTH_TOKEN_REJECTED;
        }

        $provider->token_secret = $row['token_secret'];
        return OAUTH_OK;
    }
}