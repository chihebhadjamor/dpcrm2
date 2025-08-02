<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Bundle\SecurityBundle\Security;

class TwoFactorAuthListener implements EventSubscriberInterface
{
    private RequestStack $requestStack;
    private UrlGeneratorInterface $urlGenerator;
    private Security $security;

    public function __construct(
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        Security $security
    ) {
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $this->security->getUser();

        // Only proceed if the user has 2FA enabled
        if (!$user instanceof User || !$user->isIs2faEnabled() || !$user->getSecret2fa()) {
            return;
        }

        $session = $this->requestStack->getSession();

        // Check if 2FA is already verified for this session
        if ($session->get('2fa_verified') === true) {
            return;
        }

        // Redirect to 2FA verification page
        $response = new RedirectResponse(
            $this->urlGenerator->generate('app_verify_2fa')
        );

        $event->setResponse($response);
    }
}
