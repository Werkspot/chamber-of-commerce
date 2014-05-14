## Installation

Add to composer using:

```
  "require": {
    "werkspot/chamber-of-commerce": "*@dev"
  },
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/Werkspot/chamber-of-commerce.git"
    }
  ],
```

## Usage

```php
$client = new Client();
$retriever = new DutchKvkHtmlRetriever($client, $url);
print_r($retriever->find('01234567');
```


### Timeout modification

Chamber of Commerce retrieval service

It's possible to change the default timeout duration of Guzzle:

```php
$c = new Client('', array(Client::REQUEST_OPTIONS => array('timeout' => 0.001, 'connect_timeout' => 0.002)));
```

or

```php
$c = new Client();
$c->setDefaultOption('timeout', 0.001);
$c->setDefaultOption('connect_timeout', 0.002);
```