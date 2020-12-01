<?php
namespace Germania\ClientIpLocation;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;


/**
 * Stores the client's location in the Request.
 *
 * The location is determined using the client IP coming from a request attribute
 * and a location factory callable passed to the constructor.
 *
 * The client location is not special kind of data;
 * any kind of location information coming from the location factory
 * will be stored in the request attribute.
 */
class ClientIpLocationMiddleware implements MiddlewareInterface
{


    /**
     * Callable that determines Location from Client IP address
     *
     * @var callable
     */
    public $location_factory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;


    /**
     * PSR-3 Loglevel name for errors
     * @var string
     */
    protected $error_loglevel = LogLevel::ERROR;


    /**
     * Request attribute where the canonical URI shall be stored in.
     * @var string
     */
    public $client_location_attribute_name = "X-client-location";


    /**
     * Request attribute where the Client IP is stored in.
     * @var string
     */
    public $client_ip_attribute_name = 'client-ip';




    /**
     * @param callable        $location_factory   Location factory
     * @param LoggerInterface $logger             PSR-3 Logger for errors
     * @param LogLevel        $error_loglevel     Optional: PSR-3 Loglevel name for errors (defaults to `error`)
     */
    public function __construct( callable $location_factory, LoggerInterface $logger, string $error_loglevel = LogLevel::ERROR)
    {
        $this->location_factory = $location_factory;
        $this->error_loglevel = $error_loglevel;
        $this->logger = $logger;
    }



    /**
     * Single-pass (PSR-15 style)
     *
     * @param  ServerRequestInterface  $request
     * @param  RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        $client_ip = $request->getAttribute( $this->client_ip_attribute_name ) ?: null;

        if (empty($client_ip)) {
            return $handler->handle($request);
        }


        try {
            $location = ($this->location_factory)( $client_ip );
            $request = $request->withAttribute($this->client_location_attribute_name, $location);
        }
        catch (\Throwable $e) {
            $msg = sprintf("ClientIpLocationMiddleware: %s", $e->getMessage());
            $msg_location = sprintf("%s:%s", $e->getFile(), $e->getLine());
            $this->logger->log( $this->error_loglevel, $msg, [
                'exception' => get_class($e),
                'location' => $msg_location,
                'clientIp' => $client_ip
            ]);
        }


        return $handler->handle($request);
    }



    /**
     * @param string $attr_name Client IP Attribute Name
     */
    public function setClientIpAttributeName( string $attr_name ) : self
    {
        $this->client_ip_attribute_name = $attr_name;
        return $this;
    }



    /**
     * @param string $attr_name Client Location Attribute Name
     */
    public function setClientLocationAttributeName( string $attr_name ) : self
    {
        $this->client_location_attribute_name = $attr_name;
        return $this;
    }


}
