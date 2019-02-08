<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Test\TestCaseBase;

use PHPUnit\Framework\TestCase;

class KernelTestBehaviourTest extends TestCase
{
    use KernelTestBehaviour;

    private $kernelId;

    protected function setUp(): void
    {
        $this->kernelId = spl_object_hash($this->getKernel());
    }

    protected function tearDown(): void
    {
        if (!$this->kernelId === spl_object_hash($this->getKernel())) {
            throw new \RuntimeException('Kernel has changed');
        }
    }

    public function testTheKernelIsEqual(): void
    {
        self::assertEquals($this->kernelId, spl_object_hash($this->getKernel()));
    }

    public function testClientIsUsingTheSameKernel(): void
    {
        self::assertSame(
            spl_object_hash(KernelLifecycleManager::getKernel()),
            spl_object_hash(KernelLifecycleManager::createClient(KernelLifecycleManager::getKernel())->getKernel())
        );
    }
}
