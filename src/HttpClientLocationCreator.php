<?php
namespace Germania\ClientIpLocation;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LogLevel;
use Psr\Log\LoggerAwareTrait;

class HttpClientLocationCreator
{
    use LoggerAwareTrait;

    /**
     * @var ClientInterface
     */
    protected $client;


    /**
     * @var RequestFactoryInterface
     */
    public $request_factory;


    /**
     * Geocoder API endpoint.
     *
     * @var string
     */
    public $api;


    /**
     * IP adress query parameter
     *
     * @var string
     */
    public $ip_var_name = "ip";


    /**
     * @var callable
     */
    public $response_decoder;


    /**
     * @var mixed
     */
    public $default_location;


    /**
     * @var string
     */
    protected $error_loglevel = LogLevel::ERROR;


    /**
     * @param string                  $api              Geocoder API endpoint
     * @param ClientInterface         $client           PSR-18 HTTP Client
     * @param RequestFactoryInterface $request_factory  PSR-17 Request factory
     * @param callable|null           $response_decoder Optional: PSR-7 Response decoder callable
     */
    public function __construct( string $api, ClientInterface $client, RequestFactoryInterface $request_factory, callable $response_decoder = null, LoggerInterface $logger = null)
    {
        $this->setApiEndpoint( $api )
             ->setClient($client)
             ->setRequestFactory($request_factory)
             ->setResponseDecoder($response_decoder)
             ->setLogger( $logger ?: new NullLogger);
    }



    /**
     * @param  string $client_ip Client IP address
     */
    public function __invoke( string $client_ip )
    {
        $request = $this->createRequest($client_ip);

        try {
            $response = $this->client->sendRequest( $request);
            return $this->decodeResponse($response);
        }
        catch (ClientExceptionInterface $e) {
            $msg = sprintf("HttpClientLocationCreator: %s", $e->getMessage());
            $msg_location = sprintf("%s:%s", $e->getFile(), $e->getLine());
            $this->logger->log( $this->error_loglevel, $msg, [
                'type' => get_class($e),
                'code' => $e->getCode(),
                'location' => $msg_location,
                'apiEndpoint' => $this->api,
                'clientIp' => $client_ip
            ]);

            return $this->default_location;
        }
    }



    /**
     * @param  string $client_ip Client IP address
     * @return RequestInterface
     */
    public function createRequest( string $client_ip ) : RequestInterface
    {
        $client_ip_urlencoded = urlencode($client_ip);
        $query_parameter_field = "{{" . $this->ip_var_name . "}}";

        $api = str_replace($query_parameter_field, $client_ip_urlencoded, $this->api);

        return $this->request_factory->createRequest("GET", $api);
    }



    /**
     * @param  ResponseInterface $response
     * @return mixed
     */
    public function decodeResponse(ResponseInterface $response)
    {
        return $this->response_decoder
        ? ($this->response_decoder)($response)
        : json_decode($response->getBody(), "assoc");
    }



    /**
     * Sets the default location to return on error
     *
     * @param mixed $location
     */
    public function setDefaultLocation( $location ) : self
    {
        $this->default_location = $location;
        return $this;
    }


    /**
     * @param string $error_loglevel PSR-3 Loglevel name
     */
    public function setErrorLoglevel( string $error_loglevel ) {
        $this->error_loglevel = $error_loglevel;
        return $this;
    }


    /**
     * Sets the API endpoint
     *
     * @param string $api
     */
    public function setApiEndpoint( string $api ) : self
    {
        $this->api = $api;
        return $this;
    }



    /**
     * Sets the HTTP Client to use.
     *
     * @param ClientInterface $client
     */
    public function setClient( ClientInterface $client ) : self
    {
        $this->client = $client;
        return $this;
    }


    /**
     * Sets the PSR-17 Request factory
     *
     * @param RequestFactoryInterface $request_factory
     */
    public function setRequestFactory( RequestFactoryInterface $request_factory ) : self
    {
        $this->request_factory = $request_factory;
        return $this;
    }


    /**
     * Sets the PSR-7 Response decoder callable.
     *
     * @param callable $response_decoder
     */
    public function setResponseDecoder( callable $response_decoder = null ) : self
    {
        $this->response_decoder = $response_decoder;
        return $this;
    }

}
