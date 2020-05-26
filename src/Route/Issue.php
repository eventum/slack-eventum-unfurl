<?php

namespace Eventum\SlackUnfurl\Route;

use DateTime;
use DateTimeZone;
use Eventum\RPC\EventumXmlRpcClient;
use Eventum\RPC\XmlRpcException;
use Psr\Log\LoggerInterface;
use SlackUnfurl\Traits\LoggerTrait;

class Issue
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
     * @see getIssueDetails
     */
    private const MATCH_KEYS = [
        'assignments',
        'iss_created_date',
        'iss_created_date_ts',
        'iss_description',
        'iss_id',
        'iss_last_internal_action_date',
        'iss_last_public_action_date',
        'iss_original_description',
        'iss_summary',
        'iss_updated_date',
        'prc_title',
        'pri_title',
        'reporter',
        'sta_title',
    ];

    public function __construct(
        EventumXmlRpcClient $apiClient,
        string $timeZone,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->logger = $logger;
        $this->utc = new DateTimeZone('UTC');
        $this->timeZone = new DateTimeZone($timeZone);
    }

    public function unfurl(string $url, array $parts): ?array
    {
        $issueId = (int)$parts['id'];
        $issue = $this->getIssueDetails($issueId);
        $this->debug('issue', ['issue' => $issue]);

        return [
            'title' => "{$issue['prc_title']} <$url|Issue #{$issueId}> : {$issue['iss_summary']}",
            'color' => '#006486',
            'ts' => $issue['iss_created_date_ts'],
            'footer' => "Created by {$issue['reporter']}",
            'fields' => [
                [
                    'title' => 'Priority',
                    'value' => $issue['pri_title'],
                    'short' => true,
                ],
                [
                    'title' => 'Assignment',
                    'value' => $issue['assignments'],
                    'short' => true,
                ],
                [
                    'title' => 'Status',
                    'value' => $issue['sta_title'],
                    'short' => true,
                ],
                [
                    'title' => 'Last update',
                    'value' => $this->getLastUpdate($issue)->format('Y-m-d H:i:s'),
                    'short' => true,
                ],
            ],
        ];
    }

    /**
     * Get issue details, but filter only needed keys.
     *
     * @param int $issueId
     * @throws XmlRpcException
     * @return array
     */
    private function getIssueDetails(int $issueId): array
    {
        $issue = $this->apiClient->getIssueDetails($issueId);

        return array_intersect_key($issue, array_flip(self::MATCH_KEYS));
    }

    /**
     * Get issue last update in local timezone
     *
     * @param array $issue
     * @return DateTime last action date in specified timeZone
     */
    private function getLastUpdate(array $issue): DateTime
    {
        $lastUpdated = new DateTime($issue['iss_updated_date'], $this->utc);
        $lastUpdated->setTimezone($this->timeZone);

        return $lastUpdated;
    }
}
