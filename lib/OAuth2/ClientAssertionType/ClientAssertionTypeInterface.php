<?php

/**
 * Interface for all OAuth2 Client Assertion Types
 */
interface OAuth2_ClientAssertionType_ClientAssertionTypeInterface
{
    public function validateRequest(OAuth2_RequestInterface $request, OAuth2_ResponseInterface $response);
    public function getClientId();
}
