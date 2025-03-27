<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Console\Command;

use LupaSearch\LupaSearchPlugin\Model\QueryManagerInterface;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class CreateQuery extends Command
{
    private const NAME = 'lupasearch:query:create';

    private State $state;

    private QueryManagerInterface $queryManager;

    private LoggerInterface $logger;

    public function __construct(
        QueryManagerInterface $queryManager,
        LoggerInterface $logger,
        State $state,
        ?string $name = null
    ) {
        $this->queryManager = $queryManager;
        $this->state = $state;
        $this->logger = $logger;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName(self::NAME)
            ->setDescription((string)__('Create query in LupaSearch'))
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'Query types',
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
            $this->state->setAreaCode('frontend');
            $option = (string)$input->getArgument('type');
            $storeId = (int)$input->getArgument('store_id');
            $output->writeln((string)__('Creating...'));
            $output->writeln('Query: ' . $this->queryManager->generate($option, $storeId));
            $output->writeln((string)__('Done.'));
        } catch (Throwable $e) {
            $this->logger->error($e);
            $output->writeln($e->getMessage());
            $output->writeln((string)__('There was an exception. Please check the logs for more information.'));

            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
