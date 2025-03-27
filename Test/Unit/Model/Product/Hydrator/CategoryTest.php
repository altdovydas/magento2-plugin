<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Test\Unit\Model\Product\Hydrator;

use LupaSearch\LupaSearchPlugin\Model\Config\Queries\ProductConfigInterface;
use LupaSearch\LupaSearchPlugin\Model\Product\Hydrator\Category;
use LupaSearch\LupaSearchPlugin\Model\Provider\Categories\AnchorProviderInterface;
use LupaSearch\LupaSearchPlugin\Model\Provider\Categories\ParentIdsProviderInterface;
use LupaSearch\LupaSearchPlugin\Model\Provider\Categories\PositionProviderInterface;
use LupaSearch\LupaSearchPlugin\Model\Provider\CategoriesProviderInterface;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    private Category $object;

    private MockObject $categoriesProvider;

    private MockObject $product;

    private MockObject $productConfig;

    private MockObject $positionProvider;

    private MockObject $anchorProvider;

    private MockObject $parentIdsProvider;

    public function testExtract(): void
    {
        $expected = [
            'category_id' => 17,
            'category_ids' => [16, 17],
            'categories' => [0 => 'Test1', 1 => 'Test0'],
            'category' => 'Test9 > Test0',
            'position' => [
                'category_16' => 1,
                'category_17' => 5,
            ],
            'sp_5' => [0 => 'Test1', 1 => 'Test0'],
            'categories_hierarchy' => [0 => 'Root > Test0 > Test1', 1 => 'Test9 > Test0'],
            'categories_last' => ['Test1', 'Test0'],
        ];
        $categoryIds = [
            1 => 16,
            9 => 17,
        ];
        $storeId = '1';
        $productId = 444;

        $this->positionProvider
            ->expects(self::once())
            ->method('getByProductId')
            ->with($productId)
            ->willReturn([
                16 => 1,
                17 => 5,
            ]);

        $this->categoriesProvider
            ->expects(self::exactly(9))
            ->method('getNameById')
            ->willReturnMap(
                [
                    [16, 1, 'Test1'],
                    [17, 1, 'Test0'],
                    [1, 1, 'Root'],
                    [2, 1, 'Test0'],
                    [17, 1, 'Test1'],
                    [9, 1, 'Test9'],
                ]
            );

        $this->parentIdsProvider
            ->expects(self::exactly(3))
            ->method('getById')
            ->willReturnMap(
                [
                    [17, [9]],
                    [16, [1, 2]],
                ]
            );

        $this->product
            ->expects(self::any())
            ->method('getCategoryIds')
            ->willReturn($categoryIds);

        $this->product
            ->expects(self::any())
            ->method('getCategoryId')
            ->willReturn(17);

        $this->product
            ->expects(self::any())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->product
            ->expects(self::any())
            ->method('getId')
            ->willReturn($productId);

        $this->product
            ->expects(self::any())
            ->method('getData')
            ->with('assigned_category_ids')
            ->willReturn([16, 17]);

        $result = $this->object->extract($this->product);
        $this->assertCount(8, $result);
        $this->assertEquals($expected['category_id'], $result['category_id']);
        $this->assertEquals($expected['category_ids'], $result['category_ids']);
        $this->assertEquals($expected['categories'], $result['categories']);
        $this->assertEquals($expected['category'], $result['category']);
        $this->assertEquals($expected['sp_5'], $result['sp_5']);
        $this->assertEquals($expected['position'], $result['position']);
        $this->assertEquals($expected['categories_hierarchy'], $result['categories_hierarchy']);
        $this->assertEquals($expected['categories_last'], $result['categories_last']);
    }

    protected function setUp(): void
    {
        $this->categoriesProvider = $this->createMock(CategoriesProviderInterface::class);
        $this->productConfig = $this->createMock(ProductConfigInterface::class);
        $this->parentIdsProvider = $this->createMock(ParentIdsProviderInterface::class);
        $this->positionProvider = $this->createMock(PositionProviderInterface::class);
        $this->anchorProvider = $this->createMock(AnchorProviderInterface::class);
        $this->product = $this->createMock(Product::class);

        $this->productConfig
            ->expects(self::once())
            ->method('getCategoriesSearchWeight')
            ->willReturn(5);

        $this->object = new Category(
            $this->categoriesProvider,
            $this->productConfig,
            $this->parentIdsProvider,
            $this->positionProvider,
            $this->anchorProvider
        );
    }
}
