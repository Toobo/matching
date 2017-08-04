# Toobo Matching

For devs who ever played with functional programming languages, one of the very missing feature in 
PHP is **"pattern matching"**.

I will *not* try to explain what pattern matching is.

I will just say that this library is an attempt to provide in PHP something _inspired_ by it.


## What is this?

In a nutshell, this library provides a function that given a set of callbacks (anything `callable` 
in PHP), each with different signature, returns another callback that can be executed with 
arbitrary arguments.

The returned callback acts as a _proxy_ to the original set of given callbacks, and executes only one
of them. The callback to execute is chosen trying to find the _best match_ between the arguments 
that are passed to the proxy callback and the signature of originally given callbacks.


## Clarify by examples

```php
use Toobo\Matching\Matcher;

$callback = Matcher::for(
    function(string $name) {
        return "Hi, my name is $name.";
    },
    function(int $age) {
        return "I am $age yeas old.";
    }
);

$callback('Giuseppe'); // "Hi, my name is Giuseppe."

$callback(35);         // "I am 35 yeas old."
```

In the snippet above, among the callbacks passed to `Matcher::for()` the one whose arguments *type* 
matched the values passed to `$callback` is executed.

Argument type is not the only factor that makes a callback "match", another factor is the number of
accepted parameters.

For example:

```php
$callback = Matcher::for(
    function(string $name, int $age) {
        return "Hi, my name is $name and I am $age yeas old.";
    },
    function(int $age, string $name) {
        return "Hi, my name is $name and I am $age yeas old.";
    },
    function(int $children) {
        return $children === 1 ? 'I have 1 child.' : "I have $children children.";
    }
);

$callback('Giuseppe', 35); // "Hi, my name is Giuseppe and I am 35 yeas old."
$callback(35, 'Giuseppe'); // "Hi, my name is Giuseppe and I am 35 yeas old."
$callback(1);              // "I have 1 child."
```

In the example above, when two arguments are passed to `$callback`, the function to execute is 
chosen based on the type of received arguments.

When a single argument is passed, the only callback that could match is the 3rd, which actually 
matches because the type of value passed (integer) matches the callback type declaration.


## Dealing with no matches

What does happen if no callback matches?

Simply, an exception of type `Toobo\Matching\Exception\NotMatched` (which extends 
`Toobo\Matching\Exception\Exception` which extends `\TypeError`) is thrown.

However, the `$callback` returned by `Matcher::for()` is actually an instance of `Toobo\Matching\Matcher`
which, besides of `__invoke()` method that makes it `callable`, has another method: `failWith()` that 
can be used to provide an additional callback to be ran if none of the callbacks passed to 
`Matcher::for()` matched:

```php
$callback = Matcher::for(
    function(string $name) {
        return "Hi, my name is $name.";
    }
)->failWith(function(...$args) {
    return "Sorry, I don't know what you mean.";
});

$callback('Giuseppe'); // "Hi, my name is Giuseppe."
$callback(true);       // "Sorry, I don't know what you mean."
```

which means that `failWith()` prevents the exception to be thrown. 

Note that `failWith()` receives all the arguments that where passed to `$callback`, just like any
callback passed to `Matcher::for()`.


## Dealing with no type declaration and "specificity"

In PHP type declaration is completely optional. When given callbacks have parameters with no type declaration, 
those will act as "wildcard" matching any value.

```php
$callback = Matcher::for(
    function(string $name, int $age) {
        return "I'm $name and I'm $age years old."
    }
    function($anything, int $age) {
        return "I'm $age years old.";
    }
);

$callback('Giuseppe', 35); // "I'm Giuseppe and I'm 35 years old."
$callback(true, 35);       // "I'm 35 years old."
```

### Specificity

Because of parameters with no type declaration it is possible that more callbacks with 
different signature match.

To decide which one should be executed, the library calculates a **"specificity"** value.

**Specificity is equal to the number of type declaration that actually matched.**

The callback that matches with highest value of specificity is executed.

