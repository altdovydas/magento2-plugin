<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Document;

use LupaSearch\LupaSearchPlugin\Model\Product\SortBy\OptionBuilder\SortBuilder\Relevance;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\DocumentQueryResponseInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Api\AttributeInterfaceFactory;
use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\Api\Search\DocumentInterfaceFactory;

use function array_map;

class Builder implements BuilderInterface
{
    private DocumentInterfaceFactory $documentFactory;

    private AttributeInterfaceFactory $attributeFactory;

    public function __construct(DocumentInterfaceFactory $documentFactory, AttributeInterfaceFactory $attributeFactory)
    {
        $this->documentFactory = $documentFactory;
        $this->attributeFactory = $attributeFactory;
    }

    /**
     * @inheritDoc
     */
    public function build(DocumentQueryResponseInterface $response): array
    {
        return array_map(
            function ($item): DocumentInterface {
                return $this->documentFactory->create([
                    'data' => [
                        DocumentInterface::ID => $item['id'] ?? 0,
                        DocumentInterface::CUSTOM_ATTRIBUTES => [
                            'score' => $this->attributeFactory->create(
                                [
                                    'data' => [
                                        AttributeInterface::ATTRIBUTE_CODE => 'score',
                                        AttributeInterface::VALUE => 0,
                                    ],
                                ],
                            )
                        ],
                    ],
                ]);
            },
            $response->getItems(),
        );
    }
}
