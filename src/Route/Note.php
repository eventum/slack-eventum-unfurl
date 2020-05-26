<?php

namespace Eventum\SlackUnfurl\Route;

use DateTimeZone;
use Eventum\RPC\EventumXmlRpcClient;
use Eventum\RPC\XmlRpcException;
use Psr\Log\LoggerInterface;
use SlackUnfurl\Traits\LoggerTrait;

class Note
{
    use LoggerTrait;

    /** @var EventumXmlRpcClient */
    private $apiClient;
    /** @var DateTimeZone */
    private $utc;
    /** @var DateTimeZone */
    private $timeZone;

    /**
     * getDetails keys to retrieve
     * @see getNoteDetails
     */
    private const MATCH_KEYS = [
        'not_id',
        'not_iss_id',
        'not_title',
        'not_from',
        'not_note',
        'not_created_date_ts',
    ];

    public function __construct(
        EventumXmlRpcClient $apiClient,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->logger = $logger;
    }

    public function unfurl(string $url, array $parts): ?array
    {
        $noteId = (int)$parts['id'];
        $note = $this->getNoteDetails($noteId);
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

    /**
     * @param int $noteId
     * @throws XmlRpcException
     * @return array
     */
    private function getNoteDetails(int $noteId): array
    {
        return $this->apiClient->getNoteDetails($noteId, self::MATCH_KEYS);
    }
}
