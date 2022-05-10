<?php

declare(strict_types=1);

namespace aspirantzhang\tests;

use Mockery as m;

class HelpersTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $langMock = m::mock('alias:think\facade\Lang');
        $langMock->shouldReceive('get')->andReturnUsing(function (string $name, array $vars = [], string $lang = '') {
            if (!empty($vars)) {
                return $name . ': ' . implode(';', array_map(function ($key, $value) {
                    return $key . '=' . $value;
                }, array_keys($vars), $vars));
            }

            return $name;
        });
    }

    public function testGetLang()
    {
        $this->assertEquals(__('foo'), 'foo');
        $this->assertEquals(__('foo', ['a' => 'b']), 'foo: a=b');
        $this->assertEquals(__('foo', ['a' => 'b', 'c' => 'd']), 'foo: a=b;c=d');
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
            ['id' => 1, 'parent_id' => 0],
            ['id' => 2, 'parent_id' => 0],
            ['id' => 3, 'parent_id' => 1],
            ['id' => 4, 'parent_id' => 3],
        ]);
        $expect = [
            ['id' => 1, 'parent_id' => 0, 'depth' => 1, 'children' => [
                ['id' => 3, 'parent_id' => 1, 'depth' => 2, 'children' => [
                    ['id' => 4, 'parent_id' => 3, 'depth' => 3],
                ]],
            ]],
            ['id' => 2, 'parent_id' => 0, 'depth' => 1],
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
            ['id' => 1, 'parent_id' => 0],
            ['id' => 2, 'parent_id' => 0],
            ['id' => 3, 'parent_id' => 99999],
            ['id' => 4, 'parent_id' => 1],
        ]);
        $expect = [
            ['id' => 1, 'parent_id' => 0, 'depth' => 1, 'children' => [
                ['id' => 4, 'parent_id' => 1, 'depth' => 2],
            ]],
            ['id' => 2, 'parent_id' => 0, 'depth' => 1],
            ['id' => 3, 'parent_id' => 0, 'depth' => 1],
        ];

        $this->assertEqualsCanonicalizing($expect, $actual);
    }

    public function testArrayToTreeIfNoRoot()
    {
        $actual = arrayToTree([
            ['id' => 1, 'parent_id' => 0],
            ['id' => 2, 'parent_id' => 0],
            ['id' => 3, 'parent_id' => 1],
        ], 99);
        $expect = [];

        $this->assertEqualsCanonicalizing($expect, $actual);
    }

    public function testArrayToTreeIfParentIdLessThanZero()
    {
        $actual = arrayToTree([
            ['id' => 1, 'parent_id' => 0],
            ['id' => 2, 'parent_id' => 1],
            ['id' => 0, 'parent_id' => -1],
            ['id' => 3, 'parent_id' => 999],
        ], -1);
        $expect = [
            ['id' => 0, 'parent_id' => -1, 'depth' => 1, 'children' => [
                ['id' => 1, 'parent_id' => 0, 'depth' => 2, 'children' => [
                    ['id' => 2, 'parent_id' => 1, 'depth' => 3],
                ]],
                ['id' => 3, 'parent_id' => 0, 'depth' => 2],
            ]],
        ];

        $this->assertEqualsCanonicalizing($expect, $actual);
    }

    public function testExtractValuesInvalidParam()
    {
        $this->assertEqualsCanonicalizing([], extractValues([]));
        $this->assertEqualsCanonicalizing([], extractValues([], 'whatever...', 'whatever...'));
        $actual = extractValues([
            ['unit' => 'test1', 'other' => '...'],
            ['unit' => 'test2', 'other' => '...'],
            ['unit' => 'test3', 'other' => '...'],
        ], 'unknown');
        $expect = [];
        $this->assertEqualsCanonicalizing($expect, $actual);
    }

    public function testExtractValuesValidParam()
    {
        $actual = extractValues([
            ['unit' => 'test1', 'other' => '...'],
            ['unit' => 'test2', 'other' => '...'],
        ], 'unit');
        $expect = ['test1', 'test2'];
        $this->assertEqualsCanonicalizing($expect, $actual);

        $actual2 = extractValues([
            [
                'data' => [
                    ['unit' => 'test1', 'other' => '...'],
                    ['unit' => 'test2', 'other' => '...'],
                ],
            ],
            [
                'data' => [
                    ['unit' => 'test3', 'other' => '...'],
                    ['unit' => 'test2', 'other' => '...'],
                ],
            ],
        ], 'unit', 'data');
        $expect2 = ['test1', 'test2', 'test3'];
        $this->assertEqualsCanonicalizing($expect2, $actual2);

        $actual3 = extractValues([
            [
                'data' => [
                    ['unit' => 'test1', 'other' => '...'],
                    ['unit' => 'test2', 'other' => '...'],
                ],
            ],
            [
                'data' => [
                    ['unit' => 'test3', 'other' => '...'],
                    ['unit' => 'test2', 'other' => '...'],
                ],
            ],
        ], 'unit', 'data', false);
        $expect3 = ['test1', 'test2', 'test3', 'test2'];
        $this->assertEqualsCanonicalizing($expect3, $actual3);

        $actual4 = extractValues([
            [
                'data' => [
                    'unit' => 'test1',
                ],
            ],
            [
                'data' => [
                    'unit' => 'test2',
                ],
            ],
        ], 'unit', 'data');
        $expect4 = ['test1', 'test2'];
        $this->assertEqualsCanonicalizing($expect4, $actual4);

        $actual5 = extractValues([
            [
                'data' => [
                    'unit' => ['value1', 'value2'],
                ],
            ],
            [
                'data' => [
                    'unit' => ['value3', 'value4'],
                ],
            ],
        ], 'unit', 'data');
        $expect5 = [['value1', 'value2'], ['value3', 'value4']];
        $this->assertEqualsCanonicalizing($expect5, $actual5);

        $actual6 = extractValues([
            ['unit' => 'test1', 'other' => '...'],
            ['unit' => 'test1', 'other' => '...'],
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
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
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

    public function testMatchSnapshotSuccessfully()
    {
        $actual = matchSnapshot(
            createPath(__DIR__, 'file', 'foo') . '.php',
            createPath(__DIR__, '__snapshots__', 'foo') . '.stub'
        );
        $this->assertTrue($actual);
    }

    public function testMatchSnapshotFailed()
    {
        $actual = matchSnapshot(
            createPath(__DIR__, 'file', 'bar') . '.php',
            createPath(__DIR__, '__snapshots__', 'foo') . '.stub'
        );
        $this->assertFalse($actual);
    }

    public function testMakeDirSuccessfully()
    {
        $filePath = createPath(dirname(__DIR__), 'runtime', 'file', 'dir-test');
        $actual = makeDir($filePath);
        $this->assertTrue($actual);
        $this->assertTrue(is_dir($filePath));

        return $filePath;
    }

    /**
     * @depends testMakeDirSuccessfully
     */
    public function testMakeDirFailed($filePath)
    {
        $actual = makeDir($filePath);
        $this->assertFalse($actual);

        return $filePath;
    }

    /**
     * @depends testMakeDirFailed
     */
    public function testDeleteDirSuccessfully($filePath)
    {
        $this->assertTrue(deleteDir($filePath));
    }

    public function testIsAssocArray()
    {
        $this->assertFalse(isAssocArray([1, 2, 3]));
        $this->assertFalse(isAssocArray(['a']));
        $this->assertFalse(isAssocArray(['0' => 'a', '1' => 'b', '2' => 'c']));
        $this->assertTrue(isAssocArray(['a' => 'a']));
    }

    public function testIsInt()
    {
        $expectIsInt = [0, '0', 33, '33', -10, '-10', 0x24, true, false];
        $expectIsNotInt = [null, '22f2', .22, '1.22', -1.22, PHP_INT_MAX + 1];
        foreach ($expectIsInt as $value) {
            $this->assertTrue(isInt($value));
        }
        foreach ($expectIsNotInt as $value) {
            $this->assertFalse(isInt($value));
        }
    }
}
