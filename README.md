# StatsdPrometheusBundle

Statsd Bundle for Prometheus. 

:warning: Alpha version 

## Features

* bind any event to increment metrics, set gauges and collect timers
* send Statsd metrics ([DogStatsD](https://docs.datadoghq.com/developers/dogstatsd/) format)
 compatible with Prometheus 
(converted with [statsd_exporter](https://github.com/prometheus/statsd_exporter))
* handle Prometheus tags in the metrics  
 
## Requirements

- Symfony ≥3.4
- Php ≥7.1

## Set up the bundle

### Installation & configuration
 
* See [Installation](Doc/installation.md) and [Configuration](Doc/configuration.md).

### Usage & examples

* See [Usage](Doc/usage.md) and [Examples](Doc/examples.md).

### Prometheus in Grafana

* See [Prometheus in Grafana](Doc/prometheus-grafana.md) (:warning: Work in progress)

### Contribution & Tests

* See [Contribution & tests](Doc/contribution.md).

## Credits

Bundle provided by the M6Web open source initiative.

our blog : http://tech.m6web.fr/ (french)
and twitter : https://twitter.com/TechM6Web (french)

## License

StatsdPrometheusBundle is licensed under the [MIT license](LICENCE).