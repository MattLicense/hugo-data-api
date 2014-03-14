<?php
/**
 * TokenTypeInterface.php
 * data-api
 * @author: Matt
 * @date:   2013/12
 */

namespace Hugo\Data\OAuth\Token;

use Hugo\Data\Model\ModelInterface;
use Hugo\Data\Model\User;

interface TokenTypeInterface extends ModelInterface {

    /**
     * Assigns the user to a token
     *
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * Returns a string of current Token Type
     *
     * @return string
     */
    public function getTokenType();

    /**
     * Generates, saves and returns a token
     *
     * @return string
     */
    public function generateToken();

    /**
     * Used to verify a token
     *
     * @param $token
     * @param $controller
     * @return bool
     */
    public function verifyToken($token, $controller);

    /**
     * Returns the OAuth token
     *
     * @return string
     */
    public function getToken();

} 