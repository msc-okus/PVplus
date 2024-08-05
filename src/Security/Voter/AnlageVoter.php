<?php

namespace App\Security\Voter;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class AnlageVoter extends Voter
{
    final public const EDIT = 'EDIT';
    final public const VIEW = 'VIEW';
    final public const DELETE = 'DELETE';


    public function __construct(private readonly Security $security)
    {
    }


    protected function supports(string $attribute, mixed $subject): bool
    {

        return in_array($attribute, [self::EDIT, self::VIEW,self::DELETE])
            && $subject instanceof \App\Entity\Anlage;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::EDIT:
                if( $subject->getEigner === $user){
                    return true;
                }
                if( $this->security->isGranted('ROLE_API_USER_FULL')){
                    return true;
                }
                return false;


        }

        return false;
    }
}
