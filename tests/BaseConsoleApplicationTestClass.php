<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class BaseConsoleApplicationTestClass extends KernelTestCase
{
    protected KernelInterface $kernelInstance;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->kernelInstance = self::bootKernel();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
