<?php

namespace http_requests;

class HttpRestClient extends HttpRequest
{
	public function call($url, $method = 'GET', $data = null, $headers = array())
	{
		$startTime = microtime(true);
		$context = &$this->_context;
		$context = array();

		$context['method'] = $method = strtoupper(trim($method));
		$context['data'] = $data;

		$curl = curl_init();
		switch ($method)
		{
			case 'POST':
				curl_setopt($curl, CURLOPT_POST, $context['CURLOPT_POST'] = 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $context['CURLOPT_POSTFIELDS'] = $data);
				break;

			case 'PUT':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $context['CURLOPT_CUSTOMREQUEST'] = 'PUT');
				curl_setopt($curl, CURLOPT_POSTFIELDS, $context['CURLOPT_POSTFIELDS'] = $data);
				$headers[] = 'Content-Length: ' . strlen($data);
				break;

			case 'DELETE':
				curl_setopt($curl,CURLOPT_CUSTOMREQUEST, $context['CURLOPT_CUSTOMREQUEST'] = 'DELETE');
				break;

			case 'GET':
			default:
				$url = $this->_createGetUrl($url, $data);
				break;
		}

		curl_setopt($curl, CURLOPT_URL, $context['CURLOPT_URL'] = $context['url'] = $url);
		if (!empty($headers))
		{
			curl_setopt($curl, CURLOPT_HTTPHEADER, $context['CURLOPT_HTTPHEADER']= $headers);
		}

		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $context['CURLOPT_CONNECTTIMEOUT'] = $this->connectTimeout);
		curl_setopt($curl, CURLOPT_TIMEOUT, $context['CURLOPT_TIMEOUT'] = $this->timeout);
		curl_setopt($curl, CURLOPT_USERAGENT, $context['CURLOPT_USERAGENT'] = $this->userAgent);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $context['CURLOPT_FOLLOWLOCATION'] = true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, $context['CURLOPT_RETURNTRANSFER'] = true);

		$response = null;
		$http_code = -1;
		try
		{
			$context['response'] = $response = curl_exec($curl);
			$context['http_code'] = $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$context['info'] = $info = curl_getinfo($curl);
			$context['error'] = $error = curl_error($curl);
			if (!empty($error))
			{
				$httpException = new HttpException('CURL error occurred during the request: ' . $error);
				$httpException->setContext($context);
			}
		} catch (\Exception $e)
		{
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

		self::$_stats['count']++;
		self::$_stats['time'] += microtime(true) - $startTime;

		if (isset($httpException))
		{
			throw $httpException;
		}

		return $response;
	}

	protected function _createGetUrl($baseUrl, $data)
	{
		$urlOptions = parse_url($baseUrl);

		$query = array_merge(
			isset($urlOptions['query']) ? $this->_queryStringToArray($urlOptions['query']) : array(),
			$this->_queryStringToArray($data)
		);
		$queryString = $this->_arrayToQueryString($query);

		list($baseUrl,) = explode('?', $baseUrl);
		return $baseUrl . (!empty($queryString) ? '?' . $queryString : '');
	}

	protected function _queryStringToArray($queryString)
	{
		if (empty($queryString))
		{
			return array();
		}
		if (is_array($queryString))
		{
			return $queryString;
		}
		$result = array();
		$dataPairs = explode('&', $queryString);
		foreach ($dataPairs as $pair)
		{
			if (empty($pair))
				continue;
			list($name, $value) = explode('=', $pair);
			# TODO name[]=value
			# TODO name[index]=value
			$result[urldecode($name)] = urldecode($value);
		}
		return $result;
	}

	protected function _arrayToQueryString($array)
	{
		$dataPairs = array();
		foreach ($array as $name => $value)
		{
			# TODO name[]=value
			# TODO name[index]=value
			$dataPairs[] = implode('=', array(urlencode($name), urlencode($value)));
		}
		return implode('&', $dataPairs);
	}
}