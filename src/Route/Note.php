<?php

namespace Eventum\SlackUnfurl\Route;

use DateTimeZone;
use Eventum\SlackUnfurl\ApiClient;
use Psr\Log\LoggerInterface;
use SlackUnfurl\Traits\LoggerTrait;

class Note
{
    use LoggerTrait;

    /** @var ApiClient */
    private $client;
    /** @var DateTimeZone */
    private $utc;
    /** @var DateTimeZone */
    private $timeZone;

    public function __construct(
        ApiClient $client,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function unfurl(string $url, array $parts): ?array
    {
        $noteId = (int)$parts['id'];
        $note = $this->client->getNoteDetails($noteId);
        $this->debug(' note', ['note' => $note]);
        $issueId = $note['not_iss_id'];

        return [
            'title' => "Note in <$url|Issue #{$issueId}>: {$note['not_title']}",
            'text' => $note['not_note'],
            'color' => '#006486',
            'ts' => $note['not_created_date_ts'],
            'footer' => "Note by {$note['not_from']}",
        ];
    }
}
