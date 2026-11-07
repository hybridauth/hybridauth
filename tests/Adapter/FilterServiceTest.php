<?php

namespace HybridauthTest\Adapter;

use Hybridauth\Adapter\FilterService;
use PHPUnit\Framework\TestCase;

class FilterServiceTest extends TestCase
{
    public function testReadsCurrentCallbackInputAndPreservesFilterSemantics(): void
    {
        $get = $_GET;
        $post = $_POST;
        try {
            $filter = new FilterService();
            $_GET = ['state' => 'first'];
            $this->assertSame('first', $filter->filterInput(INPUT_GET, 'state'));
            $_GET['state'] = 'second';
            $this->assertSame('second', $filter->filterInput(INPUT_GET, 'state'));
            $_POST = ['code' => '123', 'array' => ['forged']];
            $this->assertSame(123, $filter->filterInput(INPUT_POST, 'code', FILTER_VALIDATE_INT));
            $this->assertFalse($filter->filterInput(INPUT_POST, 'array'));
            $this->assertNull($filter->filterInput(INPUT_GET, 'missing'));
            $this->assertFalse($filter->filterInput(INPUT_GET, 'missing', FILTER_DEFAULT, FILTER_NULL_ON_FAILURE));
            $this->assertSame(['forged'], $filter->filterInput(INPUT_POST, 'array', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY));
        } finally {
            $_GET = $get;
            $_POST = $post;
        }
    }
}
