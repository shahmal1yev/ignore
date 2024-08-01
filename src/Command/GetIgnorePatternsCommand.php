<?php

namespace App\Command;

use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'ignore-patterns',
    description: 'Get ignore patterns from gitignore.io',
    aliases: ['patterns'],
)]
class GetIgnorePatternsCommand extends Command
{
    private HttpClientInterface $client;

    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->client = HttpClient::create();
    }

    /**
     * @param array $options
     * @param float|int $offset
     * @param int $limit
     * @param OutputInterface $output
     * @return void
     */
    private function printPage(array $options, float|int $offset, int $limit, OutputInterface $output): void
    {
        $page = array_slice(
            $options,
            $offset,
            $limit,
            true
        );

        foreach ($page as $key => $pageItem) {
            $output->writeln("<info>- $key</info>");
        }
    }

    /**
     * @param float|int $offset
     * @param int $limit
     * @param int $total
     * @return array
     */
    private function getPaginationRange(float|int $offset, int $limit, int $total): array
    {
        $start = $offset + 1;
        $end = min($offset + $limit, $total);

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * @param int $total
     * @param int $limit
     * @return int
     */
    private function calculateTotalPage(int $total, int $limit): int
    {
        return (int)ceil($total / $limit);
    }

    /**
     * @param int $currentPage
     * @param int $totalPages
     *
     * @return bool
     */
    private function isLastPage(int $currentPage, int $totalPages): bool
    {
        return $currentPage < $totalPages;
    }

    /**
     * @param int $currentPage
     * @param int $itemsPerPage
     * @return float|int
     */
    private function getOffset(int $currentPage, int $itemsPerPage): int|float
    {
        return ($currentPage - 1) * $itemsPerPage;
    }

    /**
     * @param string $input
     * @return bool
     */
    private function shouldExit(string $input): bool
    {
        return $input === 'q';
    }

    /**
     * Generates the wait message.
     */
    private function generateWaitMessage(int $totalItems, int $start, int $end): string
    {
        $message = sprintf(
            "Showing %d to %d of %d entries\nPress Enter to continue or 'q' to quit: ",
            $start,
            $end,
            $totalItems
        );

        return $this->generateBlockMessage('question', $message);
    }

    private function generateBlockMessage(string $theme, string $message): string
    {
        return $this->getHelper('formatter')->formatSection('Info', $message);
    }

    /**
     * Sends a GET request to fetch options.
     *
     * @return array
     *
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getOptions(): array
    {
        $response = $this->client->request('GET', "https://www.toptal.com/developers/gitignore/api/list?format=json", [
            'headers' => ['Accept' => 'application/json'],
        ]);

        $responseContent = $response->getContent();

        $options = json_decode(
            $responseContent,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return $options;
    }

    /**
     * Executes the command.
     *
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');

        $loadingSection = $output->section();
        $contentSection = $output->section();

        $loadingSection->write($this->getHelper('formatter')->formatSection(
            'Loading...',
            'Please wait.',
            'comment'
        ));

        $options = $this->getOptions();
        $loadingSection->clear();

        $limit = 10;
        $total = count($options);
        $totalPage = $this->calculateTotalPage($total, $limit);

        for ($currentPage = 1; $currentPage <= $totalPage; $currentPage++) {
            $offset = $this->getOffset($currentPage, $limit);

            $this->printPage($options, $offset, $limit, $output);

            if ($this->isLastPage($currentPage, $totalPage)) {
                $pagination = $this->getPaginationRange(
                    $offset,
                    $limit,
                    $total
                );

                $waitMessage = $this->generateWaitMessage(
                    $total,
                    current($pagination),
                    next($pagination)
                );

                $contentSection->writeln('');
                $output->write($waitMessage);
                $input = trim(fgets(STDIN));
                $contentSection->writeln('');

                if ($this->shouldExit($input)) {
                    break;
                }
            }
        }

        return Command::SUCCESS;
    }
}
