<?php

App::uses('AppHelper', 'View/Helper');

/**
 * VndErrorHelper is used to render errors in vnd.error format
 */
class VndErrorHelper extends AppHelper {

	public $helpers = array('RestKit.RestKit');

	/**
	 * makeJson() generates an array from (error) $data in vnd.error format ready for json encoding
	 *
	 * @param array $data
	 * @return array
	 */
	public function makeJson($data) {

		$out = array();
		$debug = Configure::read('debug');

		foreach ($data as $key => $error) {
			$temp = array();

			// only format as vnd.error in production mode
			if ($debug == 0) {
				$temp['logRef'] = $error['logRef'];
				$temp['message'] = $error['message'];
				$temp['_links'] = array();
				foreach ($error['links'] as $key => $pair) {
					array_push($temp['_links'], array($key => $pair));
				}
			} else {
				$temp = $error;
			}
			array_push($out, $temp);
		}
		return json_encode(array('errors' => $out));
	}

	public function makeXml($data) {
		$out['error'] = array();
		$debug = Configure::read('debug');

		foreach ($data as $key => $error) {
			$temp = array();

			if ($debug == 0) {
				// vnd.error always requires logRef and message
				$temp += array('@logRef' => $error['logRef']);
				$temp['message'] = $error['message'];

				// OPTIONAL: link
				$temp['link'] = array();
				foreach ($error['links'] as $link => $pair) { // e.g $link = 'help', $pair = array('href' => 'http://your.api.com/help/id')
					$links = array();
					foreach ($pair as $key => $value) {
						$links += array("@rel" => $link, "@$key" => $value);
					}
					array_push($temp['link'], $links);
				}
			} else {
				foreach ($error as $key => $value) {
					$temp[$key] = $value;
				}
			}
			array_push($out['error'], $temp);
		}
		return Xml::fromArray(array('errors' => $out))->asXML();
	}

}