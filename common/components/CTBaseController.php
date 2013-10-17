<?php
/**
 * CTRights class
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.thankyoumenu.com/
 * @copyright Copyright &copy; 2013
 * @license http://www.thankyoumenu.com/license/
 */
class CTBaseController extends CController
{

    /**
     * @var array - array of allowed Actions
     */
    public $allowedActions = array();
    public $menu = array();
    public $meta_keywords = array();
    public $meta_description = array();
    public $breadcrumbs;
    private $output;

    /**
     * Class constructor
     *
     */
    public function init() {
        /* Filter out garbage requests */
        $uri = \Yii::app()->request->requestUri;
        if (strpos($uri, 'favicon') || strpos($uri, 'robots'))
            \Yii::app()->end();

        /* Run init */
        parent::init();
    }

    public function __construct($id, $module = null) {
        parent::__construct($id, $module);
        // If there is a post-request, redirect the application to the provided url of
        // the selected language
        if (isset($_POST['language'])) {
	        $language = substr($_POST['language'], 0, 2);
	        Yii::app()->language = $language;
	        Yii::app()->user->setState('language', $language);
	        $cookie = new CHttpCookie('language', $language);
	        $cookie->expire = time() + (31536000);
	        // (1 year)
	        Yii::app()->request->cookies['language'] = $cookie;
	        die('post');
        } else if (isset($_GET['language'])) {
            $language = substr($_GET['language'], 0, 2);
            Yii::app()->language = $language;
            Yii::app()->user->setState('language', $language);
            $cookie = new CHttpCookie('language', $language);
            $cookie->expire = time() + (31536000);
            // (1 year)
            Yii::app()->request->cookies['language'] = $cookie;
        } else if (Yii::app()->session->isStarted && Yii::app()->user->hasState('language')) {
            Yii::app()->language = Yii::app()->user->getState('language');
        } else if (isset(Yii::app()->request->cookies['language'])) {
	        Yii::app()->language = Yii::app()->request->cookies['language']->value;
        }
    }

    /**
     * @return array action filters
     */
    public function filters() {
        return array('CTRights');
    }

    /**
     * The filter method for 'rights' access filter.
     * This filter is a wrapper of {@link CAccessControlFilter}.
     * @param CFilterChain $filterChain the filter chain that the filter is on.
     */
    public function filterCTRights($filterChain) {
        $filter = new CTRightsFilter;
        $filter->allowedActions = $this->allowedActions;
        $filter->filter($filterChain);
    }

    /**
     * Denies the access of the user.
     * @param string $message the message to display to the user.
     * This method may be invoked when access check fails.
     * @throws CHttpException when called unless login is required.
     */
    public function accessDenied($message = null) {
        if ($message === null)
            $message = \Yii::t('core', 'You are not authorized to perform this action.');

        $user = \Yii::app()->getUser();
        if ($user->isGuest === true)
            $user->loginRequired();
        else
            throw new CHttpException(403, $message);
    }

    public function createMultilingualReturnUrl($lang = 'en') {
        if (count($_GET) > 0) {
            $arr = $_GET;
            $arr['language'] = $lang;
        } else
            $arr = array('language' => $lang);
        return $this->createUrl('', $arr);

    }

    /**
     * Gets a param
     * @param $name
     * @param null $defaultValue
     * @return mixed
     */
    public function getActionParam($name, $defaultValue = null) {
        return Yii::app()->request->getParam($name, $defaultValue);
    }

    /**
     * Loads the requested data model.
     * @param string the model class name
     * @param integer the model ID
     * @param array additional search criteria
     * @param boolean whether to throw exception if the model is not found. Defaults to true.
     * @return CActiveRecord the model instance.
     * @throws CHttpException if the model cannot be found
     */
    protected function loadModel($class, $id, $criteria = array(), $exceptionOnNull = true) {
        if (empty($criteria))
            $model = CActiveRecord::model($class)->findByPk($id);
        else {
            $finder = CActiveRecord::model($class);
            $c = new CDbCriteria($criteria);
            $c->mergeWith(array(
                'condition' => $finder->tableSchema->primaryKey . '=:id',
                'params' => array(':id' => $id),
            ));
            $model = $finder->find($c);
        }
        if (isset($model))
            return $model;
        else if ($exceptionOnNull)
            throw new CHttpException(404, 'Unable to find the requested object.');
    }

    /**
     * Performs the AJAX validation.
     * @param CModel the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === $model->formId) {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    /**
 * Outputs (echo) json representation of $data, prints html on debug mode.
 * NOTE: json_encode exists in PHP > 5.2, so it's safe to use it directly without checking
 * @param array $data the data (PHP array) to be encoded into json array
 * @param int $opts Bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_FORCE_OBJECT.
 * @param boolean $return whether the rendering result should be returned instead of being echo to end users.
 * @return string the rendering result. Null if the rendering result is not required.
 */
	public function renderJson($data, $opts = null, $return = false) {
		if (YII_DEBUG && isset($_GET['debug']) && is_array($data)) {
			CVarDumper::dump($data,20,true);
		} else {
			header('Content-Type: application/json; charset=UTF-8');
			if($return)
				return json_encode($data, $opts);
			else
				echo json_encode($data, $opts);
		}
		return null;
	}

    /**
     * Utility function to ensure the base url.
     * @param $url
     * @return string
     */
    public function baseUrl($url = '') {
        static $baseUrl;
        if ($baseUrl === null)
            $baseUrl = Yii::app()->request->baseUrl;
        return $baseUrl . '/' . ltrim($url, '/');
    }

	public function getOutput(){
		return $this->output;
	}

	public function setOutput($output){
		$this->output = $output;
	}
}
