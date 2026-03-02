<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Tests de l'entité User : logique getRoles() (dérivation ROLE_* à partir du champ role).
 */
final class UserTest extends TestCase
{
    public function testGetRolesEtudiant(): void
    {
        $user = new User();
        $user->setName('Test');
        $user->setPrenom('User');
        $user->setEmail('test@test.com');
        $user->setRole('etudiant');
        $user->setPassword('hash');
        $user->setNumtel('12345678');

        $roles = $user->getRoles();
        self::assertContains('ROLE_USER', $roles);
        self::assertContains('ROLE_ETUDIANT', $roles);
        self::assertCount(2, array_unique($roles));
    }

    public function testGetRolesProfesseur(): void
    {
        $user = new User();
        $user->setName('Prof');
        $user->setPrenom('Test');
        $user->setEmail('prof@test.com');
        $user->setRole('professeur');
        $user->setPassword('hash');
        $user->setNumtel('12345678');

        $roles = $user->getRoles();
        self::assertContains('ROLE_USER', $roles);
        self::assertContains('ROLE_PROFESSEUR', $roles);
    }

    public function testGetRolesProfessorAlias(): void
    {
        $user = new User();
        $user->setName('Prof');
        $user->setPrenom('Test');
        $user->setEmail('prof@test.com');
        $user->setRole('professor');
        $user->setPassword('hash');
        $user->setNumtel('12345678');

        $roles = $user->getRoles();
        self::assertContains('ROLE_PROFESSEUR', $roles);
    }

    public function testGetRolesAdmin(): void
    {
        $user = new User();
        $user->setName('Admin');
        $user->setPrenom('Test');
        $user->setEmail('admin@test.com');
        $user->setRole('admin');
        $user->setPassword('hash');
        $user->setNumtel('12345678');

        $roles = $user->getRoles();
        self::assertContains('ROLE_USER', $roles);
        self::assertContains('ROLE_ADMIN', $roles);
    }

    public function testGetRolesChefDept(): void
    {
        $user = new User();
        $user->setName('Chef');
        $user->setPrenom('Dept');
        $user->setEmail('chef@test.com');
        $user->setRole('chef_dept');
        $user->setPassword('hash');
        $user->setNumtel('12345678');

        $roles = $user->getRoles();
        self::assertContains('ROLE_CHEF_DEPT', $roles);
    }

    public function testGetRolesChefDeptWithSpace(): void
    {
        $user = new User();
        $user->setName('Chef');
        $user->setPrenom('Dept');
        $user->setEmail('chef2@test.com');
        $user->setRole('chef dept');
        $user->setPassword('hash');
        $user->setNumtel('12345678');

        $roles = $user->getRoles();
        self::assertContains('ROLE_CHEF_DEPT', $roles);
    }

    public function testGetRolesUnknownFallsBackToEtudiant(): void
    {
        $user = new User();
        $user->setName('X');
        $user->setPrenom('Y');
        $user->setEmail('x@test.com');
        $user->setRole('unknown_role');
        $user->setPassword('hash');
        $user->setNumtel('12345678');

        $roles = $user->getRoles();
        self::assertContains('ROLE_ETUDIANT', $roles);
    }

    public function testGetRolesNullRoleFallsBackToEtudiant(): void
    {
        $user = new User();
        $user->setName('X');
        $user->setPrenom('Y');
        $user->setEmail('x@test.com');
        $user->setPassword('hash');
        $user->setNumtel('12345678');
        $ref = new \ReflectionClass($user);
        $prop = $ref->getProperty('role');
        $prop->setAccessible(true);
        $prop->setValue($user, null);

        $roles = $user->getRoles();
        self::assertContains('ROLE_ETUDIANT', $roles);
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $user = new User();
        $user->setEmail('ident@test.com');
        $user->setName('A');
        $user->setPrenom('B');
        $user->setRole('etudiant');
        $user->setPassword('hash');
        $user->setNumtel('12345678');

        self::assertSame('ident@test.com', $user->getUserIdentifier());
    }
}
