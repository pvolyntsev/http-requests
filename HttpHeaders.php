<?php

namespace http_requests;

class HttpHeaders
{
	protected $_headers = array();

	function __construct($headers = array())
	{
		if (is_string($headers))
		{
			$this->_headers = $this->parsePlainResponseHeaders($headers);
		} elseif (is_array($headers))
		{
			$this->_headers = $headers;
		}
	}

	public function parsePlainResponseHeaders($response)
	{
		$headers = array();
		$headersRaw = explode("\r\n",substr($response, 0, strpos($response, "\r\n\r\n")));
		foreach ($headersRaw as $i => $line) {
			if ($i === 0) {
				$headers['STATUS'] = $line;
				list($headers['HTTP_PROTOCOL'], $headers['HTTP_CODE'], $headers['HTTP_REASON']) = $this->parseStatus($headers['STATUS']);
			} else {
				list ($key, $value) = explode(': ', $line);
				$headers[strtoupper(str_replace('-', '_', $key))] = $value;
			}
		}
		return $headers;
	}

	public function parseStatus($status)
	{
		if (preg_match('/^(.+?)\s(\d+)\s(.+)$/', $status, $matches))
		{
			return array($matches[1], $matches[2], $matches[3]);
		}
		return array('HTTP/1.0', 404, 'Not found');
	}

	public function getHttpCode()
	{
		return $this->getHeader('HTTP_CODE', 404);
	}

	public function getHttpReason()
	{
		return $this->getHeader('HTTP_REASON', 'Not found');
	}

	public function getContentLength()
	{
		return $this->getHeader('CONTENT_LENGTH');
	}

	public function getLastModified()
	{
		return $this->getHeader('LAST_MODIFIED');
	}

	public function getETag()
	{
		return $this->getHeader('ETAG');
	}

	public function getContentType()
	{
		return $this->getHeader('CONTENT_TYPE');
	}

	public function getLocation()
	{
		return $this->getHeader('LOCATION');
	}

	public function getHeader($code, $defaultValue = null)
	{
		return isset($this->_headers[$code]) ? $this->_headers[$code] : $defaultValue;
	}
}