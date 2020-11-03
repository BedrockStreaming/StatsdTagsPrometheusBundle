# StatsdPrometheusBundle [![Build Status](https://travis-ci.com/M6Web/StatsdTagsPrometheusBundle.svg?branch=master)](https://travis-ci.com/M6Web/StatsdTagsPrometheusBundle)

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
