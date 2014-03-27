<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 22/03/14
 * Time: 18:54
 */

namespace Hugo\Data\OAuth\Token;


class TokenFactoryTest extends \PHPUnit_Framework_TestCase {

    protected $tokenFactory;

    protected $tokenTypes = ['bearer'];

    public function setUp()
    {
        $store = $this->getMockBuilder('\\Hugo\\Data\\Storage\\DB\\MySQL')->disableOriginalConstructor()->getMock();
        $this->tokenFactory = new TokenFactory($store);
    }

    public function testTokenGenerated()
    {
        $user = $this->getMockBuilder('\\Hugo\\Data\\Model\\User')
                     ->disableOriginalConstructor()
                     ->getMock();

        foreach($this->tokenTypes as $type) {
            $token = $this->tokenFactory->getToken($type, $user);
            $this->assertInstanceOf('\\Hugo\\Data\\OAuth\\Token\\TokenTypeInterface', $token);
        }
    }

    public function testBearerTokenGenerated()
    {
        $user = $this->getMockBuilder('\\Hugo\\Data\\Model\\User')
                     ->disableOriginalConstructor()
                     ->getMock();

        $token = $this->tokenFactory->getToken('bearer', $user);
        $this->assertInstanceOf('\\Hugo\\Data\\OAuth\\Token\\Bearer', $token);
    }

    public function testExceptionOnBadTokenType()
    {
        $badTokenType = 'undefined';

        $this->setExpectedException(
            '\\Hugo\\Data\\Exception\\InvalidTokenException',
            'Token type ' . $badTokenType .' not implemented',
            501
        );
        $user = $this->getMockBuilder('\\Hugo\\Data\\Model\\User')
                     ->disableOriginalConstructor()
                     ->getMock();

        $token = $this->tokenFactory->getToken($badTokenType, $user);
    }

}
 