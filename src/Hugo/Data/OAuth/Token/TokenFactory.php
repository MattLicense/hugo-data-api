<?php
/**
 * TokenFactory.php
 * data-api
 * @author: Matt
 * @date:   2013/12
 */

namespace Hugo\Data\OAuth\Token;

use Hugo\Data\Exception\InvalidTokenException;
use Hugo\Data\Model\User;
use Hugo\Data\Storage\DataSource;

class TokenFactory {

    /**
     * @var \Hugo\Data\Storage\DataSource
     */
    private $store;

    /**
     * @param DataSource $store
     */
    public function __construct(DataSource $store)
    {
        $this->store = $store;
    }

    /**
     * @param $type
     * @param User $user
     * @return TokenTypeInterface
     * @throws \Hugo\Data\Exception\InvalidTokenException
     */
    public function getToken($type, User $user = null)
    {
        switch(strtolower($type)) {
            case 'bearer':
                $token = new Bearer($this->store);
                break;
            default:
                throw new InvalidTokenException("Token type {$type} not implemented", 501);
        }

        if($token != null) {
            $token->setUser($user);
        }
        
        return $token;
    }

} 