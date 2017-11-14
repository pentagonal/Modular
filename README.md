# MODULAR

Easy php module storage collector for small project

| dev-master | 1.0.0     |
|------------|-----------|
| [![Build Status](https://travis-ci.org/pentagonal/Modular.svg?branch=master)](https://travis-ci.org/pentagonal/Modular?branch=master) | [![Build Status](https://travis-ci.org/pentagonal/Modular.svg?branch=1.0.0)](https://travis-ci.org/pentagonal/Modular?branch=1.0.0)
| [![Coverage Status](https://coveralls.io/repos/github/pentagonal/Modular/badge.svg?branch=master)](https://coveralls.io/github/pentagonal/Modular?branch=master) | [![Coverage Status](https://coveralls.io/repos/github/pentagonal/Modular/badge.svg?branch=1.0.0)](https://coveralls.io/github/pentagonal/Modular?branch=1.0.0)

##

```
Embed the script without change the core.
just like as add-ons, plugins or whatever it names.
```

# REQUIREMENTS

```txt
- Php 7.0 or later
- Php tokenizer extension enabled
- `spl` enabled
- function `eval` allowed (for validate php file - optional)
```
## USAGE

```php
<?php
use Pentagonal\Modular\Reader;

$reader = new Reader('/Path/To/Directory');
$reader->configure();
$modules = $reader->getValidModules();

// init all module
foreach ($modules as $moduleIdentifier => $module) {
    // initialize per module
    // this only called once on module base
    $module->finalInitOnce();
}

```
## LIBRARIES

- [`pentagonal/array-store "~1"`](https://github.com/pentagonal/ArrayStore)
- [`pentagonal/php-evaluator "~1"`](https://github.com/pentagonal/PhpEvaluator)

## LICENSE

MIT [LICENSE](LICENSE)
