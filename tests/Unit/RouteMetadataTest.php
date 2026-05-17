<?php

declare(strict_types=1);

namespace CommonPHP\Router\Tests\Unit;

use CommonPHP\Router\RouteMetadata;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class RouteMetadataTest extends TestCase
{
    public function testItStoresAndReadsMetadata(): void
    {
        $metadata = new RouteMetadata(['scope' => 'admin']);

        self::assertSame(['scope' => 'admin'], $metadata->all());
        self::assertTrue($metadata->has('scope'));
        self::assertFalse($metadata->has('missing'));
        self::assertSame('admin', $metadata->get('scope'));
        self::assertSame('fallback', $metadata->get('missing', 'fallback'));
        self::assertSame(['scope'], $metadata->keys());
        self::assertSame(1, count($metadata));
        self::assertSame($metadata->all(), iterator_to_array($metadata));
    }

    public function testFromClonesExistingInstances(): void
    {
        $original = new RouteMetadata(['scope' => 'admin']);
        $copy = RouteMetadata::from($original);

        $copy->set('feature', 'reports');

        self::assertNotSame($original, $copy);
        self::assertSame(['scope' => 'admin'], $original->all());
        self::assertSame(['scope' => 'admin', 'feature' => 'reports'], $copy->all());
        self::assertSame(['name' => 'show'], RouteMetadata::from(['name' => 'show'])->all());
    }

    public function testItMutatesFluently(): void
    {
        $metadata = new RouteMetadata();

        self::assertSame($metadata, $metadata->set('scope', 'admin'));
        self::assertSame($metadata, $metadata->merge(['feature' => 'reports']));
        self::assertSame(['scope' => 'admin', 'feature' => 'reports'], $metadata->all());

        self::assertSame($metadata, $metadata->remove('scope'));
        self::assertSame(['feature' => 'reports'], $metadata->all());

        self::assertSame($metadata, $metadata->replace(['name' => 'show']));
        self::assertSame(['name' => 'show'], $metadata->all());

        self::assertSame($metadata, $metadata->clear());
        self::assertSame([], $metadata->all());
    }

    public function testItSupportsArrayAccess(): void
    {
        $metadata = new RouteMetadata();
        $metadata['scope'] = 'admin';

        self::assertTrue(isset($metadata['scope']));
        self::assertSame('admin', $metadata['scope']);
        self::assertNull($metadata[123]);

        unset($metadata['scope']);

        self::assertFalse(isset($metadata['scope']));
    }

    public function testItRejectsEmptyKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RouteMetadata([' ' => 'value']);
    }

    public function testArrayAccessRejectsNonStringKeysOnWrite(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $metadata = new RouteMetadata();
        $metadata[] = 'value';
    }
}
