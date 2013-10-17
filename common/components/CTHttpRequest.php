<?php if (!defined('YII_PATH')) exit('No direct script access allowed!');
/**
 * CTHtml class
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.thankyoumenu.com/
 * @copyright Copyright &copy; 2013
 * @license http://www.thankyoumenu.com/license/
 */
class CTHttpRequest extends CHttpRequest
{
    private $_csrfToken;

    public function getCsrfToken()
    {
        if ($this->_csrfToken === null) {
            $session = Yii::app()->session;
            $csrfToken = $session->itemAt($this->csrfTokenName);
            if ($csrfToken === null) {
                $csrfToken = md5(uniqid(mt_rand(), true));
                $session->add($this->csrfTokenName, $csrfToken);
            }
            $this->_csrfToken = $csrfToken;
        }

        return $this->_csrfToken;
    }

    /**
     * Performs the CSRF validation.
     * This is the event handler responding to {@link CApplication::onBeginRequest}.
     * The default implementation will compare the CSRF token obtained
     * from a cookie and from a POST field. If they are different, a CSRF attack is detected.
     * @param CEvent $event event parameter
     * @throws CHttpException if the validation fails
     */
    public function validateCsrfToken($event)
    {
        if ($this->getIsPostRequest() || $this->isPutRequest || $this->isDeleteRequest) {
            // only validate POST/PUT/DELETE requests
            $session = Yii::app()->session;

            $method = $this->getRequestType();
            switch ($method) {
                case 'POST':
                    $userToken = $this->getPost($this->csrfTokenName);
                    break;
                case 'PUT':
                    $userToken = $this->getPut($this->csrfTokenName);
                    break;
                case 'DELETE':
                    $userToken = $this->getDelete($this->csrfTokenName);
            }

            if (!empty($userToken) && $session->contains($this->csrfTokenName)) {
                $sessionToken = $session->itemAt($this->csrfTokenName);
                $valid = $sessionToken === $userToken;
            } else
                $valid = false;

            if (!$valid)
                throw new CHttpException(400, Yii::t('yii', 'The CSRF token could not be verified.'));
        }
    }


	/**
	 * Returns the request type, such as GET, POST, HEAD, PUT, DELETE.
	 * Request type can be manually set in POST requests with a parameter named _method. Useful
	 * for RESTful request from older browsers which do not support PUT or DELETE
	 * natively (available since version 1.1.11).
	 * @return string request type, such as GET, POST, HEAD, PUT, DELETE.
	 */
	public function getRequestType()
	{
		if(isset($_POST['_method']))
			return strtoupper($_POST['_method']);

		return strtoupper(isset($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:'GET');
	}

	/**
	 * Returns whether this is a POST request.
	 * @return boolean whether this is a POST request.
	 */
	public function getIsPostRequest()
	{
		return (isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'],'POST')) || (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && !strcasecmp($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'],'POST'));
	}

	/**
	 * Returns whether this is a DELETE request.
	 * @return boolean whether this is a DELETE request.
	 * @since 1.1.7
	 */
	public function getIsDeleteRequest()
	{
		return (isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'],'DELETE')) || (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && !strcasecmp($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'],'DELETE')) || $this->getIsDeleteViaPostRequest();
	}

	/**
	 * Returns whether this is a DELETE request which was tunneled through POST.
	 * @return boolean whether this is a DELETE request tunneled through POST.
	 * @since 1.1.11
	 */
	protected function getIsDeleteViaPostRequest()
	{
		return isset($_POST['_method']) && !strcasecmp($_POST['_method'],'DELETE');
	}

	/**
	 * Returns whether this is a PUT request.
	 * @return boolean whether this is a PUT request.
	 * @since 1.1.7
	 */
	public function getIsPutRequest()
	{
		return (isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'],'PUT')) || (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && !strcasecmp($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'],'PUT')) || $this->getIsPutViaPostRequest();
	}

	/**
	 * Returns whether this is a PUT request which was tunneled through POST.
	 * @return boolean whether this is a PUT request tunneled through POST.
	 * @since 1.1.11
	 */
	protected function getIsPutViaPostRequest()
	{
		return isset($_POST['_method']) && !strcasecmp($_POST['_method'],'PUT');
	}

	/**
	 * Returns whether this is an AJAX (XMLHttpRequest) request.
	 * @return boolean whether this is an AJAX (XMLHttpRequest) request.
	 */
	public function getIsAjaxRequest()
	{
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';
	}

	/**
	 * Returns whether this is an Adobe Flash or Adobe Flex request.
	 * @return boolean whether this is an Adobe Flash or Adobe Flex request.
	 * @since 1.1.11
	 */
	public function getIsFlashRequest()
	{
		return isset($_SERVER['HTTP_USER_AGENT']) && (stripos($_SERVER['HTTP_USER_AGENT'],'Shockwave')!==false || stripos($_SERVER['HTTP_USER_AGENT'],'Flash')!==false);
	}
}
