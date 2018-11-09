# Download an installation

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [Install with composer](#install-with-composer)
- [Symfony >=4.0](#symfony-40)
- [Symfony < 4.0](#symfony--40)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## Install with composer
```bash
composer require m6web/statsd-prometheus-bundle
```

## Symfony >=4.0

### Register the bundle (if automatic addition has failed)

```php
// config/bundles.php
return [
    // ...Other bundles...
    M6Web\Bundle\StatsdPrometheusBundle\M6WebStatsdPrometheusBundle::class => ['all' => true]
    // ...Other bundles...
];
```

### Add the configuration file

Create a file named `config/packages/m6web_statsd_prometheus.yaml` and add 
the config root key in a config file:
```yaml
#config/packages/statsd_prometheus.yml
m6web_statsd_prometheus: ~
```


## Symfony < 4.0

### Register the bundle

```php
// app/AppKernel.php
[
    // ...Other bundles...
    new M6Web\Bundle\StatsdPrometheusBundle\M6WebStatsdPrometheusBundle(),
    // ...Other bundles...
]
```

### Set the configuration key
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



[Go back](../README.md)