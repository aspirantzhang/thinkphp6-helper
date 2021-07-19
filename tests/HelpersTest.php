<?php

declare(strict_types=1);

namespace aspirantzhang\tests;

class HelpersTest extends \PHPUnit\Framework\TestCase
{

    public function testInvalidDatetimeShouldReturnFalse()
    {
        $this->assertFalse(validateDateTime(''));
        $this->assertFalse(validateDateTime(0));
        $this->assertFalse(validateDateTime([]));
        $this->assertFalse(validateDateTime(null));
        $this->assertFalse(validateDateTime("\t"));
        $this->assertFalse(validateDateTime("\n"));
        $this->assertFalse(validateDateTime("\r"));
        $this->assertFalse(validateDateTime(' '));
        $this->assertFalse(validateDateTime(true));
        $this->assertFalse(validateDateTime(false));
    }

    public function testValidDatetimeShouldReturnTrue()
    {
        $this->assertTrue(validateDateTime('2020-04-02 11:59:59'));
    }

    public function testInvalidParamConvertTimeShouldReturnCurrent()
    {
        $this->assertEquals(convertTime(''), date('Y-m-d H:i:s'));
        $this->assertEquals(convertTime("\t"), date('Y-m-d H:i:s'));
        $this->assertEquals(convertTime("\n"), date('Y-m-d H:i:s'));
        $this->assertEquals(convertTime(' '), date('Y-m-d H:i:s'));
    }

    public function testValidParamsConvertTimeShouldReturnCorrectResult()
    {
        $this->assertEquals(convertTime('2021-06-27T00:22:10+08:00'), '2021-06-27 00:22:10');
        $this->assertEquals(convertTime('2021-06-27T00:22:10+08:00', 'Y-m-d\TH:i:sP'), '2021-06-27T00:22:10+08:00');
    }

    public function testArrayToTreeValidParam()
    {
        $actual = arrayToTree([
            [ 'id' => 1, 'parent_id' => 0 ],
            [ 'id' => 2, 'parent_id' => 0 ],
            [ 'id' => 3, 'parent_id' => 1 ],
            [ 'id' => 4, 'parent_id' => 3 ],
        ]);
        $expect = [
            [ 'id' => 1, 'parent_id' => 0, 'depth' => 1 , 'children' => [
                [ 'id' => 3, 'parent_id' => 1, 'depth' => 2, 'children' => [
                    [ 'id' => 4, 'parent_id' => 3, 'depth' => 3 ]
                ]]
            ]],
            [ 'id' => 2, 'parent_id' => 0, 'depth' => 1 ]
        ];
        $this->assertEqualsCanonicalizing($expect, $actual);
    }

    public function testArrayToTreeInvalidParam()
    {
        $actual = arrayToTree([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ]);
        $expect = [];

        $this->assertEqualsCanonicalizing($expect, $actual);
    }

    public function testArrayToTreeIfNoParent()
    {
        $actual = arrayToTree([
            [ 'id' => 1, 'parent_id' => 0 ],
            [ 'id' => 2, 'parent_id' => 0 ],
            [ 'id' => 3, 'parent_id' => 99999 ],
            [ 'id' => 4, 'parent_id' => 1 ]
        ]);
        $expect = [
            [ 'id' => 1, 'parent_id' => 0, 'depth' => 1 , 'children' => [
                [ 'id' => 4, 'parent_id' => 1, 'depth' => 2 ]
            ]],
            [ 'id' => 2, 'parent_id' => 0, 'depth' => 1 ],
            [ 'id' => 3, 'parent_id' => 0, 'depth' => 1 ]
        ];

        $this->assertEqualsCanonicalizing($expect, $actual);
    }

    public function testArrayToTreeIfNoRoot()
    {
        $actual = arrayToTree([
            [ 'id' => 1, 'parent_id' => 0 ],
            [ 'id' => 2, 'parent_id' => 0 ],
            [ 'id' => 3, 'parent_id' => 1 ],
        ], 99);
        $expect = [];

        $this->assertEqualsCanonicalizing($expect, $actual);
    }

    public function testArrayToTreeIfParentIdLessThanZero()
    {
        $actual = arrayToTree([
            [ 'id' => 1, 'parent_id' => 0 ],
            [ 'id' => 2, 'parent_id' => 1 ],
            [ 'id' => 0, 'parent_id' => -1 ],
            [ 'id' => 3, 'parent_id' => 999 ],
        ], -1);
        $expect = [
            [ 'id' => 0, 'parent_id' => -1, 'depth' => 1, 'children' => [
                [ 'id' => 1, 'parent_id' => 0, 'depth' => 2 , 'children' => [
                    [ 'id' => 2, 'parent_id' => 1, 'depth' => 3 ]
                ]],
                [ 'id' => 3, 'parent_id' => 0, 'depth' => 2 ]
            ]]
        ];

        $this->assertEqualsCanonicalizing($expect, $actual);
    }



