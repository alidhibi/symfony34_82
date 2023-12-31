<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Guard\Firewall;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Guard\AuthenticatorInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Guard\GuardAuthenticatorInterface;
use Symfony\Component\Security\Guard\Token\PreAuthenticationGuardToken;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;

/**
 * Authentication listener for the "guard" system.
 *
 * @author Ryan Weaver <ryan@knpuniversity.com>
 * @author Amaury Leroux de Lens <amaury@lerouxdelens.com>
 */
class GuardAuthenticationListener implements ListenerInterface
{
    private readonly \Symfony\Component\Security\Guard\GuardAuthenticatorHandler $guardHandler;

    private readonly \Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface $authenticationManager;

    private $providerKey;

    private $guardAuthenticators;

    private ?\Psr\Log\LoggerInterface $logger = null;

    private ?\Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface $rememberMeServices = null;

    private $hideUserNotFoundExceptions;

    /**
     * @param GuardAuthenticatorHandler         $guardHandler          The Guard handler
     * @param AuthenticationManagerInterface    $authenticationManager An AuthenticationManagerInterface instance
     * @param string                            $providerKey           The provider (i.e. firewall) key
     * @param iterable|AuthenticatorInterface[] $guardAuthenticators   The authenticators, with keys that match what's passed to GuardAuthenticationProvider
     * @param LoggerInterface                   $logger                A LoggerInterface instance
     */
    public function __construct(GuardAuthenticatorHandler $guardHandler, AuthenticationManagerInterface $authenticationManager, $providerKey, $guardAuthenticators, LoggerInterface $logger = null, $hideUserNotFoundExceptions = true)
    {
        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->guardHandler = $guardHandler;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey = $providerKey;
        $this->guardAuthenticators = $guardAuthenticators;
        $this->logger = $logger;
        $this->hideUserNotFoundExceptions = $hideUserNotFoundExceptions;
    }

    /**
     * Iterates over each authenticator to see if each wants to authenticate the request.
     */
    public function handle(GetResponseEvent $event): void
    {
        if ($this->logger instanceof \Psr\Log\LoggerInterface) {
            $context = ['firewall_key' => $this->providerKey];

            if ($this->guardAuthenticators instanceof \Countable || \is_array($this->guardAuthenticators)) {
                $context['authenticators'] = \count($this->guardAuthenticators);
            }

            $this->logger->debug('Checking for guard authentication credentials.', $context);
        }

        foreach ($this->guardAuthenticators as $key => $guardAuthenticator) {
            // get a key that's unique to *this* guard authenticator
            // this MUST be the same as GuardAuthenticationProvider
            $uniqueGuardKey = $this->providerKey.'_'.$key;

            $this->executeGuardAuthenticator($uniqueGuardKey, $guardAuthenticator, $event);

            if ($event->hasResponse()) {
                if ($this->logger instanceof \Psr\Log\LoggerInterface) {
                    $this->logger->debug('The "{authenticator}" authenticator set the response. Any later authenticator will not be called', ['authenticator' => \get_class($guardAuthenticator)]);
                }

                break;
            }
        }
    }

