<?php

namespace http_requests;

class HttpException extends \Exception {

	protected $_context = array();

	public function setContext($context)
	{
		$this->_context = $context;
	}
	
	public function getContext()
	{
		return $this->_context;
	}

	public function getRequestHeaders()
	{
		return array();
	}

	public function getResponseHeaders()
	{
		return array();
	}

	public function getRequestString()
	{
		return isset($this->_context['method'], $this->_context['url']) ? ($this->_context['method'] . ' ' . $this->_context['url']) : '';
	}

	public function getResponseString()
	{
		return isset($this->_context['response']) ? $this->_context['response'] : false;
	}

	public function getResponseCode()
	{
		return isset($this->_context['http_code']) ? $this->_context['http_code'] : false;
	}

	public function getResponseError()
	{
		return isset($this->_context['error']) ? $this->_context['error'] : false;
	}
}
