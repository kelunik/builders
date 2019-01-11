# builders

This packages allows generating builders for value objects automatically. One use case is creating test objects for unit and integration tests.

## Installation

```
composer install kelunik/builders
```

## Usage

Given the following `User` object, the builder generator will generate a `UserBuilderMethods` class.

```php
<?php

namespace Example;

class User
{
    /** @var int|null */
    private $id;
    /** @var string|null */
    private $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
```

```php
<?php

namespace Example;

use Example;

class UserBuilderMethods implements \Kelunik\Builders\Builder
{
    private $entity;

    public function __construct()
    {
        $this->entity = new Example\User;
    }

    final public function withId(?string $value)
    {
        $this->entity->setId($value);

        return $this;
    }

    final public function withName(?string $value)
    {
        $this->entity->setName($value);

        return $this;
    }

    final public function build(): Example\User
    {
        return $this->entity;
    }
}
```

### Custom Builder Methods

In order to use the generated classes, you're advised to create a `UserBuilder` object that extends the generated `UserBuilderMethods`.
This separation allow you to add custom builder methods without affecting the builder generator when it throws away the old `UserBuilderMethods` and generates a new one.

```php
<?php

namespace Example;

class UserBuilder extends UserBuilderMethods
{
    public function root()
    {
        return $this->withId(1)->withName('root');
    }
}
```

### Builder Consumption

Building test objects is easiest if you add a `builders.php` defining functions as shortcuts.

```php
<?php

namespace Example;

function user() {
    return new UserBuilder;
}
```

```php
<?php

use Example\User;
use function Example\user;

require __DIR__ . '/../vendor/autoload.php';

$user = a(user()->root()->withName('kelunik'));

var_dump($user instanceof User);
var_dump($user);
```

### Code Generation

The code generator can be invoked with the following command, paths are relative to the composer root directory:

```
vendor/bin/builder-generator App\NamespaceOfValueObjects src src-generated
```
