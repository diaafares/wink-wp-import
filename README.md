# Wink WordPress DB import

Import you WordPress Database to Wink platform easily.  

![](import.gif)

Installation
----
Clone wink-wp-import on your machine, include it in your laravel application via composer using the Path Repository method:
Add this to your composer to JSON
```json
"repositories": [
    {
        "type": "path",
        "url": "./../wink-wp-import"
    }
],
```
Update your `composer.json` file to include this package as a dependency
```json
  "writingink/wp-import": "*@dev"
```

Run `composer update` in your laravel project, then publish the config file into your project by running
```
php artisan vendor:publish
```
Choose `Provider: Wink\WpImport\WinkWpImportServiceProvider` from the list.

This will generate `/config/wink-wp-import.php` file whick look like this:
```php
return [
    'wordpress' => [
        'driver' => 'mysql',
        'host' => env('WB_DB_HOST', 'localhost'),
        'port' => env('WB_DB_PORT', '1433'),
        'database' => env('WB_DB_DATABASE', 'YOUR_DATABASE_NAME'),
        'username' => env('WB_DB_USERNAME', 'USERNAME'),
        'password' => env('WB_DB_PASSWORD', ''),
        'prefix' => env('WB_DB_PREFIX', 'wp_'),
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
];
```
__Update the configurations in this file or add them to your `.env` file like this:__
```php
WB_DB_DATABASE=ADD_YOUR_DB_NAME
WB_DB_USERNAME=ADD_YOUR_DB_USER
WB_DB_PASSWORD=ADD_YOUR_DB_PASSWORD
WB_DB_PREFIX=wp_
```

## Usage
The `Import` command have the following options:
```sh
$ php artisan -h wink-db-tools:import
Description:
  Import WordPress database

Usage:
  wink-db-tools:import [options]

Options:
      --changeAuthor    This option allows you to change the Author of imported posts.
      --withoutPages    This option prevent importing Pages table
      --withoutTags     This option prevent importing Tags table
  -T, --truncate        This option will truncate your local Wink tables (wink_pages, wink_posts, wink_tags, wink_posts_tags, wink_authors) prior to the import operation.
```

#### Import all:
Import all Authors, Pages, Posts and Tags records of WordPress database to Wink corresponding tables:
```sh
php artisan wink-db-tools:import
```

#### Changing imported posts Author:
![](change-author.gif)

To change the Author of all import Posts to a local Wink author use `--changeAuthor` flag: 
```sh
php artisan wink-db-tools:import --changeAuthor
```

#### Exclude Pages records:
To exclude the Pages from the importing operation use `--withoutPages` flag: 
```sh
php artisan wink-db-tools:import --withoutPages
```

#### Exclude Tags records:
To exclude the Tags from the importing operation use `--withoutTags` flag: 
```sh
php artisan wink-db-tools:import --withoutTags
```

#### Truncate Wink tables:
To truncate `wink_pages`, `wink_posts`, `wink_tags`, `wink_posts_tags` and `wink_authors` Wink tables prior to the import operation use `--truncate` flag:
```sh
php artisan wink-db-tools:import --truncate
```

__Of course you can use mulitple flags at the same time if needed.__


## License

**Wink WordPress DB import** is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
