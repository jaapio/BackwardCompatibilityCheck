<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\ConstantChanged;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassConstantBased\ClassConstantBased;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\ConstantChanged
 */
final class ConstantChangedTest extends TestCase
{
    public function testWillDetectChangesInConstants() : void
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public const a = 'value';
    protected const b = 'value';
    private const c = 'value';
    public const d = 'value';
    public const G = 'value';
}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    protected const b = 'value';
    public const d = 'value';
    public const e = 'value';
    public const f = 'value';
    public const g = 'value';
}
PHP
            ,
            $astLocator
        );

        $comparator = $this->createMock(ClassConstantBased::class);

        $comparator
            ->expects(self::exactly(2))
            ->method('compare')
            ->willReturnCallback(function (ReflectionClassConstant $from, ReflectionClassConstant $to) : Changes {
                $propertyName = $from->getName();

                self::assertSame($propertyName, $to->getName());

                return Changes::fromArray([Change::added($propertyName, true)]);
            });

        self::assertEquals(
            Changes::fromArray([
                Change::added('b', true),
                Change::added('d', true),
            ]),
            (new ConstantChanged($comparator))->compare(
                (new ClassReflector($fromLocator))->reflect('TheClass'),
                (new ClassReflector($toLocator))->reflect('TheClass')
            )
        );
    }
}