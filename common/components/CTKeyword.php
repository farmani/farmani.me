<?php
/**
 * CTKeyword.php class
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.farmani.ir/
 * @copyright Copyright &copy; 2013
 */

class CTKeyword extends CApplicationComponent
{
	// Name of application you created
	public $key;
	/**
	 * Component initializer
	 *
	 * @throws CException on missing CURL PHP Extension
	 */
	public function init()
	{
		// Make sure we have CURL enabled
		if( ! function_exists('curl_init') )
		{
			throw new CException(Yii::t('CTKeyword', 'Sorry, Buy you need to have the CURL extension enabled in order to be able to use this class.'), 500);
		}
		$path = Yii::getPathOfAlias('backend.extensions.alchemy');
		require_once($path.'/AlchemyAPI.php');
		require_once($path.'/AlchemyAPIParams.php');
		parent::init();
	}

	public function getKeywords($string){

		$cleanedString = $this->clean($string);

		$alchemyObj = new AlchemyAPI();

		$alchemyObj->setAPIKey($this->key);

		$keywordParams = new AlchemyAPI_KeywordParams();
		$keywordParams->setMaxRetrieve(100);
		$keywordParams->setKeywordExtractMode('strict');

		// Extract concept tags from a text string.
		$result = $alchemyObj->TextGetRankedKeywords($cleanedString,AlchemyAPI::JSON_OUTPUT_MODE,$keywordParams);
		return $result;
	}

	public function clean($string){
		$words = array();
		$stopWord = array('a', 'an', 'am', 'and', 'are', 'as', 'at', 'be', 'but', 'by', 'for', 'if', 'in', 'into', 'is', 'it', 'no','not','of','on','or','pm','s','such','t','that','the','their','then','there','these','they','this','to','was','will','with');
		$string = preg_replace('/[^[:alpha:]]/', ' ',$string);
		$string = trim($string);
		$string = explode(" ", $string);
		foreach($string as $key => $word){
			$tmp = trim($word);
			if(!empty($tmp) && strlen($tmp) > 1 && !in_array($tmp,$stopWord))
				$words[] = strtolower($tmp);
		}

		return implode(' ',$words);
	}
}