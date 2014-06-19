<?php

use Pinleague\Pinterest\Transports\CurlAdapter;

/**
 * Class CurlAdapterTest
 */
class CurlAdapterTest extends TestCase
{
    /**
     * The Curl object we are mocking
     *
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    public $curl_stub;

    /**
     * @author  Will
     */
    public function __construct()
    {
        parent::__construct();

        /*
         * Setup the mocked curl stub. A stub is used here so we don't actually
         * make API calls. We want to test OUR code not THEIR code. So we don't
         * actually care what their response is, just that when we get it we do
         * with it what we should
         */
        $this->curl_stub = $this->getMock('\Pinleague\Curl');

        $this->adapter = new CurlAdapter($this->curl_stub);
    }

    /**
     * Test getting a URL with a good API connection
     *
     * @author       Will
     *
     * @dataProvider providerTestMakeRequestsReturnsJson
     */
    public function testMakeRequestReturnsArray()
    {
        /*
         * expects() == how many times we expect this method to run
         * We pass in $this->any() to say it can run as many times as it needs
         * to. Alternatively, we could have passed $this->once() if it is only
         * supposed to run once
         */
        $expected_return_value = array('success' => true);

        $this->curl_stub->expects($this->any())
                        ->method('curl_exec')
                        ->will(
                        $this->returnValue(
                             json_encode($expected_return_value)
                        )
            );

        $result = $this->adapter->makeRequest('http://example.com');

        $this->assertEquals($result,$expected_return_value);
    }

    /**
     * @expectedException Pinleague\Pinterest\Transports\TransportException
     */
    public function testBadCurlThrowsException()
    {
        $this->curl_stub->expects($this->any())
                        ->method('curl_errno')
                        ->will(
                        $this->returnValue(
                             CURLE_RECV_ERROR
                        )
            );

        $this->curl_stub->expects($this->any())
                        ->method('curl_error')
                        ->will(
                        $this->returnValue(
                             'This is a test error'
                        )
            );

        $this->adapter->makeRequest('api.example.com');

    }

    /**
     * @author  Will
     * @expectedException Pinleague\Pinterest\Transports\TransportException
     */
    public function testEmptyUrlListThrowsException()
    {

        $this->adapter->makeBatchRequests(array());
    }

    /**
     * @author  Will
     */
    public function testErroredBatchRequestsReturnArray()
    {

        $this->curl_stub->expects($this->any())
                        ->method('curl_errno')
                        ->will(
                        $this->returnValue(
                             CURLE_RECV_ERROR
                        )
            );

        $this->curl_stub->expects($this->any())
                        ->method('curl_error')
                        ->will(
                        $this->returnValue(
                             'This is a test error'
                        )
            );


        $array = $this->adapter->makeBatchRequests(
                               array(
                                    'url1',
                                    'url2',
                               )
        );

        foreach ($array as $curl_request) {
            $this->assertArrayHasKey('error', $curl_request);
            $this->assertArrayHasKey('content', $curl_request);
        }
    }

    /**
     * @author  Will
     */
    public function testSuccessfulBatchRequestsReturnArray()
    {

        $array = $this->adapter->makeBatchRequests(
                               array(
                                    'url1',
                                    'url2',
                               )
        );

        foreach ($array as $curl_request) {
            $this->assertArrayHasKey('error', $curl_request);
            $this->assertArrayHasKey('content', $curl_request);
        }
    }
}