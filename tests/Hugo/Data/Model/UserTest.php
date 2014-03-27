<?php
/**
 * UserTest.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2014/02
 */

namespace Hugo\Data\Model;


class UserTest extends \PHPUnit_Framework_TestCase {

    public $goodParamBag;
    public $badParamBag;
    public $mockStore;

    public function setUp()
    {
        $this->goodParamBag = $this->getMockBuilder('\\Symfony\\Component\\HttpFoundation\\ParameterBag', ['get'])
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $this->goodParamBag->expects($this->any())
                           ->method('get')
                           ->will($this->returnCallback([$this,'parameterBagSuccess']));
        $this->badParamBag = $this->getMockBuilder('\\Symfony\\Component\\HttpFoundation\\ParameterBag', ['get'])
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $this->badParamBag->expects($this->any())
                          ->method('get')
                          ->will($this->returnCallback([$this,'parameterBagFailure']));
        $this->mockStore = $this->getMockBuilder('\\Hugo\\Data\\Storage\\DB\\MySQL', ['read'])
                                ->disableOriginalConstructor()
                                ->getMock();
        $this->mockStore->expects($this->any())
                        ->method('read')
                        ->will($this->returnValue([
                            'user_name'     => 'Anna Edwards',
                            'user_logon'    => 'a.edwards',
                            'user_secret'   => '$2y$10$JXEVzXlYNBxwBlc5xovHEegbHX0mpEjtFpt6eZRI.9yCS3Y9ekXq6',
                            'user_role'     => 2,
                            'active'        => true
                        ]));
    }

    public function parameterBagSuccess()
    {
        $args = func_get_args();

        switch($args[0]) {
            case 'user_name':
                return "Anna Edwards";
                break;
            case 'user_logon':
                return "a.edwards";
                break;
            case 'user_secret':
                // example generated using password_hash('secretPassword', PASSWORD_BCRYPT)
                return '$2y$10$JXEVzXlYNBxwBlc5xovHEegbHX0mpEjtFpt6eZRI.9yCS3Y9ekXq6';
                break;
            case 'user_role':
                return 1;
                break;
            case 'active':
                return true;
                break;
            default:
                return;
        }
    }

    public function parameterBagFailure()
    {
        $args = func_get_args();

        switch($args[0]) {
            case 'user_name':
                return "Anna Edwards";
                break;
            case 'user_secret':
                // example generated using password_hash('secretPassword', PASSWORD_BCRYPT)
                return '$2y$10$JXEVzXlYNBxwBlc5xovHEegbHX0mpEjtFpt6eZRI.9yCS3Y9ekXq6';
                break;
            case 'active':
                return true;
                break;
            default:
                return;
        }
    }

    public function testProcessParameters()
    {
        $user = new User($this->mockStore);
        $this->assertTrue($user->processParameters($this->goodParamBag));

        $this->assertEquals($this->goodParamBag->get('user_name'), $user->user_name);
        $this->assertEquals($this->goodParamBag->get('user_logon'), $user->user_logon);
        $this->assertEquals($this->goodParamBag->get('user_secret'), $user->user_secret);
        $this->assertEquals($this->goodParamBag->get('user_role'), $user->user_role);
        $this->assertEquals($this->goodParamBag->get('active'), $user->active);
    }

    /**
     * @expectedException \Hugo\Data\Exception\InvalidRequestException
     */
    public function testProcessParametersExceptionThrown()
    {
        $user = new User($this->mockStore);
        $user->processParameters($this->badParamBag);
    }

    public function testLogin()
    {
        $username = 'a.edwards';
        $password = 'secretPassword';

        $user = new User($this->mockStore);
        $this->assertTrue($user->login($username, $password));

        $password = 'thisIsTheWrongPassword';
        $user = new User($this->mockStore);
        $this->assertFalse($user->login($username, $password));
    }

    public function testVerifyUser()
    {
        $username = 'a.edwards';
        $password = 'secretPassword';
        $concatenatedUser = base64_encode($username . ':' . $password);

        $user = new User($this->mockStore);
        $this->assertTrue($user->verifyUser($concatenatedUser));
    }

}
 