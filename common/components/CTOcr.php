<?php
/**
 * CTOcr.php class
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.farmani.ir/
 * @copyright Copyright &copy; 2013
 */

// 1. Send image to Cloud OCR SDK using processImage call
// 2.	Get response as xml
// 3.	Read taskId from xml
class CTOcr extends CApplicationComponent
{
	// Name of application you created
	public $applicationId;
	// Password should be sent to your e-mail after application was created
	public $password;
	private $url = 'http://cloud.ocrsdk.com/processImage?language=english&imageSource=photo&exportFormat=txt';
	private $file = '';

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
			throw new CException(Yii::t('CTOcr.extension', 'Sorry, Buy you need to have the CURL extension enabled in order to be able to use this class.'), 500);
		}
		parent::init();
	}

	private function getFile(){
		if (!file_exists($this->file))
		{
			die('File ' . $this->file . ' not found.');
		}

		if (!is_readable($this->file)) {
			die('Access to file ' . $this->file . ' denied.');
		}

		return $this->file;
	}

	private function setFile($file){
		$this->file = $file;
	}

	public function postFile($file){
		if(empty($this->file))
			$this->setFile($file);
		// Send HTTP POST request and ret xml response
		$curlHandle = curl_init();
		curl_setopt($curlHandle, CURLOPT_URL, $this->url);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlHandle, CURLOPT_USERPWD, "$this->applicationId:$this->password");
		curl_setopt($curlHandle, CURLOPT_POST, 1);
		curl_setopt($curlHandle, CURLOPT_USERAGENT, "PHP Cloud OCR SDK Sample");
		$post_array['file'] = new CurlFile($this->getFile(), 'image/jpeg');//= array("my_file" => "@" . $this->getFile(),);
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $post_array);
		$response = curl_exec($curlHandle);
		if ($response == FALSE) {
			$errorText = curl_error($curlHandle);
			curl_close($curlHandle);
			throw new CException($errorText);
		}
		$httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
		curl_close($curlHandle);

		// Parse xml response
		$xml = simplexml_load_string($response);
		if ($httpCode != 200) {
			if (property_exists($xml, "message")) {
				throw new CException($xml->message);
			}
			throw new CException("Unexpected response " . $response);
		}

		$arr = $xml->task[0]->attributes();
		$taskStatus = $arr["status"];
		if ($taskStatus != "Queued") {
			throw new CException("Unexpected task status " . $taskStatus);
		}

		$result['task_id'] = $arr["id"];
		$result['result_url'] = $arr["resultUrl"];
		$result['status'] = $arr["status"];

		return $result;
	}

	public function getStatus($taskID){

		$url = 'http://cloud.ocrsdk.com/getTaskStatus';
		$qry_str = "?taskid=$taskID";

		$curlHandle = curl_init();
		curl_setopt($curlHandle, CURLOPT_URL, $url . $qry_str);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlHandle, CURLOPT_USERPWD, "$this->applicationId:$this->password");
		curl_setopt($curlHandle, CURLOPT_USERAGENT, "PHP Cloud OCR SDK Sample");
		$response = curl_exec($curlHandle);
		$httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
		curl_close($curlHandle);

		// parse xml
		$xml = simplexml_load_string($response);
		if ($httpCode != 200) {
			if (property_exists($xml, "message")) {
				throw new CException($xml->message);
			}
			throw new CException("Unexpected response " . $response);
		}
		$arr = $xml->task[0]->attributes();

		$result['task_id'] = (string)$arr["id"];
		$result['result_url'] = (string)$arr["resultUrl"];
		$result['status'] = (string)$arr["status"];

		if ($result['status'] != 'Queued' && $result['status'] != 'InProgress' && $result['status'] != 'Completed' && $result['status'] != 'ProcessingFailed')
			throw new CException("Unexpected task status " . $result['status']);

		return $result;
	}

	public function getResult($resultUrl){
		$curlHandle = curl_init();
		curl_setopt($curlHandle, CURLOPT_URL, $resultUrl);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($curlHandle);
		curl_close($curlHandle);
		return $response;
	}

}