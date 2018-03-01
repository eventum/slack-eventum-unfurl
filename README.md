# Slack unfurl Eventum Provider

Eventum issue links unfurler for [slack-unfurl].

## Installation

1. Install [slack-unfurl]
2. Require this package: `composer require eventum/slack-unfurl-eventum`
3. Merge `env.example` from this project to `.env`
4. Register provider: in `src/Application.php` add `$this->register(new \Eventum\SlackUnfurl\ServiceProvider\EventumUnfurlServiceProvider());`

[slack-unfurl]: https://github.com/glensc/slack-unfurl
