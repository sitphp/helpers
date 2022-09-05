<?php

namespace SitPHP\Helpers\Tests;

use Doubles\TestCase;
use SitPHP\Helpers\Text;

class TextTest extends TestCase
{
    function testStartsWith(){
        $message = 'my lõng message';
        $this->assertTrue(Text::startsWith($message, 'my lõng'));
        $this->assertFalse(Text::startsWith($message, 'my lõngî'));
        $this->assertTrue(Text::startsWith($message, ''));
    }
    function testEndsWith(){
        $message = 'my lõng message';
        $this->assertTrue(Text::endsWith($message, 'õng message'));
        $this->assertFalse(Text::endsWith($message, 'õng messages'));
        $this->assertTrue(Text::endsWith($message, ''));
    }

    function testCut(){
        $message = 'my lõng message';
        $this->assertEquals($message, Text::cut($message, 100));
        $this->assertEquals('my lõng...', Text::cut($message, 7));
        $this->assertEquals('my lõng',Text::cut($message, 7, null));
        $this->assertEquals('my lõng',Text::cut($message, 8, null));
        $this->assertEquals('my lõng ',Text::cut($message, 8, null, false));
    }
    function testCutWithInvalidEllipsisShouldFail(){
        $this->expectException(\InvalidArgumentException::class);
        $message = 'my long message';
        Text::cut($message, 7, []);
    }
    function testSlug(){
        $message = 'test Slug&with_special\'"&chars""&';
        $this->assertEquals('test-slug-with-special-chars', Text::slug($message));
        $this->assertEquals('test_slug_with_special_chars', Text::slug($message, '_'));
    }

    function testRemoveAccents(){
        $message = 'ÅÊêdfµdeîº';
        $this->assertEquals('AEedfudeio', Text::removeAccents($message));
    }

    function testChain(){
        $this->assertEquals(8, mb_strlen(Text::chain()));
        $this->assertStringNotContainsString('c', Text::chain(8, 'ab'));
    }

    function testContains(){
        $this->assertTrue(Text::contains('my long message', 'long'));
    }
}
