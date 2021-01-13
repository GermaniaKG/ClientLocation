<?php
namespace tests;

use Germania\ClientIpLocation\ClientIpLocationMiddleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use Nyholm\Psr7\Factory\Psr17Factory;

class ClientIpLocationMiddlewareTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait,
        LoggerTrait;



    public function testInstantiation()
    {

        $fn = function() {};
        $log = $this->getLogger();

        $sut = new ClientIpLocationMiddleware( $fn, $log );

        $this->assertInstanceOf(MiddlewareInterface::class, $sut);

        return $sut;
    }


    /**
     * @depends testInstantiation
     */
    public function testClientIpAttributeNameSetter( $sut )
    {

        $res = $sut->setClientIpAttributeName("foo");

        $this->assertInstanceOf(ClientIpLocationMiddleware::class, $res);
        $this->assertInstanceOf(MiddlewareInterface::class, $res);

        return $sut;
    }


    /**
     * @depends testInstantiation
     */
    public function testClientLocationAttributeNameSetter( $sut )
    {

        $res = $sut->setClientLocationAttributeName("foo");

        $this->assertInstanceOf(ClientIpLocationMiddleware::class, $res);
        $this->assertInstanceOf(MiddlewareInterface::class, $res);

        return $sut;
    }




    /**
     * Test PSR-15 Single Pass middleware.
     * Calling middleware's process method must yield ResponseInterface.
     *
     * @dataProvider provideClientIpData
     */
    public function testSinglePass( $client_ip, $client_location )
    {
        $client_ip_attr_name = "clientIpAA";
        $client_loc_attr_name = "clientLocationAA";

        $fn = function() use ($client_location) { return $client_location; };
        $log = $this->getLogger();

        $sut = new ClientIpLocationMiddleware( $fn, $log );
        $sut->setClientIpAttributeName($client_ip_attr_name);
        $sut->setClientLocationAttributeName($client_loc_attr_name);

        $result_request = (new Psr17Factory)->createServerRequest("GET", "https://httpbin.org/");

        $request_mock = $this->prophesize(ServerRequestInterface::class);
        $request_mock->getAttribute(Argument::exact($client_ip_attr_name))->willReturn( $client_ip);
        $request_mock->withAttribute(Argument::exact($client_loc_attr_name), Argument::exact("TheLocation"))->willReturn( $result_request );
        $request = $request_mock->reveal();

        $response = (new Psr17Factory)->createResponse(200);

        $handler_mock = $this->prophesize( RequestHandlerInterface::class );
        $handler_mock->handle( Argument::type(ServerRequestInterface::class) )->willReturn( $response );
        $handler = $handler_mock->reveal();

        $result_response = $sut->process($request, $handler);
        $this->assertInstanceOf(ResponseInterface::class, $result_response);

        return $sut;
    }



    public function testExceptionInLocationFactory( )
    {
        $client_ip = "127.0.0.1";
        $client_ip_attr_name = "clientIpBB";
        $client_loc_attr_name = "clientLocationBB";

        $fn = function() {
            throw new \RuntimeException("Huh!");
        };

        $sut = new ClientIpLocationMiddleware( $fn, $this->getLogger());
        $sut->setClientIpAttributeName($client_ip_attr_name);
        $sut->setClientLocationAttributeName($client_loc_attr_name);
        $sut->setLocationFactory($fn);

        $request_mock = $this->prophesize(ServerRequestInterface::class);
        $request_mock->getAttribute(Argument::exact($client_ip_attr_name))->willReturn( $client_ip);

        $request = $request_mock->reveal();

        $response = (new Psr17Factory)->createResponse(200);

        $handler_mock = $this->prophesize( RequestHandlerInterface::class );
        $handler_mock->handle( Argument::type(ServerRequestInterface::class) )->willReturn( $response );
        $handler = $handler_mock->reveal();

        $result_response = $sut->process($request, $handler);
        $this->assertInstanceOf(ResponseInterface::class, $result_response);
    }





    public function provideClientIpData() {
        return array(
            [ "127.0.0.1", "TheLocation"],
            [ null, null ],
        );
    }

}

