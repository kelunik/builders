<?php

namespace Kelunik\Builders;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

class BuilderGeneratorTest extends TestCase
{
    /** @var BetterReflection */
    private $reflection;

    /** @var string */
    private $source;

    /** @var string */
    private $className;

    /** @var string */
    private $result;

    public function setUp(): void
    {
        $this->reflection = new BetterReflection;
    }

    public function test_public_property(): void
    {
        $this->givenSource('Foo', <<<SOURCE
            <?php
            
            class Foo {
                public \$bar;
            }
SOURCE
        );

        $this->whenBuilderIsBuilt();

        $this->thenGeneratedCodeIs(<<<SOURCE
<?php

class FooBuilderMethods implements Kelunik\Builders\Builder
{
    private \$entity;

    public function __construct()
    {
        \$this->entity = new Foo;
    }

    final public function withBar(\$value)
    {
        \$this->entity->bar = \$value;

        return \$this;
    }

    final public function build(): Foo
    {
        return \$this->entity;
    }
}

SOURCE
        );
    }

    private function givenSource($className, $source): void
    {
        $this->className = $className;
        $this->source = $source;
    }

    private function whenBuilderIsBuilt(): void
    {
        $class = $this->reflect($this->className, $this->source);
        $builderGenerator = new BuilderGenerator($class);
        $this->result = $builderGenerator->generate();
    }

    private function reflect(string $className, string $source): ReflectionClass
    {
        $astLocator = $this->reflection->astLocator();
        $reflector = new ClassReflector(new StringSourceLocator($source, $astLocator));

        return $reflector->reflect($className);
    }

    private function thenGeneratedCodeIs(string $source): void
    {
        $this->assertSame($source, $this->result);
    }

    public function test_public_property_typed(): void
    {
        $this->givenSource('Foo', <<<SOURCE
            <?php
            
            class Foo {
                /** @var string */
                public \$bar;
            }
SOURCE
        );

        $this->whenBuilderIsBuilt();

        $this->thenGeneratedCodeIs(<<<SOURCE
<?php

class FooBuilderMethods implements Kelunik\Builders\Builder
{
    private \$entity;

    public function __construct()
    {
        \$this->entity = new Foo;
    }

    final public function withBar(string \$value)
    {
        \$this->entity->bar = \$value;

        return \$this;
    }

    final public function build(): Foo
    {
        return \$this->entity;
    }
}

SOURCE
        );
    }

    public function test_public_property_typed_nullable(): void
    {
        $this->givenSource('Foo', <<<SOURCE
            <?php
            
            class Foo {
                /** @var string|null */
                public \$bar;
            }
SOURCE
        );

        $this->whenBuilderIsBuilt();

        $this->thenGeneratedCodeIs(<<<SOURCE
<?php

class FooBuilderMethods implements Kelunik\Builders\Builder
{
    private \$entity;

    public function __construct()
    {
        \$this->entity = new Foo;
    }

    final public function withBar(?string \$value)
    {
        \$this->entity->bar = \$value;

        return \$this;
    }

    final public function build(): Foo
    {
        return \$this->entity;
    }
}

SOURCE
        );
    }

    public function test_private_property(): void
    {
        $this->givenSource('Foo', <<<SOURCE
            <?php
            
            class Foo {
                private \$bar;
            }
SOURCE
        );

        $this->whenBuilderIsBuilt();

        $this->thenGeneratedCodeIs(<<<SOURCE
<?php

class FooBuilderMethods implements Kelunik\Builders\Builder
{
    private \$entity;

    public function __construct()
    {
        \$this->entity = new Foo;
    }

    final public function build(): Foo
    {
        return \$this->entity;
    }
}

SOURCE
        );
    }

    public function test_namespace(): void
    {
        $this->givenSource('App\\Foo', <<<SOURCE
<?php
            
namespace App;

class Foo {}
SOURCE
        );

        $this->whenBuilderIsBuilt();

        $this->thenGeneratedCodeIs(<<<SOURCE
<?php

namespace App;

use App;

class FooBuilderMethods implements \Kelunik\Builders\Builder
{
    private \$entity;

    public function __construct()
    {
        \$this->entity = new App\Foo;
    }

    final public function build(): App\Foo
    {
        return \$this->entity;
    }
}

SOURCE
        );
    }

