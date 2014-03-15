<?php
/**
 * BearerTest.php
 * data-api
 * @author: Matt
 * @date:   2014/02
 */

namespace Hugo\Data\OAuth\Token;
require_once(__DIR__."/../../../../../vendor/autoload.php");


class BearerTest extends \PHPUnit_Framework_TestCase {

    protected $store;
    protected $user;

    public function setUp()
    {
        $this->store = $this->getMockBuilder('\\Hugo\\Data\\Storage\\DB\\MySQL', ['create'])
                            ->disableOriginalConstructor()
                            ->getMock();
        $this->user = $this->getMockBuilder('\\Hugo\\Data\\Model\\User', [''])
                           ->disableOriginalConstructor()
                           ->getMock();

        $this->store->expects($this->any())
                    ->method('create')
                    ->will($this->returnValue(true));
    }

    public function testTokenType()
    {
        $token = new Bearer($this->store);
        $this->assertEquals('bearer', $token->getTokenType());
    }

    public function testGenerateToken()
    {
        $token = new Bearer($this->store);
        $token->setUser($this->user);

        $this->assertTrue($token->generateToken());
        $this->assertEquals(32, strlen($token->getToken()));
        $date = new \DateTime('2 hours');
        $this->assertEquals($date->format('Y-m-d H:i:s'), $token->getExpiry());
    }

    public function testToArray()
    {
        $token = new Bearer($this->store);
        $token->setUser($this->user);


        $date = new \DateTime('2 hours');
        $this->assertTrue($token->generateToken());

        $tokenArray = $token->toArray();
        $this->assertArrayHasKey('token_type', $tokenArray);
        $this->assertEquals('1', $tokenArray['token_type']);

        $this->assertArrayHasKey('token', $tokenArray);

        $this->assertArrayHasKey('expires', $token->toArray());
        $this->assertEquals($date->format('Y-m-d H:i:s'), $tokenArray['expires']);
    }

}
 