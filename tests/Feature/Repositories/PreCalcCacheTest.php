<?php

namespace Tests\Feature\Repositories;

use App\Register;
use App\Repositories\PreCalcCache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PreCalcCacheTest extends TestCase
{
    use DatabaseTransactions;

    protected $repo;

    protected function setUp()
    {
        parent::setUp();
        $this->repo = new PreCalcCache();
    }

    public function testSet()
    {
        $t = 'pre_calc_cache';
        $entity = new Register();
        $entity->id = 123;
        $key = 'a.b';
        $value = -12.65;

        $query = DB::table($t)->select('entity_id', 'key', 'value');

        $this->repo->set($entity, $key, $value);
        $res = $query->first();
        $this->assertEquals([
            'entity_id' => $entity->id,
            'key' => $key,
            'value' => $value,
        ], (array) $res);

        $value = $value * 2;
        $this->repo->set($entity, $key, $value);
        $this->assertEquals(1, DB::table($t)->count());
        $res = $query->first();
        $this->assertEquals([
            'entity_id' => $entity->id,
            'key' => $key,
            'value' => $value,
        ], (array) $res);
    }

    public function testGet()
    {
        $key = 'test.key';
        $register = new Register();
        $register->id = 896;

        $res = $this->repo->get($register, $key);
        $this->assertNull($res);

        $res = $this->repo->get($register, $key, 111);
        $this->assertEquals(111, $res);

        $this->repo->set($register, $key, 0);
        $res = $this->repo->get($register, $key, 111);
        $this->assertEquals(0, $res);
        $this->assertInternalType('float', $res);
    }
}
