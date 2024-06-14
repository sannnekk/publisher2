<?php declare(strict_types=1);

namespace HMnet\Publisher2\Services\Util;

class HttpService {
	/**
	 * Send a GET request.
	 * 
	 * @param string $url
	 * @param string $contentType (json|xml)
	 * @return string
	 */
	public function get(string $url, string $contentType = 'json'): array|string {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		if ($contentType === 'json') {
			return json_decode($response, true);
		} else if ($contentType === 'xml') {
			return simplexml_load_string($response);
		}

		return $response;
	}

	/**
	 * Send a POST request.
	 * 
	 * @param string $url
	 * @param array $data
	 * @param string $contentType (json|xml)
	 * @return string
	 */
	public function post(string $url, array $data, string $contentType = 'json'): array|string {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		
		if ($contentType === 'json') {
			return json_decode($response, true);
		} else if ($contentType === 'xml') {
			return simplexml_load_string($response);
		}

		return $response;
	}

	/**
	 * Send a PATCH request.
	 * 
	 * @param string $url
	 * @param array $data
	 * @param string $contentType (json|xml)
	 * @return string
	 */
	public function patch(string $url, array $data, string $contentType = 'json'): array|string {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		
		if ($contentType === 'json') {
			return json_decode($response, true);
		} else if ($contentType === 'xml') {
			return simplexml_load_string($response);
		}

		return $response;
	}
}