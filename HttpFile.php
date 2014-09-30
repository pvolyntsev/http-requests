<?php

namespace http_requests;

/**
 * Class HttpFile
 * @package http_requests
 *
 * @property bool $isExists
 * @property int $size
 * @property int $timeModified
 * @property string $hash
 * @property string $mimeType
 */
class HttpFile extends HttpRequest {

	/**
	 * Таймаут ожидания, секунд
	 * Чтобы получить большой файл, таймаут надо выключить
	 * @var int
	 */
	public $timeout = 0;

	protected $_remoteFileUrl;

	public function __construct($remoteFileUrl) {
		$this->_remoteFileUrl = $remoteFileUrl;
	}

	/**
	 * @return bool
	 */
	public function getIsExists() {
		return (200 == $this->attributes()->getHttpCode());
	}

	/**
	 * @return int
	 */
	public function getSize() {
		return $this->attributes()->getContentLength();
	}

	/**
	 * @return int
	 */
	public function getTimeModified() {
		return $this->attributes()->getLastModified();
	}

	/**
	 * @return string
	 */
	public function getHash() {
		return $this->attributes()->getETag();
	}

	/**
	 * @return string
	 */
	public function getMimeType() {
		return $this->attributes()->getContentType();
	}

	/**
	 * @return HttpHeaders
	 */
	public function getHeaders() {
		return $this->attributes();
	}

	/**
	 * @return HttpHeaders
	 * @throws HttpException
	 */
	protected function attributes() {
		if (!is_null($this->_headers)) {
			return $this->_headers;
		}

		$startTime = microtime(true);
		$context = &$this->_context;
		$context = array();

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $context['CURLOPT_URL'] = $context['url'] = $this->_remoteFileUrl);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $context['CURLOPT_CONNECTTIMEOUT'] = $this->connectTimeout);
		curl_setopt($curl, CURLOPT_TIMEOUT, $context['CURLOPT_TIMEOUT'] = $this->timeout);
		curl_setopt($curl, CURLOPT_USERAGENT, $context['CURLOPT_USERAGENT'] = $this->userAgent);
		curl_setopt($curl, CURLOPT_NOBODY, $context['CURLOPT_NOBODY'] = true);
		curl_setopt($curl, CURLOPT_HEADER, $context['CURLOPT_HEADER'] = true);
		curl_setopt($curl, CURLOPT_FILETIME, $context['CURLOPT_FILETIME'] = true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, $context['CURLOPT_RETURNTRANSFER'] = true);

		$response = '';
		$http_code = -1;
		try {
			$context['response'] = $response = curl_exec($curl);
			$context['http_code'] = $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$context['info'] = $info = curl_getinfo($curl);
			$context['error'] = $error = curl_error($curl);
			if (!empty($error)) {
				$httpException = new HttpException('CURL error "' . $error . '" occurred during the request');
				$httpException->setContext($context);
			}
		} catch (\Exception $e) {
			$context['error'] = $e->getMessage();
			$httpException = new HttpException('Exception occurred during the request', 0, $e);
			$httpException->setContext($context);
		}
		curl_close($curl);

		if (!isset($httpException) && isset($http_code) && ($http_code<200 || $http_code>=300))
		{
			$context['error'] = 'HTTP '.$http_code;
			$httpException = new HttpException('HTTP error ' . $http_code. ' occurred during the request', $http_code);
			$httpException->setContext($context);
		}

		$this->_headers = new HttpHeaders($response);

		self::$_stats['count']++;
		self::$_stats['time'] += microtime(true) - $startTime;

		if (isset($httpException))
		{
			throw $httpException;
		}

		return $this->_headers;
	}

	public function download($localAbsFilepath) {

		$startTime = microtime(true);
		$context = &$this->_context;
		$context = array();

		$context['localAbsFilepath'] = $localAbsFilepath;
		// поскольку файл большой, скачиваться будет прямо на диск через поток
		$fp = fopen ($localAbsFilepath, 'w+'); // Открыть поток для файла

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_FILE, $fp); // Записать ответ сразу в файл через поток
		curl_setopt($curl, CURLOPT_URL, $context['CURLOPT_URL'] = $context['url'] = $this->_remoteFileUrl);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $context['CURLOPT_CONNECTTIMEOUT'] = $this->connectTimeout);
		curl_setopt($curl, CURLOPT_TIMEOUT, $context['CURLOPT_TIMEOUT'] = 0); // чтобы получить большой файл, таймаут надо выключить
		curl_setopt($curl, CURLOPT_USERAGENT, $context['CURLOPT_USERAGENT'] = $this->userAgent);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $context['CURLOPT_FOLLOWLOCATION'] = true);

		$http_code = -1;
		try {
			$context['response'] = $response = curl_exec($curl);
			$context['http_code'] = $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$context['info'] = $info = curl_getinfo($curl);
			$context['error'] = $error = curl_error($curl);
			if (!empty($error)) {
				$httpException = new HttpException('CURL error "' . $error . '" occurred during the request');
				$httpException->setContext($context);
			}
		} catch (\Exception $e) {
			$context['error'] = $e->getMessage();
			$httpException = new HttpException('Exception occurred during the request', 0, $e);
			$httpException->setContext($context);
		}
		curl_close($curl);
		fclose($fp);

		if (!isset($httpException) && isset($http_code) && ($http_code<200 || $http_code>=300))
		{
			$context['error'] = 'HTTP '.$http_code;
			$httpException = new HttpException('HTTP error ' . $http_code. ' occurred during the request', $http_code);
			$httpException->setContext($context);
		}

		self::$_stats['count']++;
		self::$_stats['time'] += microtime(true) - $startTime;

		if (isset($httpException))
		{
			throw $httpException;
		}

		return true;
	}
}