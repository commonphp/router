<?php

declare(strict_types=1);

namespace CommonPHP\Router\Tests\Unit;

use CommonPHP\Router\Exceptions\InvalidRouteParameterException;
use CommonPHP\Router\RouteParameters;
use PHPUnit\Framework\TestCase;

final class RouteParametersTest extends TestCase
{
    public function testItStoresAndReadsParameters(): void
    {
        $parameters = new RouteParameters(['id' => '42']);

        self::assertSame(['id' => '42'], $parameters->all());
        self::assertTrue($parameters->has('id'));
        self::assertFalse($parameters->has('missing'));
        self::assertSame('42', $parameters->get('id'));
        self::assertSame('fallback', $parameters->get('missing', 'fallback'));
        self::assertSame('42', $parameters->getRequired('id'));
        self::assertSame(['id'], $parameters->names());
        self::assertSame(1, count($parameters));
        self::assertSame($parameters->all(), iterator_to_array($parameters));
    }

    public function testFromClonesExistingInstances(): void
    {
        $original = new RouteParameters(['id' => '42']);
        $copy = RouteParameters::from($original);

        $copy->set('slug', 'alpha');

        self::assertNotSame($original, $copy);
        self::assertSame(['id' => '42'], $original->all());
        self::assertSame(['id' => '42', 'slug' => 'alpha'], $copy->all());
        self::assertSame(['name' => 'Ada'], RouteParameters::from(['name' => 'Ada'])->all());
    }

    public function testItMutatesFluently(): void
    {
        $parameters = new RouteParameters();

        self::assertSame($parameters, $parameters->set('id', 42));
        self::assertSame($parameters, $parameters->merge(['slug' => 'alpha']));
        self::assertSame(['id' => 42, 'slug' => 'alpha'], $parameters->all());

        self::assertSame($parameters, $parameters->remove('id'));
        self::assertSame(['slug' => 'alpha'], $parameters->all());

        self::assertSame($parameters, $parameters->replace(['page' => 2]));
        self::assertSame(['page' => 2], $parameters->all());

        self::assertSame($parameters, $parameters->clear());
        self::assertSame([], $parameters->all());
    }

    public function testItSupportsArrayAccess(): void
    {
        $parameters = new RouteParameters();
        $parameters['id'] = '42';

        self::assertTrue(isset($parameters['id']));
        self::assertSame('42', $parameters['id']);
        self::assertNull($parameters[123]);

        unset($parameters['id']);

        self::assertFalse(isset($parameters['id']));
    }

    public function testRequiredLookupRejectsMissingParameters(): void
    {
        $this->expectException(InvalidRouteParameterException::class);

        (new RouteParameters())->getRequired('id');
    }

    public function testItRejectsInvalidParameterNames(): void
    {
        $this->expectException(InvalidRouteParameterException::class);

        new RouteParameters(['bad-name' => 'value']);
    }

    public function testArrayAccessRejectsNonStringKeysOnWrite(): void
    {
        $this->expectException(InvalidRouteParameterException::class);

        $parameters = new RouteParameters();
        $parameters[] = 'value';
    }
}
