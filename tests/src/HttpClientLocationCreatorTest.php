<?php
namespace tests;

use Germania\ClientIpLocation\HttpClientLocationCreator;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use Nyholm\Psr7\Factory\Psr17Factory;


class HttpClientLocationCreatorTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait,
        LoggerTrait;


    public function testInstantiation()
    {
        $api = "https://httpbin.org?test={{ip}}";

        $client_mock = $this->prophesize(ClientInterface::class);
        $client = $client_mock->reveal();

        $request_factory = new Psr17Factory;

        $logger = $this->getLogger();

        $decoder = function($response) {
            return json_decode($response->getBody(), "assoc");
        };

        $sut = new HttpClientLocationCreator($api, $client, $request_factory, $decoder, $logger);
        $this->assertIsCallable($sut);

        return $sut;
    }




    /**
     * @depends testSetters
     */
    public function testInvokation( $sut )
    {
        $response = (new Psr17Factory)->createResponse(200);
        $response->getBody()->write(json_encode(['city' => "Berlin"]));

        $client_mock = $this->prophesize(ClientInterface::class);
        $client_mock->sendRequest(Argument::any())->willReturn($response);
        $client = $client_mock->reveal();
        $sut->setClient($client);

        $result = $sut->__invoke("127.0.0.1");
        $this->assertIsArray($result);
        $this->assertEquals("Berlin", $result['city']);
    }


    /**
     * @depends testSetters
     */
    public function testResponseDecoding( $sut )
    {
        $response = (new Psr17Factory)->createResponse(200);
        $response->getBody()->write(json_encode(['city' => "Berlin"]));

        $result = $sut->decodeResponse($response);
        $this->assertIsArray($result);
        $this->assertEquals("Berlin", $result['city']);
    }



    /**
     * @depends testInstantiation
     */
    public function testRequestCreation( $sut )
    {
        $request = $sut->createRequest("127.0.0.1");
        $this->assertInstanceOf(RequestInterface::class, $request);
    }



    /**
     * @depends testInstantiation
     */
    public function testSetters( $sut )
    {
        $res = $sut->setDefaultLocation("Berlin");
        $this->assertInstanceOf(HttpClientLocationCreator::class, $res);

        $res = $sut->setApiEndpoint("https://httpbin.org");
        $this->assertInstanceOf(HttpClientLocationCreator::class, $res);

        $res = $sut->setRequestFactory(new Psr17Factory);
        $this->assertInstanceOf(HttpClientLocationCreator::class, $res);

        $res = $sut->setResponseDecoder(function($response) {
            return json_decode($response->getBody(), "assoc");
        });
        $this->assertInstanceOf(HttpClientLocationCreator::class, $res);

        $client_mock = $this->prophesize(ClientInterface::class);
        $res = $sut->setClient($client_mock->reveal());
        $this->assertInstanceOf(HttpClientLocationCreator::class, $res);

        return $sut;
    }


}


