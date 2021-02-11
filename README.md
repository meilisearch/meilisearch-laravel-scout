<p align="center">
  <img src="https://res.cloudinary.com/meilisearch/image/upload/v1587402338/SDKs/meilisearch_laravel_scout.svg" alt="MeiliSearch Laravel Scout" width="200" height="200" />
</p>

<h1 align="center">MeiliSearch Laravel Scout</h1>

<h4 align="center">
  <a href="https://github.com/meilisearch/MeiliSearch">MeiliSearch</a> |
  <a href="https://docs.meilisearch.com">Documentation</a> |
  <a href="https://slack.meilisearch.com">Slack</a> |
  <a href="https://roadmap.meilisearch.com/tabs/1-under-consideration">Roadmap</a> |
  <a href="https://www.meilisearch.com">Website</a> |
  <a href="https://docs.meilisearch.com/faq">FAQ</a>
</h4>

<p align="center">
  <a href="https://packagist.org/packages/meilisearch/meilisearch-laravel-scout"><img src="https://img.shields.io/packagist/v/meilisearch/meilisearch-laravel-scout" alt="Latest Stable Version"></a>
  <a href="https://github.com/meilisearch/meilisearch-laravel-scout/actions"><img src="https://github.com/meilisearch/meilisearch-laravel-scout/workflows/Tests/badge.svg" alt="Actions Status"></a>
  <a href="https://github.com/meilisearch/meilisearch-laravel-scout/blob/main/LICENSE"><img src="https://img.shields.io/badge/license-MIT-informational" alt="License"></a>
  <a href="https://app.bors.tech/repositories/29019"><img src="https://bors.tech/images/badge_small.svg" alt="Bors enabled"></a>
</p>

<p align="center">⚡ The MeiliSearch driver for Laravel Scout</p>

**MeiliSearch Laravel Scout** is a **MeiliSearch** driver for Laravel.