    private function executeGuardAuthenticator(string $uniqueGuardKey, GuardAuthenticatorInterface $guardAuthenticator, GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        try {
            // abort the execution of the authenticator if it doesn't support the request
            if ($guardAuthenticator instanceof AuthenticatorInterface) {
                if ($this->logger instanceof \Psr\Log\LoggerInterface) {
                    $this->logger->debug('Checking support on guard authenticator.', ['firewall_key' => $this->providerKey, 'authenticator' => \get_class($guardAuthenticator)]);
                }

                if (!$guardAuthenticator->supports($request)) {
                    if ($this->logger instanceof \Psr\Log\LoggerInterface) {
                        $this->logger->debug('Guard authenticator does not support the request.', ['firewall_key' => $this->providerKey, 'authenticator' => \get_class($guardAuthenticator)]);
                    }

                    return;
                }

                // as there was a support for given request,
                // authenticator is expected to give not-null credentials.
                $credentialsCanBeNull = false;
            } else {
                // deprecated since version 3.4, to be removed in 4.0
                $credentialsCanBeNull = true;
            }

            if ($this->logger instanceof \Psr\Log\LoggerInterface) {
                $this->logger->debug('Calling getCredentials() on guard authenticator.', ['firewall_key' => $this->providerKey, 'authenticator' => \get_class($guardAuthenticator)]);
            }

            // allow the authenticator to fetch authentication info from the request
            $credentials = $guardAuthenticator->getCredentials($request);

            if (null === $credentials) {
                // deprecated since version 3.4, to be removed in 4.0
                if ($credentialsCanBeNull) {
                    return;
                }

                if ($guardAuthenticator instanceof AbstractGuardAuthenticator) {
                    @trigger_error(sprintf('Returning null from "%1$s::getCredentials()" is deprecated since Symfony 3.4 and will throw an \UnexpectedValueException in 4.0. Return false from "%1$s::supports()" instead.', \get_class($guardAuthenticator)), \E_USER_DEPRECATED);

                    return;
                }

                throw new \UnexpectedValueException(sprintf('The return value of "%1$s::getCredentials()" must not be null. Return false from "%1$s::supports()" instead.', \get_class($guardAuthenticator)));
            }

            // create a token with the unique key, so that the provider knows which authenticator to use
            $token = new PreAuthenticationGuardToken($credentials, $uniqueGuardKey);

            if ($this->logger instanceof \Psr\Log\LoggerInterface) {
                $this->logger->debug('Passing guard token information to the GuardAuthenticationProvider', ['firewall_key' => $this->providerKey, 'authenticator' => \get_class($guardAuthenticator)]);
            }

            // pass the token into the AuthenticationManager system
            // this indirectly calls GuardAuthenticationProvider::authenticate()
            $token = $this->authenticationManager->authenticate($token);

            if ($this->logger instanceof \Psr\Log\LoggerInterface) {
                $this->logger->info('Guard authentication successful!', ['token' => $token, 'authenticator' => \get_class($guardAuthenticator)]);
            }

            // sets the token on the token storage, etc
            $this->guardHandler->authenticateWithToken($token, $request, $this->providerKey);
        } catch (AuthenticationException $authenticationException) {
            // oh no! Authentication failed!

            if ($this->logger instanceof \Psr\Log\LoggerInterface) {
                $this->logger->info('Guard authentication failed.', ['exception' => $authenticationException, 'authenticator' => \get_class($guardAuthenticator)]);
            }

            // Avoid leaking error details in case of invalid user (e.g. user not found or invalid account status)
            // to prevent user enumeration via response content
            if ($this->hideUserNotFoundExceptions && ($authenticationException instanceof UsernameNotFoundException || $authenticationException instanceof AccountStatusException)) {
                $authenticationException = new BadCredentialsException('Bad credentials.', 0, $authenticationException);
            }

            $response = $this->guardHandler->handleAuthenticationFailure($authenticationException, $request, $guardAuthenticator, $this->providerKey);

            if ($response instanceof Response) {
                $event->setResponse($response);
            }

            return;
        }

        // success!
        $response = $this->guardHandler->handleAuthenticationSuccess($token, $request, $guardAuthenticator, $this->providerKey);
        if ($response instanceof Response) {
            if ($this->logger instanceof \Psr\Log\LoggerInterface) {
                $this->logger->debug('Guard authenticator set success response.', ['response' => $response, 'authenticator' => \get_class($guardAuthenticator)]);
            }
            $event->setResponse($response);
        } elseif ($this->logger instanceof \Psr\Log\LoggerInterface) {
            $this->logger->debug('Guard authenticator set no success response: request continues.', ['authenticator' => \get_class($guardAuthenticator)]);
        }

        // attempt to trigger the remember me functionality
        $this->triggerRememberMe($guardAuthenticator, $request, $token, $response);
    }

    /**
     * Should be called if this listener will support remember me.
     */
    public function setRememberMeServices(RememberMeServicesInterface $rememberMeServices): void
    {
        $this->rememberMeServices = $rememberMeServices;
    }

    /**
     * Checks to see if remember me is supported in the authenticator and
     * on the firewall. If it is, the RememberMeServicesInterface is notified.
     */
    private function triggerRememberMe(GuardAuthenticatorInterface $guardAuthenticator, Request $request, TokenInterface $token, Response $response = null): void
    {
        if (!$this->rememberMeServices instanceof \Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface) {
            if ($this->logger instanceof \Psr\Log\LoggerInterface) {
                $this->logger->debug('Remember me skipped: it is not configured for the firewall.', ['authenticator' => \get_class($guardAuthenticator)]);
            }

            return;
        }

        if (!$guardAuthenticator->supportsRememberMe()) {
            if ($this->logger instanceof \Psr\Log\LoggerInterface) {
                $this->logger->debug('Remember me skipped: your authenticator does not support it.', ['authenticator' => \get_class($guardAuthenticator)]);
            }

            return;
        }

        if (!$response instanceof Response) {
            throw new \LogicException(sprintf('"%s::onAuthenticationSuccess()" *must* return a Response if you want to use the remember me functionality. Return a Response, or set remember_me to false under the guard configuration.', \get_class($guardAuthenticator)));
        }

        $this->rememberMeServices->loginSuccess($request, $response, $token);
    }
}
