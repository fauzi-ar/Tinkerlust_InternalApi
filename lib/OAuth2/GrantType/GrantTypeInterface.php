<?php

/**
 * Interface for all OAuth2 Grant Types
 */
interface OAuth2_GrantType_GrantTypeInterface
{
    public function getQuerystringIdentifier();
    public function validateRequest(OAuth2_RequestInterface $request, OAuth2_ResponseInterface $response);
    public function getClientId();
    public function getUserId();
    public function getScope();
    public function createAccessToken(OAuth2_ResponseType_AccessTokenInterface $accessToken, $client_id, $user_id, $scope);
}
