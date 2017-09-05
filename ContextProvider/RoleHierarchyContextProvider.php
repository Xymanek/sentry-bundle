<?php
namespace Xymanek\SentryBundle\ContextProvider;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class RoleHierarchyContextProvider implements UserContextProviderInterface
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var RoleHierarchyInterface
     */
    protected $roleHierarchy;

    public function __construct (TokenStorageInterface $tokenStorage, RoleHierarchyInterface $roleHierarchy)
    {
        $this->tokenStorage = $tokenStorage;
        $this->roleHierarchy = $roleHierarchy;
    }

    public function getUserData (): array
    {
        $token = $this->tokenStorage->getToken();

        if ($token === null) {
            return [];
        }

        $roles = array_map(
            function (Role $role) {
                return $role->getRole();
            },
            $this->roleHierarchy->getReachableRoles($token->getRoles())
        );

        if ($roles === []) {
            return [];
        }

        return compact('roles');
    }
}