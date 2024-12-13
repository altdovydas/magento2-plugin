<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Console\Command;

use LupaSearch\LupaSearchPlugin\Model\Indexer\Action\FullPoolInterface;
use Exception;
use Magento\Framework\Console\Cli;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function implode;

use const PHP_EOL;

class ReindexStore extends Command
{
    private const NAME = 'lupasearch:reindex:store';

    /**
     * @var string[]
     */
    protected $indexers = [
        'product',
        'category',
        'price',
    ];

    private FullPoolInterface $fullIndexerPool;

    private LoggerInterface $logger;

    public function __construct(FullPoolInterface $fullIndexerPool, LoggerInterface $logger, ?string $name = null)
    {
        parent::__construct($name);

        $this->fullIndexerPool = $fullIndexerPool;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName(self::NAME)
            ->setDescription(__('Reindex LupaSearch by Store')->getText())
            ->addArgument(
                'indexer',
                InputArgument::REQUIRED,
                'Options: ' . implode(', ', $this->indexers),
            )
            ->addArgument(
                'store_id',
                InputArgument::REQUIRED,
                'Store ID',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $indexer = (string)$input->getArgument('indexer');
            $storeId = (int)$input->getArgument('store_id');
            $fullIndexer = $this->fullIndexerPool->get($indexer);

            if (!$fullIndexer) {
                $output->writeln("Indexer {$indexer} not found.");

                return Cli::RETURN_FAILURE;
            }

            $output->writeln((string)__('Pushing ids in queue...'));
            $fullIndexer->executeByStore($storeId);
            $output->writeln((string)__('Done.'));
        } catch (Exception $exception) {
            $this->logger->error($exception . PHP_EOL . $exception->getTraceAsString());
            $output->writeln($exception->getMessage());
            $output->writeln((string)__('There was an exception. Please check the logs for more information.'));

            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
