<?php

namespace Kelunik\Builders;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpLiteral;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\PsrPrinter;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionProperty;

class BuilderGenerator
{
    /** @var ReflectionClass */
    private $class;

    /** @var Printer */
    private $printer;

    /** @var ClassType */
    private $builderClass;

    public function __construct(ReflectionClass $class, ?Printer $printer = null)
    {
        $this->class = $class;
        $this->printer = $printer ?? new PsrPrinter;
    }

    public function generate(): string
    {
        $this->builderClass = new ClassType($this->getBuilderSimpleName());
        $this->builderClass->addImplement(Builder::class);
        $this->addEntityProperty();
        $this->addConstructor();
        $this->addProperties($this->class);
        $this->addBuildMethod();

        $namespace = new PhpNamespace($this->class->getNamespaceName());
        $namespace->add($this->builderClass);
        $namespace->addUse($this->class->getNamespaceName());

        return "<?php\n\n" . $this->printer->printNamespace($namespace);
    }

    public function getBuilderName(): string
    {
        return $this->class->getNamespaceName() . '\\' . $this->getBuilderSimpleName();
    }

    public function shouldGenerateBuilder(): bool
    {
        if (\strpos($this->class->getName(),
                'BuilderMethods') === \strlen($this->class->getName()) - \strlen('BuilderMethods')) {
            return false;
        }

        if (\strpos($this->class->getName(), 'Builder') === \strlen($this->class->getName()) - \strlen('Builder')) {
            return false;
        }

        return true;
    }

    private function getBuilderSimpleName(): string
    {
        return $this->class->getShortName() . 'BuilderMethods';
    }

    private function addEntityProperty(): void
    {
        $this->builderClass->addProperty('entity')->setVisibility('private');
    }

    private function addConstructor(): void
    {
        // TODO Add support for classes without default constructor
        $this->builderClass->addMethod('__construct')
            ->setBody('$this->entity = new \\' . $this->class->getName() . ';');
    }

    private function addProperties(ReflectionClass $class): void
    {
        $this->addPublicProperties($class);
        $this->addSetters($class);
        $this->addMutators($class);
    }

    private function addPublicProperties(ReflectionClass $class): void
    {
        foreach ($class->getProperties() as $property) {
            if ($property->isStatic() || !$property->isPublic()) {
                continue;
            }

            $method = $this->builderClass->addMethod('with' . \ucfirst($property->getName()))
                ->setBody('$this->entity->' . $property->getName() . ' = $value;' . "\n\n" . 'return $this;')
                ->setFinal();

            $parameter = $method->addParameter('value');
            if (null !== $type = $this->determinePropertyType($property)) {
                $nullable = ($type[0] ?? '') === '?';
                $parameter->setTypeHint(\ltrim($type, '?'));
                $parameter->setNullable($nullable);
            }
        }
    }

    private function determinePropertyType(ReflectionProperty $property): ?string
    {
        $comment = $property->getDocComment();
        if (\preg_match('/@var (\S+)/', $comment, $match)) {
            $type = $match[1];

            $nullable = ($type[0] ?? '') === '?';
            $type = \ltrim($type, '?');

            if (\strpos($type, 'null|') === 0) {
                $type = \substr($type, 5);
                $nullable = true;
            } else {
                if (\strpos($type, '|null') === \strlen($type) - 5) {
                    $type = \substr($type, 0, -5);
                    $nullable = true;
                }
            }

            if (\in_array(\strtolower($type),
                ['int', 'bool', 'string', 'float', 'iterable', 'callable', 'array', 'object', 'void'], true)) {
                return ($nullable ? '?' : '') . $type;
            }

            if (\class_exists($type)) {
                return ($nullable ? '?' : '') . $type;
            }
        }

        return null;
    }

    private function addSetters(ReflectionClass $class): void
    {
        foreach ($class->getMethods() as $method) {
            if ($method->isStatic() || !$method->isPublic() || $method->getNumberOfParameters() !== 1) {
                continue;
            }

            if (0 !== \strpos($method->getName(), 'set')) {
                continue;
            }

            $generatedMethod = $this->builderClass->addMethod('with' . \substr($method->getName(), 3))
                ->setBody('$this->entity->' . $method->getName() . '($' . $method->getParameters()[0]->getName() . ');' . "\n\n" . 'return $this;')
                ->setFinal();

            /** @var Parameter $generatedParameter */
            $generatedParameter = $generatedMethod->addParameter($method->getParameters()[0]->getName());
            if (null !== $type = $method->getParameters()[0]->getType()) {
                $generatedParameter->setTypeHint((string) $type);
                $generatedParameter->setNullable($type->allowsNull());
            }

            if ($method->getParameters()[0]->isDefaultValueAvailable()) {
                if ($method->getParameters()[0]->isDefaultValueConstant()) {
                    $generatedParameter->setDefaultValue(new PhpLiteral($method->getParameters()[0]->getDefaultValueConstantName()));
                } else {
                    $generatedParameter->setDefaultValue($method->getParameters()[0]->getDefaultValue());
                }
            }
        }
    }

    private function addMutators(ReflectionClass $class): void
    {
        foreach ($class->getMethods() as $method) {
            if ($method->isStatic() || !$method->isPublic() || $method->getNumberOfParameters() === 0) {
                continue;
            }

            if (0 !== \strpos($method->getName(), 'with')) {
                continue;
            }

            $arguments = \implode(', ', \array_map(static function (ReflectionParameter $parameter) {
                return '$' . $parameter->getName();
            }, $method->getParameters()));

            $generatedMethod = $this->builderClass->addMethod($method->getName())
                ->setBody('$this->entity = $this->entity->' . $method->getName() . '(' . $arguments . ');' . "\n\n" . 'return $this;')
                ->setFinal();

            foreach ($method->getParameters() as $parameter) {
                /** @var Parameter $generatedParameter */
                $generatedParameter = $generatedMethod->addParameter($parameter->getName());
                if (null !== $type = $parameter->getType()) {
                    $generatedParameter->setTypeHint((string) $type);
                    $generatedParameter->setNullable($type->allowsNull());
                }

                if ($parameter->isDefaultValueAvailable()) {
                    if ($parameter->isDefaultValueConstant()) {
                        $generatedParameter->setDefaultValue(new PhpLiteral($parameter->getDefaultValueConstantName()));
                    } else {
                        $generatedParameter->setDefaultValue($parameter->getDefaultValue());
                    }
                }
            }
        }
    }

    private function addBuildMethod(): void
    {
        $this->builderClass->addMethod('build')
            ->setBody('return $this->entity;')
            ->setReturnType($this->class->getName())
            ->setFinal();
    }
}