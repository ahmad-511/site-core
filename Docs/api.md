# SiteCore API reference

## App Class
This class provides necessary functions used in different places through out the system

### Properties:

### Functions:

```php
App::getTimezoneOffset($timezone = null)
```

Get timezone offset for specified timezone (use system default timezone if omitted)
* @param `string | null` $timezone Valid php timezone
* @return `string` timezone offset as `+/- H:i` compatible with MySql/MariaDB `time_zone` variable


```php
App::includeMeta($pageCode)
```

Used inside a layout file,
Include HTML page's meta tags (`description, keywords, title, social og:title, og:description, og:image, og:url, twitter:card`)
* @param `string` $pageCode (viewCode)

```php
App::includeFiles($pageCode)
```

Used inside a layout file,
Include page related files (css, js, js/module)
* @param `string` $pageCode (viewCode)

```php
App::setSelectedPage($pageCode)
```

Check if specified page code is currently selected one
* @param `string` $pagecode (viewCode)
* @return `string` selected | ''

```php
App::loc($str, $lang='')
```
Localize specified string using specified language (use App's current language if omitted)
* @param `string` $str string to be localized
* @param `string|empty` $lang language code
* @return `string` Localized version of the input $str or the same $str if not found in specified language dictionary

```php
App::getPageOffset($pageNum)
```
Get page record offset for pagination purposes
* @param `int` $pageNum Page number
* @return `int` Page's start record offset
