<?php

namespace Ubiquity\controllers\rest\api\jsonapi;

use Ubiquity\orm\DAO;
use Ubiquity\controllers\rest\RestError;
use Ubiquity\controllers\Startup;
use Ubiquity\controllers\rest\RestBaseController;

/**
 * Rest JsonAPI implementation.
 * Ubiquity\controllers\rest\api\jsonapi$JsonApiRestController
 * This class is part of Ubiquity
 *
 * @author jcheron <myaddressmail@gmail.com>
 * @version 1.0.0
 * @since Ubiquity 2.0.11
 *
 */
abstract class JsonApiRestController extends RestBaseController {
	const API_VERSION = 'JsonAPI 1.0';

	protected function _setResource($resource) {
		$modelsNS = $this->config ["mvcNS"] ["models"];
		$this->model = $modelsNS . "\\" . ucfirst ( $resource );
	}

	protected function _checkResource($resource, $callback) {
		$this->_setResource ( $resource );
		if (class_exists ( $this->model )) {
			$callback ();
		} else {
			$error = new RestError ( 404, "Not existing class", $this->model . " class does not exists!", Startup::getController () . "/" . Startup::getAction () );
			echo $this->_format ( $error->asArray () );
		}
	}

	protected function getRequestParam($param, $default) {
		if (isset ( $_GET [$param] )) {
			return $_GET [$param];
		}
		return $default;
	}

	/**
	 * Returns all the instances from the model $resource.
	 * Query parameters:
	 * - **included**: A string of associated members to load, comma separated (e.g. users,groups,organization...), or a boolean: true for all members, false for none (default: true).
	 * - **filter**: The filter to apply to the query (where part of an SQL query) (default: 1=1).
	 * - **page[number]**: The page to display (in this case, the page size is set to 1).
	 * - **page[size]**: The page size (count of instance per page) (default: 1).
	 *
	 * @route("{resource}/","methods"=>["get"],"priority"=>0)
	 */
	public function getAll_($resource) {
		$this->_checkResource ( $resource, function () {
			$filter = $this->getRequestParam ( 'filter', '1=1' );
			$pages = null;
			if (isset ( $_GET ['page'] )) {
				$pageNumber = $_GET ['page'] ['number'];
				$pageSize = $_GET ['page'] ['size'] ?? 1;
				$pages = $this->generatePagination ( $filter, $pageNumber, $pageSize );
			}
			$datas = DAO::getAll ( $this->model, $filter, $this->getIncluded ( $this->getRequestParam ( 'included', true ) ) );
			echo $this->_getResponseFormatter ()->get ( $datas, $pages );
		} );
	}

	/**
	 * Returns an instance of $resource, by primary key $id.
	 * Query parameters:
	 * - **included**: A string of associated members to load, comma separated (e.g. users,groups,organization...), or a boolean: true for all members, false for none (default: true).
	 * - **filter**: The filter to apply to the query (where part of an SQL query) (default: 1=1).
	 *
	 * @param string $resource
	 *        	The resource (model) to use
	 * @param string $id
	 *        	The primary key value(s), if the primary key is composite, use a comma to separate the values (e.g. 1,115,AB)
	 *
	 * @route("{resource}/{id}/","methods"=>["get"],"priority"=>1000)
	 */
	public function getOne_($resource, $id) {
		$this->_checkResource ( $resource, function () use ($id) {
			$this->_getOne ( $id, true, false );
		} );
	}

	/**
	 * Returns the api version
	 *
	 * @return string
	 */
	public static function _getApiVersion() {
		return self::API_VERSION;
	}
}
