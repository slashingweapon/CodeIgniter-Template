<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
 *	A controller that supports Json requests.  
 *
 *	A POST request to <controllername>/json will be interpreted as a JSON-2.0 request.  Any data
 *	returned will be encoded in JSON and encapsulated in a JSON 2.0 reponse.
 */
class Json_Controller extends CI_Controller {

	/**
	 *	JSON router
	 *
	 *	This method's purpose is to parse json requests, find the right method to call, and encode
	 *	responses.  For more information about the requirements of a JSON-callable method, see
	 *	_json_test().
	 */
	function json() {
		$request = null;

		// create the response object and the mandatory attributes
		$response = new stdClass;
		$response->jsonrpc = "2.0";
		$response->id = time(); // just in case the request isn't valid
		
		try {
			$input = file_get_contents("php://input");
			if ($input === false)
				throw new Exception("Input not read", 500);
			
			$request = json_decode($input);
			if (!isset($request->method, $request->jsonrpc, $request->id))
				throw new Exception("Not a valid JSON-RPC 2.0 request",500);
			
			$response->id = $request->id;
			
			$method = array($this, "_json_{$request->method}");
			if (!is_callable($method))
				throw new Exception("Invalid method name",500);
			
			// we really should count the params, to make sure we're sending enough of them
			if (isset($request->params) && is_array($request->params))
				$result = call_user_func_array($method, $request->params);
			else
				$result = call_user_func($method);
			
			$response->result = $result;
			
		} catch(Exception $ex) {
			$error = new stdClass;
			$error->code = $ex->getCode();
			$error->message = $ex->getMessage();
			$response->error = $error;
		}
		
		header("Content-Type: application/json");
		echo json_encode($response);
		return;
	}
	
	/**
	 *	An example JSON method.
	 *
	 *	JSON methods must be public or protected, and being with the string "_json_".  They can
	 *	take any number of parameters, and MUST return a value.  The value can be a scalar, an
	 *	array, or any object which can be serialized.
	 *
	 *	***	Keep in mind that the JSON serializer will treat any array that isn't strictly 
	 *		numerically indexed as an object.  So it is probably a good idea to run array_values()
	 *		on your output array if you don't want it turned into an object on the client end. ***
	 *
	 *	If your method encounters an error that prevents it from producing a result, then you must
	 *	throw an exception.
	 */
	protected function _json_hello($name=null, $fail=false) {
		if (empty($name))
			$name = "JSON";
		
		if ($fail)
			throw new Exception("You wanted an error, so here it is", 100);
			
		return "Hello, $name";
	}
	
}

