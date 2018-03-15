<?php

namespace AppBundle\Exception;

use AppBundle\Entity\Adherent;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Throwable;

class AccountNotValidatedException extends AccountStatusException
{
    public function __construct(Adherent $adherent, int $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('Account[%s] tried to connect without validated his email.', $adherent->getEmailAddress()), $code, $previous);

        $this->setUser($adherent);
    }

    public function getMessageKey()
    {
        return 'Account have to be validated ! Please Check your mailbox.';
    }
}
