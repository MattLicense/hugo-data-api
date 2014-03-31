<?php
/**
 * AbstractController.php
 * data-api
 * @author: Matt
 * @date:   2013/12
 */

namespace Hugo\Data\Controller;

use Hugo\Data\Application\Logger,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Hugo\Data\Exception\BadMethodCallException;

/**
 * Class AbstractController
 * @package Hugo\Data\Controller
 */
abstract class AbstractController implements ControllerInterface {

    protected $request;
    protected $log;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->log = new Logger();

        // if the posted data is JSON, decode it into the request content array
        if (in_array($this->request->getMethod(), ["POST", "PUT"]) && 0 === strpos($this->request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($this->request->getContent(), true);
            $this->request->request->replace(is_array($data) ? $data : array());
        }
    }

    final public function handle()
    {
        $pathArray = explode('/', trim($this->request->getPathInfo(), '/'));
        $controller = array_shift($pathArray);  // first part of URI should be the controller
        $method = array_shift($pathArray);      // next part should be the method
        if(null === $method) {
            $method = 'index';
        }

        // check the HTTP request method
        $httpMethod = strtolower($this->request->getMethod());

        // ensure OPTIONS requests are okay
        if($httpMethod == 'options') {
            return new Response('', Constants::HTTP_NO_CONTENT, ['Content-Type' => Constants::CONTENT_TYPE]);
        }

        // add the HTTP request method to make the function call, e.g., postAuth
        $method = $httpMethod.ucfirst($method);

        // make sure that the method exists
        if(!is_callable([$this,$method])) {
            $this->log->error(sprintf("Route %s %s not defined", $this->request->getMethod(), $this->request->getRequestUri()));
            throw new BadMethodCallException(
                sprintf("Route %s %s not defined", $this->request->getMethod(), $this->request->getRequestUri()),
                Constants::HTTP_NOT_FOUND
            );
        }

        // using array_shift above pops the first two elements from the array
        // so the remaining parameters are treated as function arguments
        $this->log->debug('Controller action {class}@{method}', ['class' => get_class($this), 'method' => $method]);
        return call_user_func_array([$this,$method], $pathArray);
    }

} 