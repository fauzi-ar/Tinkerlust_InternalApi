<?php

/**
 *
 * @author Brent Shaffer <bshafs at gmail dot com>
 */
interface OAuth2_ResponseType_AccessTokenInterface extends OAuth2_ResponseType_ResponseTypeInterface
{
    /**
     * Handle the creation of access token, also issue refresh token if supported / desirable.
     *
     * @param $client_id                client identifier related to the access token.
     * @param $user_id                  user ID associated with the access token
     * @param $scope                    OPTONAL scopes to be stored in space-separated string.
     * @param bool $includeRefreshToken if true, a new refresh_token will be added to the response
     *
     * @see http://tools.ietf.org/html/rfc6749#section-5
     * @ingroup oauth2_section_5
     */
    public function createAccessToken($client_id, $user_id, $scope = null, $includeRefreshToken = true);

    /**
     * Handle the revoking of refresh tokens, and access tokens if supported / desirable
     *
     * @param $token
     * @param $tokenTypeHint
     * @return mixed
     *
     * @todo v2.0 include this method in interface. Omitted to maintain BC in v1.x
     */
    //public function revokeToken($token, $tokenTypeHint);
}