```php
$callback = Matcher::for(
    function($foo, $bar) {
        return "First"
    }
    function($foo, int $bar) {
        return "Second";
    }
);

$callback('a', 'b'); // "First"
$callback('a', 1);   // "Second"
```

In the example above, both given callbacks requires two arguments, but the first callback can match 
only when the second argument is not an integer.

If fact, when second argument is an integer, the second callback will match with a specificity of `1`
whereas the first callback will match with a specificity of zero, because has no type declarations.

Note that specificity is not calculated based on type declaration in callback signature, but based
 on the actual values passed to the generated callback.

This is relevant in case of variadics or in case of parameters with default.

For example:

```php
$callback = Matcher::for(
    function(int ...$numbers) {
       
    }
    function(string $foo, int $bar = 0) {
        
    }
);
```

In the snippet above, the first callback might match with a *variable specificity* that will be equal 
or bigger than `0`: its specificity, in fact, will be actually equal to the number of arguments that 
will be passed to `$callback` (assuming they are all integers otherwise the callback does not match at all).
 
The second callback might match either with a specificity of `1` (when `$callback` is executed passing 
a single string argument), or with a specificity of `2` (when two arguments are passed and the first is 
an string and the second is an integer).


## Dealing with defaults and "weight"

Callbacks passed to `Matcher::for()` might have some parameters with defaults.

That will be taken into account for matching. For example:

```php
$callback = Matcher::for(
    function(string $name) {
        return "Hi, my name is $name.";
    }
    function(string $name, int $age, array $children = []) {
    
        $msg   = "I'm $name ($age)";
        $count = count($children);
        
        if ($count === 1) {
            $msg .= ' I have 1 child, their name is ' . reset($children) . '.';
        } elseif($count > 1) {
            $msg .= "I have $count children: " . implode(', ', $children) . '.';
        }
        
        return $msg;
    }
);

$callback('Giuseppe');                // "Hi, my name is Giuseppe."
$callback('Giuseppe', 35);            // "I'm Giuseppe (35)."
$callback('Giuseppe', 35, ['Sofia']); // "I'm Giuseppe (35). I have 1 child, their name is Sofia."
```

The second callback matched when either 2 or 3 arguments were passed to it, because its third argument
is optional.

### Weight

An effect of taking defaults into account is that more callbacks with different signatures could 
match with the same specificity.

For example:

```php
$callback = Matcher::for(
    function(string $name, int $age = -1) {
        $msg = "I'm $name";
        return $age > 0 ? "$msg ($age)." : "$msg."
    }
    function(string $name) {
        return "Hi, my name is $name.";
    }
);

$callback('Giuseppe'); // "Hi, my name is Giuseppe."
```

In snippet above `$callback` is called with a single string argument, and both given callbacks match. 

And both matches with a specificity of `1`, because there's `1` param and both callbacks have type 
declaration for it.

However, the callback being executed is the second, which signature only contains a single parameter.

The reason is that when there are more callbacks matching with same specificity, a **"weight"** value 
is calculated to decide which one will be executed.

The weight is calculated as **the total number of received values, minus the modulus of difference
between number of actually received values and declared callback parameters**.

An example might clarify.

In the last snippet above, the first callback matched with a weight of `0`: 

```php
// $weight = {n. of total args} - abs( {n. of total args} - {n. of params in signature} )
$weight = 1 - abs(1 - 2); // 0
```
 
whereas the second callback matched with a weight of `1`:

```php
$weight = 1 - abs(1 - 1); // 1
```

So the second callback is executed because of higher weight.

Worth repeating: _weight_ is used to decide which callback execute when more than one callback
match **with same _specificity_**; if multiple callbacks match, and one of them matches with 
higher specificity tha tone is executed, no matter the _weight_.

 
## Dealing with variadics

The way variadics arguments are treated is based on the principle of _least astonishment_.

First of all, if a variadic argument has a type declaration, it will work _as expected_.

Moreover, in PHP variadic parameters are always optional, and so they are considered.
  
