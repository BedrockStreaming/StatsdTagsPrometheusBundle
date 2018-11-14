# StatsdPrometheusBundle [![Build Status](https://travis-ci.com/M6Web/StatsdTagsPrometheusBundle.svg?branch=master)](https://travis-ci.com/M6Web/StatsdTagsPrometheusBundle)

Statsd Bundle for Prometheus.  

## Features

* Bind any event to increment metrics, set gauges and collect timers
* Send Statsd metrics ([DogStatsD](https://docs.datadoghq.com/developers/dogstatsd/) format)
 compatible with Prometheus 
(converted with [statsd_exporter](https://github.com/prometheus/statsd_exporter))
* Handle Prometheus tags in the metrics  
 
## Requirements

- Symfony ≥3.4
- Php ≥7.1

## How to use the bundle

1. First [Download and install](Doc/installation.md) the bundle
2. Then, see how to [Configure the bundle](Doc/configuration.md)
3. Then, have a look at the [Usage and code examples](Doc/usage-and-examples.md) documentation

## Contribution & Tests

* See [Contribution & tests](Doc/contribution.md) (:warning: Work in progress).
* Install a local statsd_exporter using docker:
[https://github.com/prometheus/statsd_exporter#using-docker](https://github.com/prometheus/statsd_exporter#using-docker)

## Credits

Bundle provided by the M6Web open source initiative.

Our blog : http://tech.m6web.fr/ (french)
and twitter : https://twitter.com/TechM6Web (french)

## License

StatsdPrometheusBundle is licensed under the [MIT license](LICENCE).
