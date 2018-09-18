Prometheus in Grafana 
======

(:warning: Work in progress)

Getting started with Prometheus
------

### Configuration example

This is a configuration example that we will use for this documentation:

```yaml
m6web_statsd_prometheus:
    servers: [...]
    tags:
        project: 'my_project'
    clients:
        default_client:
            server: 'default_server'
            groups:
                default_group:
                    events:
                        m6kernel.terminate:
                            metrics:
                                -   type: 'increment'
                                    name: 'http_request_total'
                                    tags:
                                        status: 'statusCode'
                                        route: 'routeName'
                                        
                                -   type: 'timer'
                                    name: 'http_request_seconds'
                                    param_value: 'getTiming'
                                    tags:
                                        status: 'statusCode'
                                        route: 'routeName'
```

In `status` tag, you will return the HTTP code, and in  `route` tag you will return the Symfony route name.

### Querying your metrics with PromQL

PromQL is the query language used to get your prometheus metrics. 
This is the language you will use in Grafana.

Please have a look at the [Prometheus documentation](https://prometheus.io/docs/prometheus/latest/querying/basics/) in order to understand the basics about PromQL.


Prometheus provides a convenient system to perform your researches: __tags__ (aka labels).
Those tags are added to your metric name, and allow you to filter the metrics.

For example, when you want to find the data for the metric `http_request_total`, this how you can do it:

`http_request_total`

Using tags, you can filter this metric for a specific context. The syntax is:

`metric_name{tag_name="tag_value"}`

In the configuration example, we have set up a tag named "project" which has the value "MonitoringEvent_6play_users_cloud". 
So, if we want to get the metrics __http_request__ metrics sent for __my_project__ project, 
we would do this:

`http_request_total{project="my_project"}`

Maybe you want to filter and get only metrics with an HTTP Code 200:

`http_request_total{project="my_project",statusCode="200"}`

Or maybe get, every status code that begins with "2..":

`http_request_total{project="my_project",statusCode=~"2.+"}`


If you want to get this result only for the __status__ route:

`http_request_total{project="my_project",statusCode="200",routeName="status"}`

You can only use regex on the tags value. The metric name is not queryable like this.
For this purpose, Prometheus injects the metric name in an internal tag named  `__name__`.

So if you want to query on the metric name, for example, getting all the metrics beginning with `request_`,
you could do this:

`{__name__=~"request_.+"}`

:information_source: Note: `{__name__="request_http",statusCode="200"}` = `request_http{statusCode="200"}` 

### Metric types

In Prometheus, there are 4 metric types :
* `Counter`: that matches the Statsd __counter__
* `Gauge`: that matches the Statsd __gauge__
* `Summary`: that matches the Statsd __timer__
* `Histogram`: that may be similar to summary. This metric is not supported by the StatsdPrometheusBundle yet.


#### Counter

(:warning: Work in progress)

Counter are values that will always increment. 

To get the value for a "real-time" moment, you will need to use the `increase` function over a range time.

For example, if we want to display the number of request over time, we would do this:

`increase(http_request_total[2m])` 


##### Average over time: using rate

One way to calculate an average value is to use the `rate` function.

This function gives you an average rate per second of your value. 


Given:
* your counter: http_request_seconds_sum
* filtered on the __my_project__ project
* the range time will be 2 minutes (with actual settings, we cannot have smaller ranges, Prometheus scrappes every minute)

Then, this is how your average rate per second:
```
rate(http_request_seconds_count{project="my_project"}[2m])
```

To be able to have your rate __per minute__, you'll need to multiply by 60.
```
rate(http_request_seconds_count{project="my_project"}[2m]) * 60
```


#### Gauge

(:warning: Work in progress)

You can use the `delta` function to get the difference between 2 values.

#### Histogram

Not supported yet.

#### Summary / Timer

(:warning: Work in progress)

##### Aggregates sum, count and percentiles

For each metric of this kind, Prometheus will provide automatically some aggregates: 
* a `total sum` of your metric,  
* a `total counter` (number of time your metric has been sent): The automatic counter will __dispense__ us to use an 
__additional increment__ metric.
* The repartition of your data over the `0.99 percentile`
* The repartition of your data over the `0.9 percentile`
* The repartition of your data over the `0.5 percentile`


The aggregates are easy to find, you just have to append `_sum` (for the sum) or `_count` (for the counter)
 at your metric name.
 
The percentiles are sent as tags, with the tag nam: `quantile`.
 
So, according to our example, this is how we can use those aggregates:
* `http_request_seconds_count`: that will display the number of times this metric has been called since the beginning
* `http_request_seconds_sum`: this will provide the sum of each metric value since the beginning.
* `http_request_seconds[quantile="0.99"]`: this will provide the value of the 0.99 percentile
* and so on...


Graphs with Grafana
------

### Differences between Statsd data and Prometheus data

We can observe some differences between the Statsd graphs and the Prometheus one.

It is related to Prometheus way of collecting data.
Indeed, Statsd metrics are sent in "real time" and aggregated more regularly, 
so we have a more accurate "real time" value.
Prometheus collects data every 1 minute (with current seting), so some values are aggregated for a longer time range. 
The consequence is having a bigger amount for a sum or a counter in one point instead of having two points (for example).

I think it is working like this:

| minutes | metric value | Statsd: result per minute | Prometheus (result every 1 minute) | 
| --- | --- | --- | --- |
| 0 | 2 | 2 | 0 (not gathered yet) |
| 1 | 12 | 12 | 14 (2 + 12) |
| 2 | 4 | 4 | 0 (not gathered yet) |
| 3 | 8 | 8 | 12 (4 + 8) |
| 4 | 6 | 6 | 0 (not gathered yet) |
| 5 | 9 | 9 | 15 (6 + 9) |

So the graphs would be slightly different.