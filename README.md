# Laravel Scout MeiliSearch

[![Licence](https://img.shields.io/badge/licence-MIT-blue.svg)](https://img.shields.io/badge/licence-MIT-blue.svg)

The Laravel scout package for MeiliSearch.

MeiliSearch provides an ultra relevant and instant full-text search. Our solution is open-source and you can check out [our repository here](https://github.com/meilisearch/MeiliSearch).

Here is the [MeiliSearch documentation](https://docs.meilisearch.com/) 📖

## Table of Contents

- [Installation](#installation)
- [Getting started](#getting-started)
- [Compatibility with MeiliSearch](#compatibility-with-meilisearch)
- [Additional notes](#additional-notes)

## Installation

### Composer

```bash
$ composer require shokme/laravel-scout-meilisearch
```

### Export configuration

```bash
$ php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
$ php artisan vendor:publish --provider="Shokme\Meilisearch\MeilisearchServiceProvider" --tag="config"
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
$ docker run -it --rm -p 7700:7700 getmeili/meilisearch:latest --master-key=masterKey
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
        Book::whereIn('id', [1, 42])->delete();  
        // Delete all documents /!\
        Book::query()->delete();
    }
}
```
or you can use the artisan command to delete all documents from an index:
```bash
$ php artisan scout:flush "App\Book"
```

### Delete an index
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
        })->limit(3)->get();
    }
}
```

#### Pagination

```php
class BookController extends Controller
{
    public function search()
    {   
        Book::search('prime')->paginate();
        // with a limit of items per page:
        Book::search('prime')->paginate(5);
    }
}
```

## Compatibility with MeiliSearch

This package works for MeiliSearch `>=v0.9.1`.

## Additional notes
You can use more advance function by reading the documentation of [MeiliSearch PHP Client](https://github.com/meilisearch/meilisearch-php)

This package is a custom engine of [Laravel Scout](https://laravel.com/docs/master/scout)
