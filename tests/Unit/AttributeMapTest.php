<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit;

use InvalidArgumentException;
use MongoDB\BSON\Binary;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Tubee\AttributeMap;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\AttributeMap\Exception;

class AttributeMapTest extends TestCase
{
    public function testAttributeEnsureAbsent()
    {
        $map = new AttributeMap([
            'foo' => ['ensure' => AttributeMapInterface::ENSURE_ABSENT],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar'], new UTCDateTime());
        $this->assertSame([], $result);
    }

    public function testAttributeEnsureExists()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'ensure' => AttributeMapInterface::ENSURE_EXISTS],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar'], new UTCDateTime());
        $this->assertSame(['foo' => 'bar'], $result);
    }

    public function testAttributeChangeName()
    {
        $map = new AttributeMap([
            'bar' => ['from' => 'foo', 'ensure' => AttributeMapInterface::ENSURE_EXISTS],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar'], new UTCDateTime());
        $this->assertSame(['bar' => 'bar'], $result);
    }

    public function testAttributeEnsureMergeNotArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $map = new AttributeMap([
            'string' => ['from' => 'foo', 'ensure' => AttributeMapInterface::ENSURE_MERGE],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar'], new UTCDateTime());
    }

    public function testAttributeInvalidMap()
    {
        $this->expectException(InvalidArgumentException::class);
        $map = new AttributeMap(['foo'], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));
        $result = $map->map(['foo' => 'bar'], new UTCDateTime());
    }

    public function testAttributeConvert()
    {
        $map = new AttributeMap([
            'string' => ['from' => 'foo', 'type' => AttributeMapInterface::TYPE_STRING],
            'int' => ['from' => 'foo', 'type' => AttributeMapInterface::TYPE_INT],
            'float' => ['from' => 'foo', 'type' => AttributeMapInterface::TYPE_FLOAT],
            'null' => ['from' => 'foo', 'type' => AttributeMapInterface::TYPE_NULL],
            'bool' => ['from' => 'foo', 'type' => AttributeMapInterface::TYPE_BOOL],
            'array' => ['from' => 'foo', 'type' => AttributeMapInterface::TYPE_ARRAY],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar'], new UTCDateTime());
        $this->assertSame('bar', $result['string']);
        $this->assertSame(0, $result['int']);
        $this->assertSame(0.0, $result['float']);
        $this->assertSame(null, $result['null']);
        $this->assertSame(true, $result['bool']);
        $this->assertSame(['bar'], $result['array']);
    }

    public function testAttributeInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'type' => 'foo'],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar'], new UTCDateTime());
    }

    public function testAttributeRequired()
    {
        $this->expectException(Exception\AttributeNotResolvable::class);
        $map = new AttributeMap([
            'foo' => ['from' => 'bar'],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar'], new UTCDateTime());
    }

    public function testAttributeNotRequired()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'bar', 'required' => false],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar'], new UTCDateTime());
        $this->assertSame([], $result);
    }

    public function testAttributeRequireRegexMatch()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'require_regex' => '#[a-z][A-Z][a-z]#'],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'fOo'], new UTCDateTime());
        $this->assertSame('fOo', $result['foo']);
    }

    public function testAttributeRequireRegexNotMatch()
    {
        $this->expectException(Exception\AttributeRegexNotMatch::class);
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'require_regex' => '#[a-z][A-Z][a-z]#'],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo'], new UTCDateTime());
    }

    public function testAttributeRequireRegexNotMatchNoAttribute()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'bar', 'required' => false, 'require_regex' => '#[a-z][A-Z][a-z]#'],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo'], new UTCDateTime());
        $this->assertSame([], $result);
    }

    public function testAttributeRewriteRegexRuleNoTo()
    {
        $this->expectException(InvalidArgumentException::class);
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'rewrite' => [
                ['match' => '#^foo$#'],
            ]],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo'], new UTCDateTime());
    }

    public function testAttributeRewriteRegexRuleNoMatch()
    {
        $this->expectException(InvalidArgumentException::class);
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'rewrite' => [
                ['to' => 'foo'],
            ]],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo'], new UTCDateTime());
    }

    public function testAttributeRewriteRegexRule()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'rewrite' => [
                ['match' => '#^foo$#', 'to' => 'bar'],
            ]],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo'], new UTCDateTime());
        $this->assertSame('bar', $result['foo']);
    }

    public function testAttributeRewriteRegexRuleFirstMatch()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'rewrite' => [
                ['match' => '#^foo$#', 'to' => 'bar'],
                ['match' => '#^foo$#', 'to' => 'foobar'],
            ]],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo'], new UTCDateTime());
        $this->assertSame('bar', $result['foo']);
    }

    public function testAttributeRewriteRegexRuleFirstMatchLastRule()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'rewrite' => [
                ['match' => '#^fo$#', 'to' => 'bar'],
                ['match' => '#^foo$#', 'to' => 'foobar'],
            ]],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo'], new UTCDateTime());
        $this->assertSame('foobar', $result['foo']);
    }

    public function testAttributeRewriteCompareRule()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'rewrite' => [
                ['regex' => false, 'match' => 'foo', 'to' => 'bar'],
            ]],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo'], new UTCDateTime());
        $this->assertSame('bar', $result['foo']);
    }

    public function testAttributeStaticValue()
    {
        $map = new AttributeMap([
            'foo' => ['value' => 'foobar'],
        ], new ExpressionLanguage(), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo'], new UTCDateTime());
        $this->assertSame('foobar', $result['foo']);
    }

    public function testAttributeScriptDynamicValue()
    {
        $map = new AttributeMap([
            'foo' => ['script' => 'bar'],
        ], new ExpressionLanguage(), $this->createMock(LoggerInterface::class));

        $result = $map->map(['bar' => 'foo'], new UTCDateTime());
        $this->assertSame('foo', $result['foo']);
    }

    public function testAttributeScriptJoinValues()
    {
        $map = new AttributeMap([
            'foo' => ['script' => 'foo~bar'],
        ], new ExpressionLanguage(), $this->createMock(LoggerInterface::class));

        $result = $map->map([
            'foo' => 'foo',
            'bar' => 'bar',
        ], new UTCDateTime());
        $this->assertSame('foobar', $result['foo']);
    }

    public function testAttributeDynamicValueNotResolvable()
    {
        $this->expectException(Exception\AttributeNotResolvable::class);
        $map = new AttributeMap([
            'foo' => ['script' => 'bar'],
        ], new ExpressionLanguage(), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo'], new UTCDateTime());
        $this->assertSame('foobar', $result['foo']);
    }

    public function testAttributeDynamicValueNotResolvableNotRequired()
    {
        $map = new AttributeMap([
            'foo' => ['script' => 'bar', 'required' => false],
        ], new ExpressionLanguage(), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo'], new UTCDateTime());
        $this->assertSame([], $result);
    }

    public function testArrayAttributeAsString()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo'],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => ['foo', 'bar']], new UTCDateTime());
        $this->assertSame('foo', $result['foo']);
    }

    public function testArrayAttributeRequireRegexMatch()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'type' => 'array', 'require_regex' => '#[a-z][A-Z][a-z]#'],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => ['fOo', 'fOobar']], new UTCDateTime());
        $this->assertSame(['fOo', 'fOobar'], $result['foo']);
    }

    public function testArrayOneAttributeRequireRegexNotMatch()
    {
        $this->expectException(Exception\AttributeRegexNotMatch::class);
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'type' => 'array', 'require_regex' => '#[a-z][A-Z][a-z]#'],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => ['foo', 'fOo']], new UTCDateTime());
    }

    public function testArrayAttributeRewriteRegexRule()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'type' => 'array', 'rewrite' => [
                ['match' => '#^foo#', 'to' => 'bar'],
            ]],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => ['foo', 'foobar']], new UTCDateTime());
        $this->assertSame(['bar', 'barbar'], $result['foo']);
    }

    public function testArrayAttributeRewriteCompareRule()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'type' => 'array', 'rewrite' => [
                ['regex' => false, 'match' => 'foo', 'to' => 'bar'],
            ]],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => ['foo', 'foobar']], new UTCDateTime());
        $this->assertSame(['bar', 'foobar'], $result['foo']);
    }

    public function testBinaryType()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'type' => Binary::class],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo'], new UTCDateTime());
        $this->assertInstanceOf(Binary::class, $result['foo']);
    }

    public function testGetDiffNoChange()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo'],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $mapped = ['foo' => 'foo'];
        $existing = ['foo' => 'foo'];
        $result = $map->getDiff($mapped, $existing);
        $this->assertSame([], $result);
    }

    public function testGetDiffUpdateValue()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'ensure' => AttributeMapInterface::ENSURE_LAST],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $mapped = ['foo' => 'foo'];
        $existing = ['foo' => 'bar'];
        $result = $map->getDiff($mapped, $existing);

        $expected = [
            'foo' => [
                'action' => AttributeMapInterface::ACTION_REPLACE,
                'value' => 'foo',
            ],
        ];

        $this->assertSame($expected, $result);
    }
}
