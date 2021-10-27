# StatsdPrometheusBundle [![Build Status](https://img.shields.io/endpoint.svg?url=https%3A%2F%2Factions-badge.atrox.dev%2FBedrockStreaming%2FStatsdTagsPrometheusBundle%2Fbadge%3Fref%3Dmaster&style=flat)](https://actions-badge.atrox.dev/BedrockStreaming/StatsdTagsPrometheusBundle/goto?ref=master)

Statsd Bundle for Prometheus.  

## Features

* Dispatch events to increment metrics, set gauges and collect timers
* Send Statsd metrics ([DogStatsD](https://docs.datadoghq.com/developers/dogstatsd/) format)
* Handle Prometheus tags in the metrics

## How to use the bundle

1. First [Download and install](Doc/installation.md) the bundle
2. Then, see how to [Configure the bundle](Doc/configuration.md)
3. Then, have a look at the [Usage and code examples](Doc/usage-and-examples.md) documentation

## Noticeable versions and migration guides

* Version 1.6 is compatible with Prometheus 
(converted with [statsd_exporter](https://github.com/prometheus/statsd_exporter))
* Version 2+ adds new features, and the configuration changed a bit, [see upgrade doc](Doc/upgrades/from-1-to-2.md)
* Version 3+ adds compatibility with psr 14 about event dispatcher and use specialized events without names, [see upgrade doc](Doc/upgrades/from-2-to-3.md)

## Contribution

* See [Contribution](Doc/contribution.md)

## License

StatsdPrometheusBundle is licensed under the [MIT license](LICENCE).
