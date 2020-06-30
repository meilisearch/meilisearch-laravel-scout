<p align="center">
  <img src="https://res.cloudinary.com/meilisearch/image/upload/v1587402338/SDKs/meilisearch_laravel_scout.svg" alt="MeiliSearch Laravel Scout" width="200" height="200" />
</p>

<h1 align="center">MeiliSearch Laravel Scout</h1>

<h4 align="center">
  <a href="https://github.com/meilisearch/MeiliSearch">MeiliSearch</a> |
  <a href="https://www.meilisearch.com">Website</a> |
  <a href="https://blog.meilisearch.com">Blog</a> |
  <a href="https://twitter.com/meilisearch">Twitter</a> |
  <a href="https://docs.meilisearch.com">Documentation</a> |
  <a href="https://docs.meilisearch.com/faq">FAQ</a>
</h4>

<p align="center">
  <a href="https://packagist.org/packages/meilisearch/meilisearch-laravel-scout"><img src="https://img.shields.io/packagist/v/meilisearch/meilisearch-laravel-scout" alt="Latest Stable Version"></a>
  <a href="https://github.com/meilisearch/meilisearch-laravel-scout/actions"><img src="https://github.com/meilisearch/meilisearch-laravel-scout/workflows/Tests/badge.svg" alt="Actions Status"></a>
  <a href="https://github.com/meilisearch/meilisearch-laravel-scout/blob/master/LICENSE"><img src="https://img.shields.io/badge/license-MIT-informational" alt="License"></a>
  <a href="https://slack.meilisearch.com"><img src="https://img.shields.io/badge/slack-MeiliSearch-blue.svg?logo=slack" alt="Slack"></a>
</p>

<p align="center">⚡ Lightning Fast, Ultra Relevant, and Typo-Tolerant Search Engine MeiliSearch driver for Laravel Scout</p>

**MeiliSearch Laravel Scout** is a **MeiliSearch** driver for Laravel. **MeiliSearch** is a powerful, fast, open-source, easy to use and deploy search engine. Both searching and indexing are highly customizable. Features such as typo-tolerance, filters, and synonyms are provided out-of-the-box.

## Table of Contents

- [Installation](#installation)
- [Getting started](#getting-started)
- [Compatibility with MeiliSearch](#compatibility-with-meilisearch)
- [Development Workflow](#development-workflow)
- [Additional notes](#additional-notes)

## Installation

### Composer

```bash
$ composer require meilisearch/meilisearch-laravel-scout
```

### Export configuration

```bash
$ php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
$ php artisan vendor:publish --provider="Meilisearch\Scout\MeilisearchServiceProvider" --tag="config"
```

### Update .env
```dotenv
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=masterKey
```

### Run MeiliSearch

There are many easy ways to [download and run a MeiliSearch instance](https://docs.meilisearch.com/guides/advanced_guides/installation.html#download-and-launch).

For example, if you use Docker:
```bash
$ docker run -it --rm -p 7700:7700 getmeili/meilisearch:latest ./meilisearch --master-key=masterKey
```

NB: you can also download MeiliSearch from **Homebrew** or **APT**.

## Getting started

### Indexes

#### Create an index

```bash
// Create an index
$ php artisan scout:index books
// Create an index and give the primary-key
$ php artisan scout:index books --key book_id
```

#### Add document

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

#### Search in index

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

#### Delete documents

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

#### Delete an index
```bash
$ php artisan scout:index -d books
```

### Search

#### Custom search

All the supported options are described in [this documentation section](https://docs.meilisearch.com/references/search.html#search-in-an-index).

```php
class BookController extends Controller
{
    public function customSearch()
    {
        Book::search('prince', function (Index $meilisearch, $query, $options) {
            $options['filters'] = 'author="Antoine de Saint-Exupéry"';

            return $meilisearch->search($query, $options);
        })->take(3)->get();
    }
}
```

#### Pagination

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

## Compatibility with MeiliSearch

This package is compatible with the following MeiliSearch versions:
- `v0.11.X`

## Development Workflow

If you want to contribute, this section describes the steps to follow.

Thank you for your interest in a MeiliSearch tool! ♥️

### Install dependencies

```bash
$ composer install
```

### Tests and Linter

Each PR should pass the tests to be accepted.

```bash
$ vendor/bin/phpunit --color tests/
```

### Release

MeiliSearch tools follow the [Semantic Versioning Convention](https://semver.org/).

Once the changes are merged into `master`, you must create a release (with this name `vX.X.X`) via the GitHub interface.<br>
A webhook will be triggered and push the new package on [Packagist](https://packagist.org/packages/meilisearch/meilisearch-laravel-scout).

## Additional notes

You can use more advance function by reading the documentation of [MeiliSearch PHP Client](https://github.com/meilisearch/meilisearch-php)

This package is a custom engine of [Laravel Scout](https://laravel.com/docs/master/scout)

<hr>

**MeiliSearch** provides and maintains many **SDKs and Integration tools** like this one. We want to provide everyone with an **amazing search experience for any kind of project**. If you want to contribute, make suggestions, or just know what's going on right now, visit us in the [integration-guides](https://github.com/meilisearch/integration-guides) repository.
