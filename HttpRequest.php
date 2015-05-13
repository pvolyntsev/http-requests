<?php

namespace http_requests;

class HttpRequest extends \CComponent
{
	/**
	 * Строка идентификатора агента
	 * @var string
	 */
	public $userAgent = 'HTTP-REQUEST';

	/**
	 * Таймаут соединения, секунд
	 * @var int
	 */
	public $connectTimeout = 10;

	/**
	 * Таймаут ожидания, секунд
	 * @var int
	 */
	public $timeout = 10;

	/**
	 * Имя пользователя для авторизации
	 * @var string
	 */
	public $username;

	/**
	 * Пароль пользователя для авторизации
	 * @var string
	 */
	public $password;

	/**
	 * Проверять ли подлинность удалённого хоста с использованием сертификатов SSL
	 * @var bool|null
	 */
	public $ssl_verify;

	/**
	 * Путь к ключу для проверки подлинности сертификата SSL
	 * @var string|null
	 */
	public $ssl_certificate;

	/**
	 * Пароль к ключу для проверки подлинности сертификата SSL
	 * @var string|null
	 */
	public $ssl_certificate_password;

	/**
	 * Путь к файлу со списком доверенных центров проверки подлинности сертификатов SSL
	 * @var string|null
	 */
	public $ssl_cainfo;

	protected static $_stats = array(
		'count' => 0,
		'time' => 0,
	);

	protected $_context;

	/**
	 * @var HttpHeaders
	 */
	protected $_headers;

	public function getFileHeaders($url)
	{
		$file = new HttpFile($url);
		return $file->headers;
	}

	public function downloadFile($url, $absPathToFile)
	{
		$file = new HttpFile($url);
		return $file->download($absPathToFile);
	}

	public function getStats()
	{
		return self::$_stats;
	}
}