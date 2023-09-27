<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{

    const PRODUCT_PEN = 'pen';

    const PRODUCT_PAPER = 'paper';

    public function load(ObjectManager $manager): void
    {
        $pen = Product::new()->setName(static::PRODUCT_PEN)->setPrice(1.5);
        $manager->persist($pen);

        $paper = Product::new()->setName(static::PRODUCT_PAPER)->setPrice(15);
        $manager->persist($paper);

        $manager->flush();

        $this->addReference(static::PRODUCT_PEN, $pen);
        $this->addReference(static::PRODUCT_PAPER, $paper);
    }

}
