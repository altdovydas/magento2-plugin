<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Test\Unit\Model\Indexer;

use LupaSearch\LupaSearchPlugin\Model\BatchInterface;
use LupaSearch\LupaSearchPlugin\Model\Indexer\Consumer;
use LupaSearch\LupaSearchPlugin\Model\Indexer\PartialIndexerInterface;
use InvalidArgumentException;
use LupaSearch\Exceptions\BadResponseException;
use Magento\Framework\App\Area;
use Magento\Framework\App\AreaInterface;
use Magento\Framework\App\AreaList;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Store\Model\App\Emulation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ConsumerTest extends TestCase
{

    private Consumer $object;

    private MockObject $partialIndexer;

    private MockObject $publisher;

    private MockObject $emulation;

    private MockObject $areaList;

    private MockObject $logger;

    private MockObject $batch;

    private string $topic = 'lupasearch.product.index';

    public function testProcess(): void
    {
        $ids = [1, 2, 3, 4];
        $storeId = 1;

        $this->emulation
            ->expects(self::once())
            ->method('startEnvironmentEmulation')
            ->with($storeId, Area::AREA_FRONTEND, true);

        $this->batch
            ->expects(self::once())
            ->method('getIds')
            ->willReturn($ids);

        $this->batch
            ->expects(self::atLeastOnce())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->partialIndexer
            ->expects(self::once())
            ->method('reindex')
            ->with($ids, $storeId);

        $this->publisher
            ->expects(self::never())
            ->method('publish');

        $this->object->process($this->batch);
    }

    public function testProcessBadResponse(): void
    {
        $ids = [1, 2, 3, 4];
        $storeId = 1;

        $this->emulation
            ->expects(self::once())
            ->method('startEnvironmentEmulation')
            ->with($storeId, Area::AREA_FRONTEND, true);

        $this->batch
            ->expects(self::once())
            ->method('getIds')
            ->willReturn($ids);

        $this->batch
            ->expects(self::atLeastOnce())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->partialIndexer
            ->expects(self::once())
            ->method('reindex')
            ->with($ids, $storeId)
            ->willThrowException(new BadResponseException('Test'));

        $this->publisher
            ->expects(self::once())
            ->method('publish')
            ->with($this->topic, $this->batch);

        $this->logger
            ->expects(self::never())
            ->method('critical');

        $this->object->process($this->batch);
    }

    public function testProcessBadResponsePublisherError(): void
    {
        $ids = [1, 2, 3, 4];
        $storeId = 1;

        $this->emulation
            ->expects(self::once())
            ->method('startEnvironmentEmulation')
            ->with($storeId, Area::AREA_FRONTEND, true);

        $this->batch
            ->expects(self::once())
            ->method('getIds')
            ->willReturn($ids);

        $this->batch
            ->expects(self::atLeastOnce())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->partialIndexer
            ->expects(self::once())
            ->method('reindex')
            ->with($ids, $storeId)
            ->willThrowException(new BadResponseException('Test'));

        $this->publisher
            ->expects(self::once())
            ->method('publish')
            ->with($this->topic, $this->batch)
            ->willThrowException(new InvalidArgumentException('Test Argument'));

        $this->logger
            ->expects(self::once())
            ->method('critical')
            ->with('Test Argument');

        $this->object->process($this->batch);
    }

    protected function setUp(): void
    {
        $this->partialIndexer = $this->createMock(PartialIndexerInterface::class);
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->emulation = $this->createMock(Emulation::class);
        $this->areaList = $this->createMock(AreaList::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->batch = $this->createMock(BatchInterface::class);

        $area = $this->createMock(AreaInterface::class);
        $area
            ->method('load')
            ->willReturnSelf();
        $this->areaList
            ->method('getArea')
            ->with(Area::AREA_FRONTEND)
            ->willReturn($area);

        $this->object = new Consumer(
            $this->partialIndexer,
            $this->publisher,
            $this->emulation,
            $this->areaList,
            $this->logger,
            $this->topic
        );
    }
}
