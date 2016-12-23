<?php

/**
 * @see OAuth2\Controller\UserInfoControllerInterface
 */
class OAuth2_OpenID_Controller_UserInfoController extends OAuth2_Controller_ResourceController implements OAuth2_OpenID_Controller_UserInfoControllerInterface
{
    private $token;

    protected $tokenType;
    protected $tokenStorage;
    protected $userClaimsStorage;
    protected $config;
    protected $scopeUtil;

    public function __construct(OAuth2_TokenType_TokenTypeInterface $tokenType, OAuth2_Storage_AccessTokenInterface $tokenStorage, OAuth2_OpenID_Storage_UserClaimsInterface $userClaimsStorage, $config = array(), OAuth2_ScopeInterface $scopeUtil = null)
    {
        $this->tokenType = $tokenType;
        $this->tokenStorage = $tokenStorage;
        $this->userClaimsStorage = $userClaimsStorage;

        $this->config = array_merge(array(
            'www_realm' => 'Service',
        ), $config);

        if (is_null($scopeUtil)) {
            $scopeUtil = new OAuth2_Scope();
        }
        $this->scopeUtil = $scopeUtil;
    }

    public function handleUserInfoRequest(OAuth2_RequestInterface $request, OAuth2_ResponseInterface $response)
    {
        if (!$this->verifyResourceRequest($request, $response, 'openid')) {
            return;
        }

        $token = $this->getToken();
        $claims = $this->userClaimsStorage->getUserClaims($token['user_id'], $token['scope']);
        // The sub Claim MUST always be returned in the UserInfo Response.
        // http://openid.net/specs/openid-connect-core-1_0.html#UserInfoResponse
        $claims += array(
            'sub' => $token['user_id'],
        );
        $response->addParameters($claims);
    }
}
