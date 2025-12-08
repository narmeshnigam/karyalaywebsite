<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Example property-based test to verify Eris is working correctly
 */
class ExamplePropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Example property test: string concatenation length
     * 
     * @test
     */
    public function stringConcatenationLength(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\string()
        )
        ->then(function ($str1, $str2) {
            $concatenated = $str1 . $str2;
            $this->assertEquals(
                strlen($str1) + strlen($str2),
                strlen($concatenated),
                'Concatenated string length should equal sum of individual lengths'
            );
        });
    }
}
