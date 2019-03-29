<?php

namespace Eventum\SlackUnfurl\Event\Subscriber;

use Eventum\SlackUnfurl\Route;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SlackUnfurl\CommandResolver;
use SlackUnfurl\Event\Events;
use SlackUnfurl\Event\UnfurlEvent;
use SlackUnfurl\Route\RouteMatcher;
use SlackUnfurl\Traits\LoggerTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventumUnfurler implements EventSubscriberInterface
{
    use LoggerTrait;

    /** @var string */
    private $domain;

    private const ROUTES = [
        'issue' => Route\Issue::class,
    ];

    /** @var RouteMatcher */
    private $routeMatcher;

    /** @var CommandResolver */
    private $commandResolver;

    public function __construct(
        RouteMatcher $routeMatcher,
        CommandResolver $commandResolver,
        string $domain,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->domain = $domain;
        $this->routeMatcher = $routeMatcher;
        $this->commandResolver = $commandResolver;

        if (!$this->domain) {
            throw new InvalidArgumentException('Domain not set');
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::SLACK_UNFURL => ['unfurl', 10],
        ];
    }

    public function unfurl(UnfurlEvent $event): void
    {
        foreach ($event->getMatchingLinks($this->domain) as $link) {
            try {
                $unfurl = $this->unfurlByUrl($link['url']);
                if ($unfurl) {
                    $event->addUnfurl($link['url'], $unfurl);
                }
            } catch (RuntimeException $e) {
                $this->debug("eventum: {$e->getMessage()}");
            }
        }
    }

    private function unfurlByUrl(string $url): ?array
    {
        [$router, $matches] = $this->routeMatcher->match($url);

        $command = $this->commandResolver
            ->configure(self::ROUTES)
            ->resolve($router);

        return $command->unfurl($url, $matches);
    }
}