    public function testExtractValuesInvalidParam()
    {
        $this->assertEqualsCanonicalizing([], extractValues([]));
        $this->assertEqualsCanonicalizing([], extractValues([], 'whatever...', 'whatever...'));
        $actual = extractValues([
            [ 'unit' => 'test1', 'other' => '...' ],
            [ 'unit' => 'test2', 'other' => '...' ],
            [ 'unit' => 'test3', 'other' => '...' ],
        ], 'unknown');
        $expect = [];
        $this->assertEqualsCanonicalizing($expect, $actual);
    }

    public function testExtractValuesValidParam()
    {
        $actual = extractValues([
            [ 'unit' => 'test1', 'other' => '...' ],
            [ 'unit' => 'test2', 'other' => '...' ],
        ], 'unit');
        $expect = ['test1', 'test2'];
        $this->assertEqualsCanonicalizing($expect, $actual);

        $actual2 = extractValues([
            [
                'data' => [
                    [ 'unit' => 'test1', 'other' => '...' ],
                    [ 'unit' => 'test2', 'other' => '...' ],
                ],
            ],
            [
                'data' => [
                    [ 'unit' => 'test3', 'other' => '...' ],
                    [ 'unit' => 'test2', 'other' => '...' ],
                ],
            ],
        ], 'unit', 'data');
        $expect2 = ['test1', 'test2', 'test3'];
        $this->assertEqualsCanonicalizing($expect2, $actual2);

        $actual3 = extractValues([
            [
                'data' => [
                    [ 'unit' => 'test1', 'other' => '...' ],
                    [ 'unit' => 'test2', 'other' => '...' ],
                ],
            ],
            [
                'data' => [
                    [ 'unit' => 'test3', 'other' => '...' ],
                    [ 'unit' => 'test2', 'other' => '...' ],
                ],
            ],
        ], 'unit', 'data', false);
        $expect3 = ['test1', 'test2', 'test3', 'test2'];
        $this->assertEqualsCanonicalizing($expect3, $actual3);

        $actual4 = extractValues([
            [
                'data' => [
                    'unit' => 'test1'
                ],
            ],
            [
                'data' => [
                    'unit' => 'test2'
                ],
            ],
        ], 'unit', 'data');
        $expect4 = ['test1', 'test2'];
        $this->assertEqualsCanonicalizing($expect4, $actual4);

        $actual5 = extractValues([
            [
                'data' => [
                    'unit' => ['value1','value2']
                ],
            ],
            [
                'data' => [
                    'unit' => ['value3','value4']
                ],
            ],
        ], 'unit', 'data');
        $expect5 = [['value1','value2'], ['value3','value4']];
        $this->assertEqualsCanonicalizing($expect5, $actual5);

        $actual6 = extractValues([
            [ 'unit' => 'test1', 'other' => '...' ],
            [ 'unit' => 'test1', 'other' => '...' ],
        ], 'unit', '', false);
        $expect6 = ['test1', 'test1'];
        $this->assertEqualsCanonicalizing($expect6, $actual6);
    }

    public function testSearchDescendantInvalidParam()
    {
        $actual = searchDescendantValueAggregation('', '', '', []);
        $expect = [];
        $this->assertEqualsCanonicalizing($expect, $actual);
    }

    public function testSearchDescendantValidParam()
    {
        $array = [
            [
                'id' => 1,
                'name' => 'one',
                'children' => [
                    [
                        'id' => 2,
                        'name' => 'two',
                        'children' => [
                            [
                                'id' => 3,
                                'name' => 'three',
                                'children' => [
                                    [
                                        'id' => 4,
                                        'name' => 'four',
                                    ],
                                    [
                                        'id' => 5,
                                        'name' => 'five',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $actual1 = searchDescendantValueAggregation('id', 'name', 'two', $array);
        $expect1 = [3, 4, 5];
        $this->assertEqualsCanonicalizing($expect1, $actual1);

        $actual2 = searchDescendantValueAggregation('id', 'name', 'two', $array, false);
        $expect2 = [3];
        $this->assertEqualsCanonicalizing($expect2, $actual2);

        $actual3 = searchDescendantValueAggregation('id', 'name', 'four', $array);
        $expect3 = [4];
        $this->assertEqualsCanonicalizing($expect3, $actual3);

        $actual4 = searchDescendantValueAggregation('id', 'name', 'unknown', $array);
        $expect4 = [];
        $this->assertEqualsCanonicalizing($expect4, $actual4);

        $actual5 = searchDescendantValueAggregation('id', 'name', 'unknown', $array, false);
        $expect5 = [];
        $this->assertEqualsCanonicalizing($expect5, $actual5);
    }

    public function testCreatePathValidParam()
    {
        $actual = createPath('a', 'b', 'c');
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $expect = 'a\\b\\c';
        } else {
            $expect = 'a/b/c';
        }
        $this->assertEquals($expect, $actual);
    }
}
