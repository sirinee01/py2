<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class AuthenticationListener
{
    private RouterInterface $router;
    private RequestStack $requestStack;

    public function __construct(RouterInterface $router, RequestStack $requestStack)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $authToken = $event->getAuthenticationToken();
        $user = $authToken->getUser();

        // Check if user is verified
        if ($user instanceof User && !$user->isVerified()) {
            // Clear the token to prevent login
            $this->requestStack->getCurrentRequest()->getSession()->invalidate();

            // Add flash message
            $this->requestStack->getCurrentRequest()->getSession()->getFlashBag()->add(
                'error',
                'Your email is not verified. Please verify your email before logging in.'
            );

            // Redirect to verification page
            $response = new RedirectResponse(
                $this->router->generate('app_register')
            );
            $event->stopPropagation();
            $event->setResponse($response);
        }
    }
}
