# `airtable-client`![](https://img.shields.io/packagist/dt/guym4c/airtable-client.svg) 

A modern PHP API client for Airtable.

# Install
via Composer:
```bash
composer require guym4c/airtable-client
```

# Usage
Get yourself an instance of the client:
```php
$airtable = new \Guym4c\Airtable\Airtable(/* YOUR API KEY */, /* YOUR BASE ID */);
```

You can now access CRUD (create, read, update, delete) operations on the tables in your base.
```php
$airtable->get($table, $recordId);
$airtable->create($table, $jsonArray);
$airtable->update($record /* more on this below */); // you may pass an additional boolean as TRUE for a destructive update
$airtable->delete($table, $recordId); // returns a boolean - whether deletion was successful
```

## Records
The client will provide an instance of a `Record` after Get, Create and Update operations. `Record` exposes getters for the table it is in, its Airtable ID, the JSON data array, and the time that it was fetched from Airtable.

You can also access fields in the record as properties of the `Record`:
```php
$myField = $record->field;
```
Where your Airtable field names have spaces in, you can use the curly brace syntax - `$record->{'field name with spaces'};`.

Fields that relate to other tables in the base are automatically detected. If you are accessing the field as a property, the client does not know which table the relation is directed towards, and will return you a `Loader` that you can pass the table name to and resolve the relation. 
```php
$relatedRecord = $record->field->load('relatedTable');
```
You can also just call `$record->load($field, $table);` to get straight to the related record.

## Listing records
You can get more than 1 record by calling `$airtable->list($table)`. You can filter these results by passing an extra `Filter` parameter - see your base's API docs for details on what the properties of `Filter` do.

There are two filter shorthand methods: `search($table, $field, $value)` and `find($table, $field, $value)`. `search()` performs an Airtable search for `$value` in `$table`.`$field`, and as such will return substring matches. `find()` will only return exact matches.

As `list()` results are paginated, `list()` returns the completed `Request` object to you. You can then either get the list of `Record`s that were fetched using `getRecords()`, or move to the next page using `nextPage()`. Bear in mind that the page pointer will eventually reset on Airtable's end.

## Rate limits
Airtable's API is rate-limited to 5 queries per second. If you exceed this limit, the client will throttle requests, blocking as it does. If this rate is exceeded on Airtable's end and you are put into the 30-second penalty box, calls to the API will raise an exception.

### Caching
Commonly, you may have some tables within your base where data is more stagnant. To prevent these from affecting your rate limit, you may provide the client constructor an implementation of the [Doctrine Cache](https://www.doctrine-project.org/projects/doctrine-cache/en/1.8/index.html) interface, and an array of tables you consider 'cachable'.
```php
$airtable = new \Guym4c\Airtable\Airtable($apiKey, $baseId, $cache, ['cachable_table_one', 'cachable_table_two']);
```

The client will respond to requests that have no filters or sorts applied from the cache, and attempt to respond to `get()`, `search()` and `find()` too. You can clear the cache by calling the client's `flushCache()` method.

Cachable tables must have less than 101 records, as the client will not cache tables that Airtable paginates.

## Advanced
The client constructor exposes additional parameters for advanced usage.

```php
public function __construct(
    string $key,
    string $baseId,
    ?CacheProvider $cache = null,
    array $cachableTables = [],
    ?string $apiEndpoint = null,
    array $headers = [],
    bool $isRateLimited = true
) {
```
* `$key` (required): API key
* `$baseId` (required): Airtable base ID
* `$cache`: Doctrine Cache instance
* `$cachableTables`: Tables that are cached by the client
* `$apiEndpoint`: An API endpoint to use as an alternative to the official Airtable API endpoint. Must include the version path.
* `$headers`: Associative array of headers to append to requests
* `$isRateLimited`: Whether to enforce the Airtable rate limit (default `true`)

If you are using the client across multiple applications, you may come up against the rate limit if your clients all attempt to contact Airtable at once. The last three constructor parameters are provided to enable you to proxy requests to Airtable through a central throttle.
