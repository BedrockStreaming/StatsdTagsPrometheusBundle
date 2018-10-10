# StatsdPrometheusBundle [![Build Status](https://travis-ci.org/M6Web/StatsdTagsPrometheusBundle.svg?branch=master)](https://travis-ci.org/M6Web/StatsdTagsPrometheusBundle)

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

## 1. Get the bundle

* See the [download and install](Doc/installation.md) documentation

## 2. Configure the bundle

1. [Configure the servers](Doc/configuration.md#1-configure-the-servers)
2. [Configure the clients](Doc/configuration.md#2-configure-the-clients)
3. [Configure the groups](Doc/configuration.md#3-configure-the-groups)
4. [Configure the events](Doc/configuration.md#4-configure-the-events)
5. [Configure the metrics](Doc/configuration.md#5-configure-the-metrics)
6. [Configure the tags](Doc/configuration.md#6-configure-the-tags)
7. [Compatibility and legacy behaviour](Doc/configuration.md#7-compatibility-and-legacy-behaviour)

## 3. Usage & examples

* See [How to use the bundle](Doc/usage.md) and [Code examples](Doc/examples.md).
* See [Prometheus in Grafana](Doc/prometheus-grafana.md) (:warning: Work in progress).

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