    public function test_public_setter(): void
    {
        $this->givenSource('Foo', <<<SOURCE
            <?php
            
            class Foo {
                private \$bar;
                
                public function setBar(string \$bar): void
                {
                    \$this->bar = \$bar;
                }
            }
SOURCE
        );

        $this->whenBuilderIsBuilt();

        $this->thenGeneratedCodeIs(<<<SOURCE
<?php

class FooBuilderMethods implements Kelunik\Builders\Builder
{
    private \$entity;

    public function __construct()
    {
        \$this->entity = new Foo;
    }

    final public function withBar(string \$value)
    {
        \$this->entity->setBar(\$value);

        return \$this;
    }

    final public function build(): Foo
    {
        return \$this->entity;
    }
}

SOURCE
        );
    }

    public function test_public_setter_nullable(): void
    {
        $this->givenSource('Foo', <<<SOURCE
            <?php
            
            class Foo {
                private \$bar;
                
                public function setBar(?string \$bar): void
                {
                    \$this->bar = \$bar;
                }
            }
SOURCE
        );

        $this->whenBuilderIsBuilt();

        $this->thenGeneratedCodeIs(<<<SOURCE
<?php

class FooBuilderMethods implements Kelunik\Builders\Builder
{
    private \$entity;

    public function __construct()
    {
        \$this->entity = new Foo;
    }

    final public function withBar(?string \$value)
    {
        \$this->entity->setBar(\$value);

        return \$this;
    }

    final public function build(): Foo
    {
        return \$this->entity;
    }
}

SOURCE
        );
    }

    public function test_public_setter_nullable_namespaced(): void
    {
        $this->givenSource('App\Foo', <<<SOURCE
<?php

namespace App;

class Foo {
    private \$bar;
    
    public function setBar(?string \$bar): void
    {
        \$this->bar = \$bar;
    }
}
SOURCE
        );

        $this->whenBuilderIsBuilt();

        $this->thenGeneratedCodeIs(<<<SOURCE
<?php

namespace App;

use App;

class FooBuilderMethods implements \Kelunik\Builders\Builder
{
    private \$entity;

    public function __construct()
    {
        \$this->entity = new App\Foo;
    }

    final public function withBar(?string \$value)
    {
        \$this->entity->setBar(\$value);

        return \$this;
    }

    final public function build(): App\Foo
    {
        return \$this->entity;
    }
}

SOURCE
        );
    }

    public function test_public_setter_default_value(): void
    {
        $this->givenSource('Foo', <<<SOURCE
            <?php
            
            class Foo {
                private \$bar;
                
                public function setBar(?string \$bar = 'foo'): void
                {
                    \$this->bar = \$bar;
                }
            }
SOURCE
        );

        $this->whenBuilderIsBuilt();

        $this->thenGeneratedCodeIs(<<<SOURCE
<?php

class FooBuilderMethods implements Kelunik\Builders\Builder
{
    private \$entity;

    public function __construct()
    {
        \$this->entity = new Foo;
    }

    final public function withBar(?string \$value = 'foo')
    {
        \$this->entity->setBar(\$value);

        return \$this;
    }

    final public function build(): Foo
    {
        return \$this->entity;
    }
}

SOURCE
        );
    }

    public function test_public_setter_default_value_constant(): void
    {
        $this->givenSource('Foo', <<<SOURCE
            <?php
            
            class Foo {
                const X = 'Y';            

                private \$bar;
                
                public function setBar(?string \$bar = self::X): void
                {
                    \$this->bar = \$bar;
                }
            }
SOURCE
        );

        $this->whenBuilderIsBuilt();

        $this->thenGeneratedCodeIs(<<<SOURCE
<?php

class FooBuilderMethods implements Kelunik\Builders\Builder
{
    private \$entity;

    public function __construct()
    {
        \$this->entity = new Foo;
    }

    final public function withBar(?string \$value = Foo::X)
    {
        \$this->entity->setBar(\$value);

        return \$this;
    }

    final public function build(): Foo
    {
        return \$this->entity;
    }
}

SOURCE
        );
    }

    public function test_public_setter_default_value_constant_private(): void
    {
        // TODO: This should error in a future version, because the constant can't be reused in the builder
        $this->givenSource('Foo', <<<SOURCE
            <?php
            
            class Foo {
                private const X = 'Y';            

                private \$bar;
                
                public function setBar(?string \$bar = self::X): void
                {
                    \$this->bar = \$bar;
                }
            }
SOURCE
        );

        $this->whenBuilderIsBuilt();

        $this->thenGeneratedCodeIs(<<<SOURCE
<?php

class FooBuilderMethods implements Kelunik\Builders\Builder
{
    private \$entity;

    public function __construct()
    {
        \$this->entity = new Foo;
    }

    final public function withBar(?string \$value = Foo::X)
    {
        \$this->entity->setBar(\$value);

        return \$this;
    }

    final public function build(): Foo
    {
        return \$this->entity;
    }
}

SOURCE
        );
    }
}