<?php

/**
 * This middleware handles the redirect of the user during login/logout process during saml authentication
 * I relies on that the target for redirecting is passed via RelayState parameter during ACS call from saml server towards
 * TYPO3.
 */

namespace Netresearch\NrSamlAuth\Middleware;


use OneLogin\Saml2\Error;
use OneLogin\Saml2\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;


class DeepLinkSsoMiddleware implements MiddlewareInterface
{

    /**
     * Process the redirect after login/logout if necessary.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     * @throws Error
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isResponsible($request)) {
            return  $handler->handle($request);
        }

        $this->handleSamlRedirectIfRequired();


        return  $handler->handle($request);
    }

    /**
     * Returns true, if we handle a saml login or logout request.
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    private function isResponsible(ServerRequestInterface $request)
    {
        if ($this->isSamlLoginRequest($request)) {
            return true;
        }

        if ($this->isSamlLogoutRequest()) {
            return true;
        }
        return false;
    }

    /**
     * Returns the passed redirect target.
     *
     * @return mixed|void
     */
    private function  getRedirectTarget()
    {
        if (!isset($_REQUEST['RelayState'])) {
            return;
        }

        return $_REQUEST['RelayState'];
    }

    /**
     * Process the redirect for the given target.
     *
     * @return void
     *
     * @throws \OneLogin\Saml2\Error
     */
    private function handleSamlRedirectIfRequired()
    {
        $target = $this->getRedirectTarget();

        if (empty($target)) {
            return;
        }
        Utils::redirect($target);
    }


    /**
     * Returns true, if the current request is sent from saml server towards TYPO3 as login request.
     *
     * @param ServerRequestInterface $request the request
     * @return bool
     */
    private function isSamlLoginRequest(ServerRequestInterface $request)
    {
        return $request->getMethod() == 'POST' && isset($_POST['RelayState']);
    }

    /**
     * Returns true, if the current request is sent from saml server towards TYPO3 as logout request.
     *
     * @return bool
     */
    private function isSamlLogoutRequest()
    {
        return isset($_GET['logintype']) && $_GET['logintype'] == 'logout' && isset($_GET['RelayState']) && isset($_GET['SAMLResponse']);
    }
}