And because of least astonishment principle, internally **a special treatment is reserved to variadics in 
the regard of weight calculation: when callbacks have a variadic parameter their weight is 
calculated starting from `0` and not from total number of arguments**.
 
The following example should clarify the reason. Given:

```php
$callback = Matcher::for(
    function(...$args) {
        
    }
    function($a = 'x', $b = 'y', $c = 'z') {
    }
);

$callback('foo', 'bar');
```

Without knowing the internal of weight calculation, one is naturally led to assume the matching 
callback is the second.

However, without applying the special weight calculation for variadics, both callbacks would have a
weight of `1` (because the modulus of difference between received arguments and declared params is 
`1` in both cases), and so the executed function would not be predictable.
 
Thanks to "special" weight calculation applied to variadics, the function with variadic argument 
matches with a weight of `-1` and the other callback, having higher weight, is executed as one could 
expect.

However, when variadics also include type declaration, their specificity increases with the number of
arguments passed, easily increasing the chances of matching.
  
For example:

```php
$callback = Matcher::for(
    function(int...$numbers) {
        return 'These numbers matched: ' . implode(', ', $numbers);
    }
    function(int $age) {
        return "I'm $age years old.";
    },
    function($a, int $b, $c) {
        return implode(', ', [$a, $b, $c]);
    }
);

$callback(1, 2, 3); // "These numbers matched: 1, 2, 3"

$callback(35);      // "I'm 35 years old."
```

In first case (when passing three integers) the first and the third callbacks match, however the first 
(variadic) callback "wins" because it matches with a specificity of `3` (it receives `3` arguments 
which satisfied the variadic type declaration), whereas the third callback matches with a specificity of `1`.

In the second case (when passing a single integer) the first and the second callback match, but the 
latter is executed, because for both callbacks specificity is equal to `1`, but the
variadic callback matches with a weight of zero, whereas the callback executed matches with a weight 
of `1`.

  
### The "catch all" variadic callback
  
Something might not immediately clear is that a callback like this:  

```php
$callback = Matcher::for(
    function(...$args) {
        // something here
    }
);
```

will *always* match.

Because there's no type declaration so it accepts _any kind_ of arguments and the variadic param is 
validated for _any number_ of arguments.

However, the *specificity* of such callback will always be *zero*, because there's no type declaration, 
and the *weight* will be equal or less of *zero* (the more arguments passed, the lower the weight).

With such low *specificity* and *weight* this kind of callback is at any effect a sort of "fallback"
that will always match if nothing *more suitable* matches.

It also means that such callback could be used in place of `failWith()` to handle the case nothing 
else matched (and anyway if such callback is used, eventual `failWith()` callback would never be called), 
but there's a difference: the callback passed to `failWith()`, unlike callbacks passed to 
`Matcher::for()`, can't be "bound" to an object.

More on *callback binding* below.


## Callbacks binding

Even if all the examples in this README makes use of anonymous functions fore readability sake, 
`Matcher::for()` accepts anything that is `callable` in PHP.

