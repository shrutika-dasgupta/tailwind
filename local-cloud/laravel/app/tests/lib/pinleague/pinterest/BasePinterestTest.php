<?php

use Pinleague\Pinterest\BasePinterest,
    Mockery as M;

/**
 * Class PinterestTest
 * Testing suite for the Tailwind Pinterest Class
 *
 * @author  Will
 */
class BasePinterestTest extends TestCase
{

    /**
     * @var BasePinterest
     */
    protected $base_pinterest;

    /**
     * @author  Will
     */
    public function testGetInstanceReturnsInstance()
    {
        $transport
            = M::mock('\Pinleague\Pinterest\Transports\TransportInterface');

        $instance = BasePinterest::getInstance(
                                 'fake_client_id',
                                 'fake_secret',
                                 $transport
        );

        $this->assertInstanceOf(
             '\Pinleague\Pinterest\BasePinterest', $instance
        );
    }

    /**
     * @author  Will
     */
    public function testGetBoardInformationReturnsArray()
    {

        $expected = array('mocked_board_data' => 'board_data');

        $transport
            = M::mock('\Pinleague\Pinterest\Transports\TransportInterface');

        $transport
            ->shouldReceive('makeRequest')
            ->once()
            ->andReturn(
            array('code' => 0, 'data' => $expected)
            );

        $this->base_pinterest = new BasePinterest(
            'fake_client_id', 'fake_secret', $transport
        );

        $array = $this->base_pinterest->getBoardInformation('137438953493');
        $this->assertEquals($array, $expected);

    }

    /**
     * @author  Will
     */
    public function testMethodsThrowPinterestExceptionOnErrorCode40()
    {

        $transport
            = M::mock('\Pinleague\Pinterest\Transports\TransportInterface');

        $transport->shouldReceive('makeRequest')
                  ->once()
                  ->andReturn(
                  array(
                       'code'    => 40,
                       'message' => null,
                       'data'    => null,
                       'host'    => null,
                  )
            );

        $this->base_pinterest = new BasePinterest(
            'fake_client_id', 'fake_secret', $transport
        );

        try {

            $this->base_pinterest->getBoardInformation('137438953493');
        }
        catch (\Pinleague\Pinterest\PinterestBoardNotFoundException $e) {
            return;
        }

        $this->fail('PinterestBoardNotFoundException not thrown');

    }

    public function testPassingNonArrayToGetBoardInformationThrowsException()
    {

        try {
            $this->base_pinterest = new BasePinterest(
                'fake_client_id', 'fake_secret', null
            );

            $this->base_pinterest->getBoardInformation('some id', 'not an array');
        }
        catch (\Pinleague\Pinterest\BasePinterestException $e) {
            return;
        }

        $this->fail('There was no exception thrown');

    }
}