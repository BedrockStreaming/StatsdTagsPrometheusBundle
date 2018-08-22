Prometheus in Grafana 
======

(:warning: Work in progress)

Getting started with Prometheus
------

### Configuration example

This is how you configured your event:
```yaml
m6web_statsd_prometheus:
    servers: [...]
    tags:
        project: 'service_6play_users_cloud'
    clients:
        default_client:
            server: 'default_server'
            groups:
                default_group:
                    events:
                        m6kernel.terminate:
                            metrics:
                                -   type: 'increment'
                                    name: 'http_request'
                                    tags:
                                        - 'statusCode'
                                        - 'routeName'
```
In `statusCode` tag, you will return the HTTP code, and in  `routeName` tag you will return the route name.

### Search for a metric with tags

When you want to look for a metric, you have to give the full metric name.
It is not possible with prometheus to look for a partial metric name, 
like we use to do with Graphite.

So, if you are looking for:

* `http`: :x: you won't find any value
* `.*request.*` (with Regex): :x: you will have an error 
* `http_request`: :heavy_check_mark: you will find your values

With Prometheus, you need to use `tags` to perform your researches.

In order to get every __http_request__ metrics sent for __service_6play_users_cloud__ project:

`http_request{project="service_6play_users_cloud"}`

Maybe you want to filter and get only metrics with an HTTP Code 200:

`http_request{project="service_6play_users_cloud",statusCode="200"}`

If you want to get this result only for the __status__ soute:

`http_request{project="service_6play_users_cloud",statusCode="200",routeName="status"}`


### Automatic sums and counts

For each metric, Prometheus will provide a counter and a sum of your metric.

It is easy to find, you just have to add `_sum` or `_count` to get this compiled metric.

So, we can find those two metrics:
* `http_request_count`: that will display the number of times this metric has been called since the beginning
* `http_request_sum`: this will provide the sum of each metric value since the beginning.

@TODO
==============

équivalence avec statsd