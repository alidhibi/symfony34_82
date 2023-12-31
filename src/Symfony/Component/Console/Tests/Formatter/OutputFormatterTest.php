<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Tests\Formatter;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class OutputFormatterTest extends TestCase
{
    public function testEmptyTag(): void
    {
        $formatter = new OutputFormatter(true);
        $this->assertEquals('foo<>bar', $formatter->format('foo<>bar'));
    }

    public function testLGCharEscaping(): void
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals('foo<bar', $formatter->format('foo\\<bar'));
        $this->assertEquals('foo << bar', $formatter->format('foo << bar'));
        $this->assertEquals('foo << bar \\', $formatter->format('foo << bar \\'));
        $this->assertEquals("foo << \033[32mbar \\ baz\033[39m \\", $formatter->format('foo << <info>bar \\ baz</info> \\'));
        $this->assertEquals('<info>some info</info>', $formatter->format('\\<info>some info\\</info>'));
        $this->assertEquals('\\<info>some info\\</info>', OutputFormatter::escape('<info>some info</info>'));

        $this->assertEquals(
            "\033[33mSymfony\\Component\\Console does work very well!\033[39m",
            $formatter->format('<comment>Symfony\Component\Console does work very well!</comment>')
        );
    }

    public function testBundledStyles(): void
    {
        $formatter = new OutputFormatter(true);

        $this->assertTrue($formatter->hasStyle('error'));
        $this->assertTrue($formatter->hasStyle('info'));
        $this->assertTrue($formatter->hasStyle('comment'));
        $this->assertTrue($formatter->hasStyle('question'));

        $this->assertEquals(
            "\033[37;41msome error\033[39;49m",
            $formatter->format('<error>some error</error>')
        );
        $this->assertEquals(
            "\033[32msome info\033[39m",
            $formatter->format('<info>some info</info>')
        );
        $this->assertEquals(
            "\033[33msome comment\033[39m",
            $formatter->format('<comment>some comment</comment>')
        );
        $this->assertEquals(
            "\033[30;46msome question\033[39;49m",
            $formatter->format('<question>some question</question>')
        );
    }

    public function testNestedStyles(): void
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals(
            "\033[37;41msome \033[39;49m\033[32msome info\033[39m\033[37;41m error\033[39;49m",
            $formatter->format('<error>some <info>some info</info> error</error>')
        );
    }

    public function testAdjacentStyles(): void
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals(
            "\033[37;41msome error\033[39;49m\033[32msome info\033[39m",
            $formatter->format('<error>some error</error><info>some info</info>')
        );
    }

    public function testStyleMatchingNotGreedy(): void
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals(
            "(\033[32m>=2.0,<2.3\033[39m)",
            $formatter->format('(<info>>=2.0,<2.3</info>)')
        );
    }

    public function testStyleEscaping(): void
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals(
            "(\033[32mz>=2.0,<<<a2.3\\\033[39m)",
            $formatter->format('(<info>'.$formatter->escape('z>=2.0,<\\<<a2.3\\').'</info>)')
        );

        $this->assertEquals(
            "\033[32m<error>some error</error>\033[39m",
            $formatter->format('<info>'.$formatter->escape('<error>some error</error>').'</info>')
        );
    }

    public function testDeepNestedStyles(): void
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals(
            "\033[37;41merror\033[39;49m\033[32minfo\033[39m\033[33mcomment\033[39m\033[37;41merror\033[39;49m",
            $formatter->format('<error>error<info>info<comment>comment</info>error</error>')
        );
    }

    public function testNewStyle(): void
    {
        $formatter = new OutputFormatter(true);

        $style = new OutputFormatterStyle('blue', 'white');
        $formatter->setStyle('test', $style);

        $this->assertEquals($style, $formatter->getStyle('test'));
        $this->assertNotEquals($style, $formatter->getStyle('info'));

        $style = new OutputFormatterStyle('blue', 'white');
        $formatter->setStyle('b', $style);

        $this->assertEquals("\033[34;47msome \033[39;49m\033[34;47mcustom\033[39;49m\033[34;47m msg\033[39;49m", $formatter->format('<test>some <b>custom</b> msg</test>'));
    }

    public function testRedefineStyle(): void
    {
        $formatter = new OutputFormatter(true);

        $style = new OutputFormatterStyle('blue', 'white');
        $formatter->setStyle('info', $style);

        $this->assertEquals("\033[34;47msome custom msg\033[39;49m", $formatter->format('<info>some custom msg</info>'));
    }

    public function testInlineStyle(): void
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals("\033[34;41msome text\033[39;49m", $formatter->format('<fg=blue;bg=red>some text</>'));
        $this->assertEquals("\033[34;41msome text\033[39;49m", $formatter->format('<fg=blue;bg=red>some text</fg=blue;bg=red>'));
    }

    /**
     * @param string|null $expected
     * @param string|null $input
     *
     * @dataProvider provideInlineStyleOptionsCases
     */
    public function testInlineStyleOptions(string $tag, string $expected = null, string $input = null): void
    {
        $styleString = substr($tag, 1, -1);
        $formatter = new OutputFormatter(true);
        $method = new \ReflectionMethod($formatter, 'createStyleFromString');
        $method->setAccessible(true);

        $result = $method->invoke($formatter, $styleString);
        if (null === $expected) {
            $this->assertFalse($result);
            $expected = $tag.$input.'</'.$styleString.'>';
            $this->assertSame($expected, $formatter->format($expected));
        } else {
            /* @var OutputFormatterStyle $result */
            $this->assertInstanceOf(OutputFormatterStyle::class, $result);
            $this->assertSame($expected, $formatter->format($tag.$input.'</>'));
            $this->assertSame($expected, $formatter->format($tag.$input.'</'.$styleString.'>'));
        }
    }

    public function provideInlineStyleOptionsCases(): array
    {
        return [
            ['<unknown=_unknown_>'],
            ['<unknown=_unknown_;a=1;b>'],
            ['<fg=green;>', "\033[32m[test]\033[39m", '[test]'],
            ['<fg=green;bg=blue;>', "\033[32;44ma\033[39;49m", 'a'],
            ['<fg=green;options=bold>', "\033[32;1mb\033[39;22m", 'b'],
            ['<fg=green;options=reverse;>', "\033[32;7m<a>\033[39;27m", '<a>'],
            ['<fg=green;options=bold,underscore>', "\033[32;1;4mz\033[39;22;24m", 'z'],
            ['<fg=green;options=bold,underscore,reverse;>', "\033[32;1;4;7md\033[39;22;24;27m", 'd'],
        ];
    }

    /**
     * @group legacy
     * @dataProvider provideInlineStyleTagsWithUnknownOptions
     * @expectedDeprecation Unknown style options are deprecated since Symfony 3.2 and will be removed in 4.0. Exception "Invalid option specified: "%s". Expected one of (bold, underscore, blink, reverse, conceal).".
     */
    public function testInlineStyleOptionsUnknownAreDeprecated(?string $tag, string $option): void
    {
        $formatter = new OutputFormatter(true);
        $formatter->format($tag);
    }

    public function provideInlineStyleTagsWithUnknownOptions(): array
    {
        return [
            ['<options=abc;>', 'abc'],
            ['<options=abc,def;>', 'abc'],
            ['<fg=green;options=xyz;>', 'xyz'],
            ['<fg=green;options=efg,abc>', 'efg'],
        ];
    }

    public function testNonStyleTag(): void
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals("\033[32msome \033[39m\033[32m<tag>\033[39m\033[32m \033[39m\033[32m<setting=value>\033[39m\033[32m styled \033[39m\033[32m<p>\033[39m\033[32msingle-char tag\033[39m\033[32m</p>\033[39m", $formatter->format('<info>some <tag> <setting=value> styled <p>single-char tag</p></info>'));
    }

    public function testFormatLongString(): void
    {
        $formatter = new OutputFormatter(true);
        $long = str_repeat('\\', 14000);
        $this->assertEquals("\033[37;41msome error\033[39;49m".$long, $formatter->format('<error>some error</error>'.$long));
    }

    public function testFormatToStringObject(): void
    {
        $formatter = new OutputFormatter(false);
        $this->assertEquals(
            'some info', $formatter->format(new TableCell())
        );
    }

    public function testNotDecoratedFormatter(): void
    {
        $formatter = new OutputFormatter(false);

        $this->assertTrue($formatter->hasStyle('error'));
        $this->assertTrue($formatter->hasStyle('info'));
        $this->assertTrue($formatter->hasStyle('comment'));
        $this->assertTrue($formatter->hasStyle('question'));

        $this->assertEquals(
            'some error', $formatter->format('<error>some error</error>')
        );
        $this->assertEquals(
            'some info', $formatter->format('<info>some info</info>')
        );
        $this->assertEquals(
            'some comment', $formatter->format('<comment>some comment</comment>')
        );
        $this->assertEquals(
            'some question', $formatter->format('<question>some question</question>')
        );
        $this->assertEquals(
            'some text with inline style', $formatter->format('<fg=red>some text with inline style</>')
        );

        $formatter->setDecorated(true);

        $this->assertEquals(
            "\033[37;41msome error\033[39;49m", $formatter->format('<error>some error</error>')
        );
        $this->assertEquals(
            "\033[32msome info\033[39m", $formatter->format('<info>some info</info>')
        );
        $this->assertEquals(
            "\033[33msome comment\033[39m", $formatter->format('<comment>some comment</comment>')
        );
        $this->assertEquals(
            "\033[30;46msome question\033[39;49m", $formatter->format('<question>some question</question>')
        );
        $this->assertEquals(
            "\033[31msome text with inline style\033[39m", $formatter->format('<fg=red>some text with inline style</>')
        );
    }

    public function testContentWithLineBreaks(): void
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals(<<<EOF
\033[32m
some text\033[39m
EOF
            , $formatter->format(<<<'EOF'
<info>
some text</info>
EOF
        ));

        $this->assertEquals(<<<EOF
\033[32msome text
\033[39m
EOF
            , $formatter->format(<<<'EOF'
<info>some text
</info>
EOF
        ));

        $this->assertEquals(<<<EOF
\033[32m
some text
\033[39m
EOF
            , $formatter->format(<<<'EOF'
<info>
some text
</info>
EOF
        ));

        $this->assertEquals(<<<EOF
\033[32m
some text
more text
\033[39m
EOF
            , $formatter->format(<<<'EOF'
<info>
some text
more text
</info>
EOF
        ));
    }
}

class TableCell
{
    public function __toString(): string
    {
        return '<info>some info</info>';
    }
}
