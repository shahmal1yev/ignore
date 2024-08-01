<?php

namespace App\Command;

use InvalidArgumentException;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'create-gitignore',
    description: 'Create gitignore file',
    aliases: ['create']
)]
class CreateGitignoreCommand extends Command
{
    /**
     * @param InputInterface $input
     * @param string $gitignoreContent
     * @param OutputInterface $output
     * @return void
     */
    public function save(InputInterface $input, string $gitignoreContent, OutputInterface $output): void
    {
        if (! $input->getOption('path')) {
            return;
        }

        $savePath = $input->getOption('path');
        $absolutePath = rtrim($savePath, '/') . '/.gitignore';

        $file = fopen($absolutePath, 'w');
        fwrite($file, $gitignoreContent);
        fclose($file);

        $output->writeln($this->getHelper('formatter')->formatSection(
            "Success",
            "File saved on $absolutePath"
        ));
    }

    /**
     * @param mixed $selectedOptions
     * @return string
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getGitignoreContent(mixed $selectedOptions): string
    {
        $url = $this->prepareURL($selectedOptions);

        $client = HttpClient::create();
        $response = $client->request('GET', $url);

        return $response->getContent();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $choices
     * @return mixed
     */
    public function getSelectedOptions(InputInterface $input, OutputInterface $output, array $choices): mixed
    {
        $selectedOptions = $this->ask($choices, $input, $output);
        return $selectedOptions;
    }

    /**
     * @param mixed $selectedOptions
     * @return string
     */
    public function prepareURL(mixed $selectedOptions): string
    {
        return sprintf(
            "https://www.toptal.com/developers/gitignore/api/%s",
            implode(',', $selectedOptions)
        );
    }

    /**
     * @param array $choices
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    public function ask(array $choices, InputInterface $input, OutputInterface $output)
    {
        $question = new ChoiceQuestion(
            'Please select patterns:',
            $choices,
        );

        $question->setMultiselect(true);

        $selectedOptions = $this->getHelper('question')->ask(
            $input,
            $output,
            $question
        );

        return $selectedOptions;
    }

    /**
     * @return array
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getChoices(): array
    {
        $options = (new GetIgnorePatternsCommand())->getOptions();

        return array_combine(
            array_keys($options),
            array_column($options, 'name')
        );
    }

    /**
     * @param InputInterface $input
     * @param array $choices
     * @return mixed
     */
    public function validatePatternsArgument(InputInterface $input, array $choices): array
    {
        if ($input->hasOption('patterns')) {
            if ($patterns = $input->getOption('patterns')) {
                $invalidArguments = array_filter(
                    $patterns,
                    fn ($pattern) => ! in_array($pattern, array_keys($choices))
                );

                if (! empty($invalidArguments)) {
                    throw new InvalidArgumentException(sprintf("Invalid patterns: '%s'", implode(', ', $invalidArguments)));
                }
            }
        }

        return $patterns ?? [];
    }

    /**
     * @param InputInterface $input
     * @return void
     */
    public function validateSavePathOption(InputInterface $input): void
    {
        $savePath = $input->getOption('path');

        $isDir = is_dir($savePath);
        $userWantsToSave = $savePath !== false;

        if (! $isDir && $userWantsToSave) {
            throw new InvalidArgumentException(sprintf("Directory does not exist: '%s'", $savePath));
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->validateSavePathOption($input);

        $choices = $this->getChoices();
        $patterns = $this->validatePatternsArgument($input, $choices);

        if (empty($patterns)) {
            $patterns = $this->getSelectedOptions($input, $output, $choices);
        }

        $gitignoreContent = $this->getGitignoreContent($patterns);

        $output->writeln('');
        $output->writeln("<comment>$gitignoreContent</comment>");

        $this->save($input, $gitignoreContent, $output);

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this->addOption(
            'path',
            'pt',
            InputOption::VALUE_OPTIONAL,
            'If you want to create and save a file as .gitignore from result use this argument.',
            false,
            ['./gitignore']
        );

        $this->addOption(
            'patterns',
            'p',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Retrieves the provided content of pattern categories from Gitignore.io.',
        );
    }
}
