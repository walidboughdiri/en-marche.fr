<?php

namespace AppBundle\Security;

use AppBundle\Entity\Adherent;
use AppBundle\Exception\AccountNotValidatedException;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function checkPreAuth(UserInterface $user)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function checkPostAuth(UserInterface $user)
    {
        /** @var Adherent $user */
        if (!$user instanceof Adherent && $user->isEnabled()) {
            throw new \UnexpectedValueException(sprintf('You have to pass and Adherent instance.'));
        }

        if (!$user->getActivatedAt()) {
            throw new AccountNotValidatedException($user, $this->router->generate('adherent_resend_validation'));
        }

        $ex = new DisabledException('Account disabled.');
        $ex->setUser($user);
        throw $ex;
    }
}
