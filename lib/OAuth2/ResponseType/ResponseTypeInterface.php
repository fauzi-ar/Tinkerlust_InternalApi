<?php

interface OAuth2_ResponseType_ResponseTypeInterface
{
    public function getAuthorizeResponse($params, $user_id = null);
}
