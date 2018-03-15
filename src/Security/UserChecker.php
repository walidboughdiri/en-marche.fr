<?php

namespace AppBundle\Security;

use AppBundle\Entity\Adherent;
use AppBundle\Exception\AccountNotValidatedException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
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
        if (!$user instanceof Adherent) {
            return;
        }

        if (!$user->isEnabled()) {
            if (!$user->getActivatedAt()) {
                throw new AccountNotValidatedException($user);
            }
            $ex = new DisabledException('Account disabled.');
            $ex->setUser($user);
            throw $ex;
        }
    }
}
