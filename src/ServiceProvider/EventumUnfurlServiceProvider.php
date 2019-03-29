<?php

namespace Eventum\SlackUnfurl\ServiceProvider;

use Eventum\RPC\EventumXmlRpcClient;
use Eventum\SlackUnfurl\Event\Subscriber\EventumUnfurler;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventumUnfurlServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app): void
    {
        $app['eventum.rpc_url'] = getenv('EVENTUM_RPC_URL');
        $app['eventum.username'] = getenv('EVENTUM_USERNAME');
        $app['eventum.access_token'] = getenv('EVENTUM_ACCESS_TOKEN');
        $app['eventum.domain'] = getenv('EVENTUM_DOMAIN');
        $app['eventum.timezone'] = getenv('EVENTUM_TIMEZONE');

        $app[EventumXmlRpcClient::class] = function ($app) {
            $client = new EventumXmlRpcClient($app['eventum.rpc_url']);
            $client->setCredentials($app['eventum.username'], $app['eventum.access_token']);

            return $client;
        };

        $app[EventumUnfurler::class] = function ($app) {
            return new EventumUnfurler(
                $app[EventumXmlRpcClient::class],
                $app['eventum.domain'],
                $app['eventum.timezone'],
                $app['logger']
            );
        };
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher): void
    {
        $dispatcher->addSubscriber($app[EventumUnfurler::class]);
    }
}
