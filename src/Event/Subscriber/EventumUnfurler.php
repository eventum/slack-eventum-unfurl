<?php

namespace Eventum\SlackUnfurl\Event\Subscriber;

use DateTime;
use DateTimeZone;
use Eventum_RPC;
use Eventum_RPC_Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SlackUnfurl\Event\Events;
use SlackUnfurl\Event\UnfurlEvent;
use SlackUnfurl\LoggerTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventumUnfurler implements EventSubscriberInterface
{
    use LoggerTrait;

    /** @var Eventum_RPC */
    private $apiClient;
    /** @var DateTimeZone */
    private $utc;
    /** @var string */
    private $domain;
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
        Eventum_RPC $apiClient,
        string $domain,
        string $timeZone,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->domain = $domain;
        $this->apiClient = $apiClient;
        $this->utc = new DateTimeZone('UTC');
        $this->timeZone = new DateTimeZone($timeZone);

        if (!$this->domain) {
            throw new InvalidArgumentException('Domain not set');
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::SLACK_UNFURL => ['unfurl', 10],
        ];
    }

    public function unfurl(UnfurlEvent $event)
    {
        foreach ($event->getMatchingLinks($this->domain) as $link) {
            $issueId = $this->getIssueId($link);
            if (!$issueId) {
                $this->error('Could not extract issueId', ['link' => $link]);
                continue;
            }

            $url = $link['url'];
            $unfurl = $this->getIssueUnfurl($issueId, $url);
            $event->addUnfurl($url, $unfurl);
        }
    }

    public function getIssueUnfurl(int $issueId, string $url)
    {
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
     * @return array
     * @throws Eventum_RPC_Exception
     */
    private function getIssueDetails(int $issueId)
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
    private function getLastUpdate(array $issue)
    {
        $lastUpdated = new DateTime($issue['iss_updated_date'], $this->utc);
        $lastUpdated->setTimezone($this->timeZone);

        return $lastUpdated;
    }

    private function getIssueId($link): ?int
    {
        if (!preg_match('#view.php\?id=(?P<id>\d+)#', $link['url'], $m)) {
            return null;
        }

        return (int)$m['id'];
    }
}