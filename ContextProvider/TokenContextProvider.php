<?php
namespace Xymanek\SentryBundle\ContextProvider;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Role\Role;

class TokenContextProvider implements UserContextProviderInterface
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    public function __construct (
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getUserData (): array
    {
        $token = $this->tokenStorage->getToken();

        if ($token === null || !$this->authorizationChecker->isGranted(AuthenticatedVoter::IS_AUTHENTICATED_REMEMBERED)) {
            return [];
        }

        $roles = array_map(
            function (Role $role) {
                return $role->getRole();
            },
            $token->getRoles()
        );

        return [
            'username' => $token->getUsername(),
            'attributes' => $token->getAttributes(),
            'roles' => $roles,
        ];
    }
}