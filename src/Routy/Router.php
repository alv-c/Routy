<?php

namespace Routy;

/**
 * Class to manage the routes
 *
 * @author Alvaro Carneiro <d3sign.night@gmail.com>
 * @package Routy
 * @license MIT License
 * @copyright 2012 Alvaro Carneiro
 */
class Router {
	
	/**
	 * List of routes names
	 * Used to generate urls using the base url and assignin values to wildcards
	 *
	 * @var array
	 */
	protected $name = array();
	
	/**
	 * Context of the router
	 *
	 * @var string
	 */
	protected $context;
	
	/**
	 * The url of the web page, if not provided it will be automatically generated
	 *
	 * @var string
	 */
	protected $base_url;
	
	/**
	 * The uri string requested
	 *
	 * @var string
	 */
	protected $uri;
	
	/**
	 * List of error handlers. They will be called when we catch a HttpException error
	 *
	 * @var array
	 */
	protected $error_handlers;
	
	/**
	 * List of callbacks assigned to certain request method
	 *
	 * @var array
	 */
	protected $actions = array(
		'ANY'		=>	array(),
		'GET'		=>	array(),
		'POST'		=>	array(),
		'PUT'		=>	array(),
		'DELETE'	=>	array()
	);
	
	/**
	 * Generate the router
	 *
	 * <code>
	 *	$router = new Routy\Router(); // Will generate a router to handle request from: http://site.com/
	 *
	 *	$admin = new Routy\Router('admin'); // Will generate a router to handle request from: http://site.com/admin
	 * </code>
	 *
	 * @param string $context Context of the router
	 * @param string $base_url The url of the web page, if not provided it will be automaitally generater
	 */
	public function __construct($context = '', $base_url = null)
	{
		// Generate the base url if not provided
		if ( ! $base_url)
		{
			$base_url = "http://{$_SERVER['HTTP_HOST']}";
		}
		
		// Add a trailing slash but delete it if
		// it's already in there to prevent making a double slash url
		$this->base_url = rtrim($base_url, '/') . '/';
		
		
		// The same as the base_url
		$this->context = trim($context, '/');
		
		// parse the request uri to prevent problems and do
		// the same as we did with "base_url" and "context"
		$this->uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
	}
	
	/**
	 * Get the entire url generated by joining the "base_url" variable and "context"
	 *
	 * <code>
	 *	$router = new Routy\Router('blog');
	 *
	 *	$router->base(); // will return: http://site.com/blog
	 *
	 *	$other = new Routy\Router('other/index.php', 'example.net');
	 *
	 *	$other->base(); // will return: example.net/other/index.php
	 * </code>
	 *
	 * @return string The entire url
	 */
	public function base()
	{
		$base = $this->base_url . $this->context;
		
		return $base;
	}
	
	/**
	 * Assign a callback to one or more routes
	 *
	 * @param string $method Request method
	 * @param string|array $route Routes this callback with handle
	 * @param callable $callback The callback
	 *
	 * @throws InvalidArgumentException When the callback parameter isn't valid
	 *
	 * @return Action The action
	 */
	protected function register($method, $route, $callback)
	{
		// Create the new action
		$action = new Action($route, $callback, $this);
		
		// and save it to the corresponding request method
		return $this->actions[$method][] = $action;
	}
	
	/**
	 * Assign a callback to one or more routes from every request method
	 *
	 * @param string|array $route Routes this callback with handle
	 * @param callable $callback The callback
	 *
	 * @throws InvalidArgumentException When the callback parameter isn't valid
	 *
	 * @return Action The action
	 */
	public function any($route, $callback)
	{
		return $this->register('ANY', $route, $callback);
	}
	
	/**
	 * Assign a callback to one or more routes from GET request method
	 *
	 * @param string|array $route Routes this callback with handle
	 * @param callable $callback The callback
	 *
	 * @throws InvalidArgumentException When the callback parameter isn't valid
	 *
	 * @return Action The action
	 */
	public function get($route, $callback)
	{
		return $this->register('GET', $route, $callback);
	}
	
	/**
	 * Assign a callback to one or more routes from POST request method
	 *
	 * @param string|array $route Routes this callback with handle
	 * @param callable $callback The callback
	 *
	 * @throws InvalidArgumentException When the callback parameter isn't valid
	 *
	 * @return Action The action
	 */
	public function post($route, $callback)
	{
		return $this->register('POST', $route, $callback);
	}
	
	/**
	 * Assign a callback to one or more routes from GET request method
	 *
	 * Because HTML don't support PUT/DELETE methods we use "_method" input (in $_REQUEST variable). 
	 *
	 * @param string|array $route Routes this callback with handle
	 * @param callable $callback The callback
	 *
	 * @throws InvalidArgumentException When the callback parameter isn't valid
	 *
	 * @return Action The action
	 */
	public function put($route, $callback)
	{
		return $this->register('PUT', $route, $callback);
	}
	
	/**
	 * Assign a callback to one or more routes from DELETE request method
	 *
	 * Because HTML don't support PUT/DELETE methods we use "_method" input (in $_REQUEST variable). 
	 *
	 * @param string|array $route Routes this callback with handle
	 * @param callable $callback The callback
	 *
	 * @throws InvalidArgumentException When the callback parameter isn't valid
	 *
	 * @return Action The action
	 */
	public function delete($route, $callback)
	{
		return $this->register('DELETE', $route, $callback);
	}
	
