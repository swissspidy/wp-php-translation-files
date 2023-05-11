# WP PHP Translation Files

[![Commit activity](https://img.shields.io/github/commit-activity/m/swissspidy/wp-php-translation-files)](https://github.com/swissspidy/wp-php-translation-files/pulse/monthly)
[![Code Coverage](https://codecov.io/gh/swissspidy/wp-php-translation-files/branch/main/graph/badge.svg)](https://codecov.io/gh/swissspidy/wp-php-translation-files)
[![License](https://img.shields.io/github/license/swissspidy/wp-php-translation-files)](https://github.com/swissspidy/wp-php-translation-files/blob/main/LICENSE)

Use plain PHP files for WordPress translations instead of `.mo` files.

The WordPress i18n system uses gettext, which operates with source `.po` (Portable Object) files and binary `.mo` (Machine Object) files for storing and loading translations. is not trivial.
With this solution, translations will instead be stored in plain `.php` files returning an associate array of translation strings.
Whenever a `.php` file is available, it will be preferred over the `.mo` file, which is still used as a fallback.

Ideally, the `.php` files will be served directly from translate.wordpress.org.
For this proof of concept, the `.php` files are created automatically after translation updates and also on demand when loading `.mo` files.

## Format

```php
<?php
return [
	'translation-revision-date' => '+0000',
	'generator' => 'WordPress/6.3-alpha-55629',
	'messages' => [
		'Foo' => ['Bar'],
		'Bar' => ['Baz'],
		'1 Dragon' => ['1 Dragon', '2 Dragons'],
	]
];
```

## Performance

Nothing is faster in PHP than loading and executing another PHP file. JSON, INI, or XML would all be much slower.

Some initial measurements taken for a typical frontend request:

  | English |   | MO |   | PHP |  
-- | -- | -- | -- | -- | -- | --
  | Load (s) | Memory (B) | Load (s) | Memory (B) | Load (s) | Memory (B)
Median | 0.31 | 14,639,624 | 0.40 | 24,442,272 | 0.36 | 22,852,584
Difference |   |   | 29.58% | 66.96% | 17.35% | 56.10%
Difference to MO  |   |   |   |   | -9.44% | -6.50%

In short, this reduces load time by ~10% and memory usage by ~7% for localized WordPress sites.
