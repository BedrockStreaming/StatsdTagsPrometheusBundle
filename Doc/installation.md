# Installation

## Install with composer
```bash
composer require m6web/statsd-prometheus-bundle
```

## Symfony <4

## Register the bundle

```php
// app/AppKernel.php
[
    // ...Other bundles...
    new M6Web\Bundle\StatsdPrometheusBundle\M6WebStatsdPrometheusBundle(),
    // ...Other bundles...
]
```

## Set the configuration key
Create a file named, for example, `m6web_statsd_prometheus.yml` and add the config root key in a config file:
```yaml
#app/config/m6web_statsd_prometheus.yml
m6web_statsd_prometheus: ~
```
Include this file in your main config file:
```yaml
#app/config/config.yml
- { resource: m6web_statsd_prometheus.yml }
```

## Symfony >=4

## Register the bundle (if automatic addition has failed)

```php
// config/bundles.php
return [
    // ...Other bundles...
    M6Web\Bundle\StatsdPrometheusBundle\M6WebStatsdPrometheusBundle::class => ['all' => true]
    // ...Other bundles...
];
```

## Add the configuration file
Create a file named `config/packages/m6web_statsd_prometheus.yaml` and add 
the config root key in a config file:
```yaml
#config/packages/statsd_prometheus.yml
m6web_statsd_prometheus: ~
```


[Go back](../README.md)