	/**
	 * Generate a route using the base_url variable.
	 * If there's no predefined route identified with the $name we'll use
	 * the $name variable as the url we're going to generate
	 *
	 * @param string $name The name of the route
	 * @param array $replacements Structure to give to it
	 * @param integer $offset Some actions have more than one route, optionally you can declare wich one do you like to use
	 *
	 * @return string The build route
	 */
	public function to($name, array $replacements = array(), $offset = 0)
	{
		// try to set the route with a predefined one
		// but if it don't exists use the name as it
		if ( ! ($action = $this->find($name)))
		{
			$route = $name;
		}
		else
		{
			$routes = $action->route();
			
			$route = isset($routes[$offset]) ? $routes[$offset] : $routes[0];
		}
		
		// If we've to do some replacements
		if (count($replacements))
		{
			$route = str_replace(array_keys($replacements), array_values($replacements), $route);
		}
		
		return $this->base() . '/' . $route;
	}
	
	/**
	 * Sets an identifier to this route, so we can make a url to it
	 *
	 * @param Action $action The action
	 * @param string $name The name of this action
	 *
	 * @return void
	 */
	public function identify(Action $action, $name)
	{
		$this->name[$name] = $action;
	}
	
	/**
	 * Get the action with this identifier
	 *
	 * @param string $name The name of the action
	 *
	 * @return Action|bool The action, or false if it don't exists
	 */
	public function find($name)
	{
		return ( ! isset($this->name[$name])) ?: $this->name[$name];
	}
	
	/**
	 * Run the router
	 *
	 */
	public function run()
	{
		// if the _method is set and it's valid
		// use it instead of the normal request method value
		if (isset($_REQUEST['_method']) && in_array(strtoupper($_REQUEST['_method']), array('PUT', 'DELETE')))
		{
			$method = strtoupper($_REQUEST['_method']);
		}
		// if not set or not valid use the normal request method
		else
		{
			$method = $_SERVER['REQUEST_METHOD'];
		}
		
		// merge the actions with the ones that correspond
		// to the actual request method
		$actions = array_merge($this->actions['ANY'], $this->actions[$method]);
		
		try
		{
			// Itereate over each action
			foreach ($actions as $action)
			{
				// And over each route of it
				foreach ($action->route() as $route)
				{
					// check if the current uri matches with the
					// action's route and fetch the arguments to pass to it
					list($matches, $arguments) = $this->matches($route);
					
					// if matches, call it with the arguments
					// and return the contents of it
					if ($matches)
					{
						return $action->call($arguments);
					}
				}
			}
			
			// we finished the foreach without calling any action
			// so we can throw a http not found error
			throw new HttpException('Route not found', 404);
		}
		// use that try statment so we can throw
		// an http exception inside of an action
		catch(HttpException $error)
		{
			// If we can handle errors with this status code
			if (isset($this->error_handlers[(string)$error->getCode()]))
			{
				$handler = $this->error_handlers[(string)$error->getCode()];
			}
			// if we setted a global handler for all type of http errors
			elseif (isset($this->error_handlers['global']))
			{
				$handler = $this->error_handlers['global'];
			}
			// make the handler simply to throw the exception again
			// because we don't setted an error handler
			else
			{
				$handler = function($exception){ throw $exception; };
			}
			
			// call that handler passing by parameter
			// the thrown exception
			return call_user_func($handler, $error);
		}
	}
	
	/**
	 * Set an error handler to the given http error codes
	 * If $code isn't an integer type it must be a callable type used to handle all type
	 * of http errors
	 *
	 * <code>
	 *	$router->error('404', function(){ }); // Will handle http errors with 404 code (page not found)
	 *	
	 *	$router->error(function(){ }); // Will handle all type of http errors except 404 because we already have defined the handler for that error
	 * </code>
	 *
	 * @param int $code The http error code we'll handle
	 * @param callable $handler The handler of it
	 */
	public function error($code, $handler = null)
	{
		// if the $code isn't an integer use it as the handler to
		// all type of exceptions
		if ( ! is_int($code))
		{
			$handler = $code;
			$code = 'global';
		}
		
		// If the handler is invalid
		// throw an exception
		if ( ! is_callable($handler))
		{
			throw new \InvalidArgumentException('The $handler parameter isn\'t valid.');
		}
		
		// Make the $code variable an array so we can
		// handle mutliple http errors types
		foreach ((array)$code as $status)
		{
			$this->error_handlers[(string)$status] = $handler;
		}
	}
	
	/**
	 * Check if the route matches the requested uri
	 *
	 * @param string $route The route to check if matches
	 *
	 * @todo Add regular expression comparision
	 *
	 * @return array The first element of the array is the resultant of the comparison and the second are the parameters to pass to it
	 */
	public function matches($route)
	{
		$route = trim($this->context . '/' . trim($route, '/'), '/');
		
		// No wildcards, simply compare it
		if ( ! strpos($route, '{'))
		{
			// Because it don't have regular expressions
			// compare it and simply pass an array as arguments
			$matches = $route == $this->uri;
			
			$arguments = array();
		}
		else
		{
			// apply wildcards to the route
			$route = Wildcards::make($route);
			
			// Use regular expressions to compare it and
			// store the results in the arguments array to pass
			// them to the action
			$arguments = array();
			
			$matches = (bool)preg_match('#^' . $route . '$#', $this->uri, $arguments);
			
			array_shift($arguments);
		}
		
		return array($matches, $arguments);
	}
}