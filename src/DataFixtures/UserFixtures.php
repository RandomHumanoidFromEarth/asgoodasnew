<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{

    const DEFAULT_USER = 'default-user';

    public function load(ObjectManager $manager): void
    {
        $user = User::new()->setFirstName('Tylor')->setLastName('Durden');
        $manager->persist($user);

        $user = User::new()->setFirstName('Marla')->setLastName('Singer');
        $manager->persist($user);

        $manager->flush();

        $this->addReference(static::DEFAULT_USER, $user);
    }

}
