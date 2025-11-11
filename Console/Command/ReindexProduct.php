<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Console\Command;

use LupaSearch\LupaSearchPlugin\Model\Indexer\Action\RowsPollInterface;
use LupaSearch\LupaSearchPlugin\Model\Product\CollectionBuilder;
use Exception;
use Magento\Framework\Console\Cli;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function array_map;
use function count;
use function str_replace;

use const PHP_EOL;

class ReindexProduct extends Command
{
    private const NAME = 'lupasearch:reindex:product';

    private RowsPollInterface $rowsPoll;

    private LoggerInterface $logger;

    private CollectionBuilder $collectionBuilder;

    public function __construct(
        RowsPollInterface $rowsPoll,
        CollectionBuilder $collectionBuilder,
        LoggerInterface $logger,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->rowsPoll = $rowsPoll;
        $this->logger = $logger;
        $this->collectionBuilder = $collectionBuilder;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName(self::NAME)
            ->setDescription(__('Reindex LupaSearch Product by SKU')->getText())
            ->addArgument(
                'sku',
                InputArgument::REQUIRED,
                'SKU (searching with wildcard)',
            )
            ->addArgument(
                'store_id',
                InputArgument::REQUIRED,
                'Store ID',
            )
            ->addArgument(
                'price',
                InputArgument::OPTIONAL,
                'Price only',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $indexerName = $input->getArgument('price') ? 'price' : 'product';
            $fullIndexer = $this->rowsPoll->get($indexerName);
            $output->writeln((string)__('Searching...'));
            $ids = $this->getIds($input);

            if (empty($ids)) {
                $output->writeln((string)__('No products were found matching your condition.'));

                return Cli::RETURN_SUCCESS;
            }

            $output->writeln((string)__('Found: ' . count($ids)));
            $output->writeln((string)__('Pushing products in queue...'));
            $fullIndexer->executeByStore($this->getStoreId($input), $ids);
            $output->writeln((string)__('Done.'));
        } catch (Exception $exception) {
            $this->logger->error($exception . PHP_EOL . $exception->getTraceAsString());
            $output->writeln($exception->getMessage());
            $output->writeln((string)__('There was an exception. Please check the logs for more information.'));

            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @return int[]
     */
    private function getIds(InputInterface $input): array
    {
        $collection = $this->collectionBuilder->build($this->getStoreId($input));
        $collection->removeAllFieldsFromSelect();
        $collection->addAttributeToFilter('sku', ['like' => $this->getSku($input)]);

        return array_map('intval', $collection->getAllIds());
    }

    private function getStoreId(InputInterface $input): int
    {
        return (int)$input->getArgument('store_id');
    }

    private function getSku(InputInterface $input): string
    {
        $sku = (string)$input->getArgument('sku');

        return str_replace('*', '%', $sku);
    }
}
