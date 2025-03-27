<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Console\Command;

use LupaSearch\LupaSearchPlugin\Model\SuggestionsGeneratorInterface;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateSuggestions extends Command
{
    private const NAME = 'lupasearch:generate:suggestions';

    private SuggestionsGeneratorInterface $suggestionsGenerator;

    public function __construct(SuggestionsGeneratorInterface $suggestionsGenerator, ?string $name = null)
    {
        parent::__construct($name);

        $this->suggestionsGenerator = $suggestionsGenerator;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName(self::NAME)
            ->setDescription((string)__('Generate suggestions'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln((string)__('Generating suggestions...'));
        $this->suggestionsGenerator->generateAll();
        $output->writeln((string)__('Done.'));

        return Cli::RETURN_SUCCESS;
    }
}
