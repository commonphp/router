<?php

declare(strict_types=1);

namespace CommonPHP\Router\Tests\Unit;

use CommonPHP\Router\Contracts\RouteConstraintInterface;
use CommonPHP\Router\Exceptions\InvalidRouteException;
use CommonPHP\Router\RouteConstraint;
use PHPUnit\Framework\TestCase;

final class RouteConstraintTest extends TestCase
{
    public function testItUsesASingleSegmentDefaultPattern(): void
    {
        $constraint = new RouteConstraint();

        self::assertInstanceOf(RouteConstraintInterface::class, $constraint);
        self::assertSame('[^/]+', $constraint->pattern());
        self::assertSame('[^/]+', $constraint->description());
        self::assertTrue($constraint->matches('abc'));
        self::assertFalse($constraint->matches('abc/def'));
    }

    public function testRegexFactoryStoresPatternAndDescription(): void
    {
        $constraint = RouteConstraint::regex('[A-Z]{2}[0-9]{2}', 'code');

        self::assertSame('[A-Z]{2}[0-9]{2}', $constraint->pattern());
        self::assertSame('code', $constraint->description());
        self::assertTrue($constraint->matches('AB12'));
        self::assertFalse($constraint->matches('ab12'));
    }

    public function testCallbackFactoryCombinesPatternAndValidator(): void
    {
        $constraint = RouteConstraint::callback(
            static fn (string $value): bool => $value !== '13',
            '[0-9]+',
            'number except 13',
        );

        self::assertTrue($constraint->matches('12'));
        self::assertFalse($constraint->matches('abc'));
        self::assertFalse($constraint->matches('13'));
        self::assertSame('number except 13', $constraint->description());
    }

    public function testInConstraintCanBeCaseSensitiveOrInsensitive(): void
    {
        $sensitive = RouteConstraint::in(['Draft', 'Published']);
        $insensitive = RouteConstraint::in(['Draft', 'Published'], false);

        self::assertTrue($sensitive->matches('Draft'));
        self::assertFalse($sensitive->matches('draft'));
        self::assertTrue($insensitive->matches('draft'));
        self::assertSame('one of Draft, Published', $insensitive->description());
    }

    public function testBuiltInConstraintFactoriesMatchExpectedValues(): void
    {
        self::assertTrue(RouteConstraint::number()->matches('123'));
        self::assertFalse(RouteConstraint::number()->matches('12a'));

        self::assertTrue(RouteConstraint::alpha()->matches('abcXYZ'));
        self::assertFalse(RouteConstraint::alpha()->matches('abc123'));

        self::assertTrue(RouteConstraint::alphaNumeric()->matches('abc123'));
        self::assertFalse(RouteConstraint::alphaNumeric()->matches('abc-123'));

        self::assertTrue(RouteConstraint::slug()->matches('monthly-close-2026'));
        self::assertFalse(RouteConstraint::slug()->matches('-bad'));

        self::assertTrue(RouteConstraint::uuid()->matches('123e4567-e89b-12d3-a456-426614174000'));
        self::assertFalse(RouteConstraint::uuid()->matches('not-a-uuid'));
    }

    public function testItRejectsEmptyPatterns(): void
    {
        $this->expectException(InvalidRouteException::class);

        new RouteConstraint('');
    }

    public function testItRejectsInvalidPatterns(): void
    {
        $this->expectException(InvalidRouteException::class);

        new RouteConstraint('[');
    }
}