However, internally, it always stores the *closure version* of given callbacks that is obtained with
[`\ReflectionFunction::getClosure()`](http://php.net/manual/fr/reflectionfunction.getclosure.php) 
(or [`\ReflectionMethod::getClosure()`](http://php.net/manual/en/reflectionmethod.getclosure.php)).

Always storing closures allows to "bind" given callbacks to arbitrary objects, opening interesting
 possibilitites.

To bind an object is done by calling the method `Matcher::bindTo()` passing the object that should be 
used as the `$newthis` for given callbacks.

For example:

```php
$matcher = Matcher::for(
    function(string $param) {
         return $this->offsetExists($param) ? $this[$param] : null;
    },
    function(int $param) {
        $values = array_values($this->getArrayCopy());
        return array_key_exists($param, $values) ? $values[$param] : null;
    },
    function(string ...$params) {
        return array_map(function($param) {
            return $this->offsetExists($param) ? $this[$param] : null;
        }, $params);
    }
);

$matcher = $matcher->bindTo(new \ArrayObject(['foo' => 'Foo!', 'bar' => 'Bar!']));


$matcher('foo');                // "Foo!"
$matcher(1);                    // "Bar!"
$matcher('foo', 'meh', 'bar');  // ["Foo!", null, "Bar!"]
```

Even if the matcher has many callbacks the callback binding is quite efficient, because **it is only
done for the matching callback**.

Actually, just nothing happen when `Matcher::bindTo()` is called: the passed object is stored, and
will be used to bound the matching callback in the moment a matching callback happen to be
calculated.

This also means that `Matcher::bindTo()` can efficiently be called on same matcher instance with 
different objects.


### Not bindable callbacks

Some callbacks can't be bound to objects. For example, closures declared as `static` or plain 
functions.

This is not an issue. When `Matcher::bindTo()` is called, and the matched callback is not bindable,
just nothing is done. Considering that callbacks that are not bindable can't contain any `$this`
reference this is just not relevant.


## An almost real world usage example

In some languages, e.g. Java, an object might have more than one constructor, each with a different 
signature. When the object is instantiated, the constructor to use is chosen based on the parameters
passed to constructor, matching them by type, like this library does.

Such flexibility in object construction can be obtained in PHP with the usage of conditionals
in the constructor (increasing cyclomatic complexity and decreasing readability) and giving up 
to type-safe constructor parameters.

With the help of this library, it is possible to mimic in PHP the Java multiple constructors
feature keeping type-safe parameters and without usage of conditionals.

For example:

```php
namespace Example;

use Toobo\Matching\Matcher;

class Person {

    private static $factory;
    
    private static function buildFactory(): Matcher {
    
        self:$factory or self:$factory = Matcher::for(
        
            function(string $firstname, string $lastname, int $age, string $email = '') {
                $this->fullname = "$firstname $lastname";
                $this->age      = $age;
                $this->email    = $email;
            },
            function(string $fullname, int $age, string $email = '') {
                $this->fullname = $fullname;
                $this->age      = $age;
                $this->email    = $email;
            },
            function(int $age, string $fullname, string $email = '') {
                $this->age      = $age;
                $this->fullname = $fullname;
                $this->email    = $email;
            }
        );
        
        return self:$factory;
    }
    
    public function __construct(...$args) {
    
        self::buildFactory()->bindTo($this)(...$args);
    }
    
    public function introduce(): string {
        
        $out = "My name is $this->fullname and I am $this->age years old.";
        if ( $this->email ) {
            $out .= " My email address is '$this->email'.";
        }
        
        return $out;
    }
}
```

And the flexible constructor in action:

```php
(new Person('Giuseppe', 'Mazzapica', 35))
    ->introduce();
// My name is Giuseppe Mazzapica and I am 35 years old.
    
(new Person('Giuseppe Mazzapica', 35))
    ->introduce();
// My name is Giuseppe Mazzapica and I am 35 years old.
    
(new Person(35, 'Giuseppe Mazzapica'))
    ->introduce();
// My name is Giuseppe Mazzapica and I am 35 years old.
    
(new Person(35, 'Giuseppe Mazzapica', 'gm@example.com'))
    ->introduce();
// My name is Giuseppe Mazzapica and I am 35 years old. My email address is 'gm@example.com'.
```

So we have flexibility _and_ type safety: calling the constructor of `Person` with something that
does not match the type of internal callbacks will throw an exception (that extends `TypeError`, 
the same throwable thrown by PHP when a type declaration does not match).


## Ok, nice, but should I use this in production?

I'm quite confident the code is production ready, and test coverage is close to 100%.

However, to do its work the Matching uses a lot of reflections and closures generation which are
not among the fastest operations in PHP.

To be honest, however, at the moment I did no performance profiling at all.


## Why does this exist?

For fun (my fun), mostly. Also, why not?


## Requirements

- PHP 7+
- Composer to install


## Installation

Via Composer, `toobo/matching` on packagist.org.


## License

Matching is open source and released under MIT license. See LICENSE file for more info.