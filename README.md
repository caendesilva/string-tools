# String Tools CLI

A simple CLI tool to manipulate strings in various ways.

> [!WARNING]
> This software is designed primarly for internal use, and is highly opinionated to work for my personal workflow. There are no BC promises and things may change at any time. Use at your own risk.

## Installation

```bash
curl -O https://raw.githubusercontent.com/caendesilva/string-tools/master/string-tools.php
chmod +x string-tools.php
mv string-tools.php /usr/local/bin/string-tools # You can also move it as 'st' for a shorter command
```

## Usage

```bash
php string-tools.php
```

```
String Tools CLI -- Usage: string-tools <command> [args]

help      Display the help screen.
kebab     Convert a string to kebab-case.
snake     Convert a string to snake_case.
camel     Convert a string to camelCase.
studly    Convert a string to StudlyCaps/PascalCase.
lower     Convert a string to lowercase.
upper     Convert a string to UPPERCASE.
title     Convert a string to Title Case.
headline  Convert a string to Headline Case.
slug      Convert a string to a URL-friendly slug.
sentence  Convert a string to Sentence case.
count     Count the number of characters in a string.
words     Count the number of words in a string.
```

### Example: Convert a string to kebab-case

```bash
string-tools kebab "Hello, World!" # Output: hello-world
```

## Attribution and License

This software is released under the MIT license. See [LICENSE.md](LICENSE) for more details.

The single-file executable contains bundled dependencies from the following MIT-licensed projects:
- MinimaPHP - https://github.com/caendesilva/MinimaPHP
- Laravel Illuminate/Support Str class - https://github.com/laravel/framework/blob/11.x/src/Illuminate/Support/Str.php
