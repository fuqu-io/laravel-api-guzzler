<?php

namespace FuquIo\ApiGuzzler;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

abstract class Client{
	
	protected $guzzle;
	private $last_call = ['name' => '', 'args' => []];
	public $last_json = '';
	
	public $status = 205;
	public $datum = null;
	
	public $chunk = [];
	public $record = [];
	public $records = [];
	
	/**
	 * @param $name
	 * @param $arguments
	 *
	 * @return Client
	 * @throws \Exception
	 */
	public function __call($name, $arguments){
		$this->last_call = ['name' => $name, 'args' => $arguments];
		
		$params = (!empty($arguments[0])) ? $arguments[0]:[];
		
		if(!empty($arguments[1]) and is_array($arguments[1])){
			$params = ['data' => $params];
			foreach($arguments[1] as $dot_notation => $value){
				Arr::set($params, 'data.' . $dot_notation, $value);
			}
		}
		
		$route_info = parse_ini_file(static::$endpoints, true);
		if(!empty($route_info[$name])){
			$route_info = $route_info[$name];
		}else{
			throw new \Exception('Missing route info.');
		}
		
		$url = $route_info['uri'];
		
		foreach($params as $k => $v){
			$pattern = '{' . $k . '}';
			if(strpos($url, $pattern) !== false){
				$url = str_replace($pattern, $v, $url);
				unset($params[$k]);
			}
		}
		
		$call = strtolower($route_info['method']);
		
		if($call == 'get' and !empty($params)){
			$url    .= '?' . http_build_query($params);
			$params = [];
		}
		
		if(in_array($call, ['post', 'put']) and !empty($params)){
			$this->last_json = json_encode($params, JSON_UNESCAPED_SLASHES);
			$params          = ['body' => json_encode($params, JSON_UNESCAPED_SLASHES)];
			//$params = [RequestOptions::JSON => json_encode($params)];
		}
		
		try{
			$result = $this->guzzle->$call($url, $params);
			$this->setStatusAndContent($result, $route_info);
		}catch(\Exception $exception){
			Log::critical($exception);
			throw $exception;
		}
		
	}
	
	/**
	 * @param $name
	 *
	 * @return mixed|null
	 */
	public function __get($name){
		if(!empty($this->$name)){
			return $this->$name;
		}
		
		$data = [$this->datum, $this->record];
		
		$iterator  = new \RecursiveArrayIterator($data);
		$recursive = new \RecursiveIteratorIterator(
			$iterator,
			\RecursiveIteratorIterator::SELF_FIRST
		);
		foreach($recursive as $key => $value){
			if($key === $name){
				return $value;
			}
		}
		
		return null;
	}
	
	
	/**
	 * @param ResponseInterface $result
	 * @param array             $route_info
	 */
	private function setStatusAndContent(ResponseInterface $result, array $route_info){
		$this->status  = $result->getStatusCode();
		$this->chunk   = [];
		$this->record  = [];
		$this->records = [];
		
		try{
			$this->datum = $result->getBody();
			
			$this->datum = (!empty($this->datum)) ? json_decode($this->datum, true):null;
			if(json_last_error() !== JSON_ERROR_NONE){
				throw new \Exception('Could not read response.');
			}
			
			switch(true){
				
				case (!empty($route_info['target'])):
				
				break;
				
				default:
					$this->record = $this->datum;
				break;
			}
			
			$x = 5;
		}catch(\Exception $exception){
			$this->status = 205;
			$this->datum  = null;
		}
	}
	
	/**
	 * @return array
	 * @internal
	 */
	public function getLastCall(){
		return $this->last_call;
	}
}
