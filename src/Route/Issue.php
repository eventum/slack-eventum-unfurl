<?php

namespace Eventum\SlackUnfurl\Route;

use DateTime;
use DateTimeZone;
use Eventum\SlackUnfurl\ApiClient;
use Psr\Log\LoggerInterface;
use SlackUnfurl\Traits\LoggerTrait;

class Issue
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
        string $timeZone,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->utc = new DateTimeZone('UTC');
        $this->timeZone = new DateTimeZone($timeZone);
    }

    public function unfurl(string $url, array $parts): ?array
    {
        $issueId = (int)$parts['id'];
        $issue = $this->client->getIssueDetails($issueId);
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