**MeiliSearch** is an open-source search engine. [Discover what MeiliSearch is!](https://github.com/meilisearch/MeiliSearch)

## Table of Contents <!-- omit in toc -->

- [📖 Documentation](#-documentation)
- [🔧 Installation](#-installation)
- [🚀 Getting Started](#-getting-started)
  - [Indexes](#indexes)
  - [Search](#search)
- [🤖 Compatibility with MeiliSearch](#-compatibility-with-meilisearch)
- [💡 Learn More](#-learn-more)
- [Development Workflow and Contributing](#development-workflow-and-contributing)

## 📖 Documentation

See our [Documentation](https://docs.meilisearch.com/learn/tutorials/getting_started.html) or our [API References](https://docs.meilisearch.com/reference/api/).

Also, take a look at the [Wiki](https://github.com/meilisearch/meilisearch-laravel-scout/wiki) of this repository!

## 🔧 Installation

### Install the Plugin <!-- omit in toc -->

```bash
$ composer require meilisearch/meilisearch-laravel-scout
```

### Install the HTTP Client <!-- omit in toc -->

You could use any [PSR-18](https://www.php-fig.org/psr/psr-18/) compatible client to use with this SDK. No additional configurations are required.<br>
A list of compatible HTTP clients and client adapters can be found at [php-http.org](http://docs.php-http.org/en/latest/clients.html).

If you use **Laravel 8** you can skip this section as laravel pre-install Guzzle 7 by default.

Guzzle 7:
```bash
$ composer require guzzlehttp/guzzle
```

If you already have guzzle installed with a version < 7, don't forget to update the version inside your composer.json
```json
"require": {
  "guzzlehttp/guzzle": "^7.0"
}
```

Guzzle 6:

```bash
$ composer require php-http/guzzle6-adapter
```

Symfony Http Client:

```bash
$ composer require symfony/http-client nyholm/psr7
```

Curl:

```bash
$ composer require php-http/curl-client nyholm/psr7
```

### Export Configuration <!-- omit in toc -->

```bash
$ php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
$ php artisan vendor:publish --provider="Meilisearch\Scout\MeilisearchServiceProvider" --tag="config"
```

### Update the `.env` file <!-- omit in toc -->

```dotenv
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=masterKey
```

### Run MeiliSearch <!-- omit in toc -->

There are many easy ways to [download and run a MeiliSearch instance](https://docs.meilisearch.com/reference/features/installation.html#download-and-launch).

For example, if you use Docker:
```bash
$ docker run -it --rm -p 7700:7700 getmeili/meilisearch:latest ./meilisearch --master-key=masterKey
```

NB: you can also download MeiliSearch from **Homebrew** or **APT**.

## 🚀 Getting Started

### Indexes

#### Create an Index <!-- omit in toc -->

```bash
// Create an index
$ php artisan scout:index books
// Create an index and give the primary-key
$ php artisan scout:index books --key book_id
```

#### Add Documents <!-- omit in toc -->

```php
<?php

use Laravel\Scout\Searchable;

class Book extends Model
{
    use Searchable;
}
```

```php
<?php

class BookController extends Controller
{
    public function store()
    {
        $book = new Book();
        $book->title = 'Pride and Prejudice';
        ...
        $book->save();
    }
}
```
You can also import all your table to meilisearch by using the artisan command:
```bash
$ php artisan scout:import "App\Book"
```

#### Search in an Index <!-- omit in toc -->

```php
class BookController extends Controller
{
    public function search()
    {
        // MeiliSearch is typo-tolerant:
        Book::search('harry pottre')->get();
        // Or if you want to get the result from meilisearch:
        Book::search('harry pottre')->raw();
    }
}
```

#### Delete Documents <!-- omit in toc -->

```php
class BookController extends Controller
{
    public function destroy($id)
    {
        // Delete one document
        Book::find($id)->delete();
        // Delete several documents
        Book::destroy([1, 42]);
        // Delete all documents /!\
        Book::query()->delete();
    }
}
```
or you can use the artisan command to delete all documents from an index:
```bash
$ php artisan scout:flush "App\Book"
```

#### Delete an Index <!-- omit in toc -->
```bash
$ php artisan scout:index -d books
```

### Search

#### Custom Search <!-- omit in toc -->

All the supported options are described in the [search parameters](https://docs.meilisearch.com/reference/features/search_parameters.html) section of the documentation.

```php
class BookController extends Controller
{
    public function customSearch()
    {
        Book::search('prince', function (Indexes $meilisearch, $query, $options) {
            $options['filters'] = 'author="Antoine de Saint-Exupéry"';

            return $meilisearch->search($query, $options);
        })->take(3)->get();
    }
}
```

#### Pagination <!-- omit in toc -->

```php
class BookController extends Controller
{
    public function search()
    {
        Book::search('mustang')->paginate();
        // with a limit of items per page:
        Book::search('mustang')->paginate(5);
        // using meilisearch response:
        Book::search('mustang')->paginateRaw();
    }
}
```

## 🤖 Compatibility with MeiliSearch

This package only guarantees the compatibility with the [version v0.19.0 of MeiliSearch](https://github.com/meilisearch/MeiliSearch/releases/tag/v0.19.0).

## 💡 Learn More

If you're not familiar with MeiliSerach yet, the following sections may interest you:

- **Manipulate documents**: see the [API references](https://docs.meilisearch.com/reference/api/documents.html) or read more about [documents](https://docs.meilisearch.com/learn/core_concepts/documents.html).
- **Search**: see the [API references](https://docs.meilisearch.com/reference/api/search.html) or follow our guide on [search parameters](https://docs.meilisearch.com/reference/features/search_parameters.html).
- **Manage the indexes**: see the [API references](https://docs.meilisearch.com/reference/api/indexes.html) or read more about [indexes](https://docs.meilisearch.com/learn/core_concepts/indexes.html).
- **Configure the index settings**: see the [API references](https://docs.meilisearch.com/reference/api/settings.html) or follow our guide on [settings parameters](https://docs.meilisearch.com/reference/features/settings.html). Also, the [Wiki](https://github.com/meilisearch/meilisearch-laravel-scout/wiki) of this repository will guide you through the configuration!

💡 You can use more advance function by reading the documentation of [MeiliSearch PHP Client](https://github.com/meilisearch/meilisearch-php).

👍 This package is a custom engine of [Laravel Scout](https://laravel.com/docs/master/scout).

## Development Workflow and Contributing

Any new contribution is more than welcome in this project!

If you want to know more about the development workflow or want to contribute, please visit our [contributing guidelines](/CONTRIBUTING.md) for detailed instructions!

<hr>

**MeiliSearch** provides and maintains many **SDKs and Integration tools** like this one. We want to provide everyone with an **amazing search experience for any kind of project**. If you want to contribute, make suggestions, or just know what's going on right now, visit us in the [integration-guides](https://github.com/meilisearch/integration-guides) repository.
