<?php

namespace Tests\Feature\Repositories;

use App\CalculatedValue;
use App\Order;
use App\Register;
use App\Repositories\CalculatedValueRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CalculatedValueRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    protected $repo;

    protected function setUp()
    {
        parent::setUp();
        $this->repo = new CalculatedValueRepository(new CalculatedValue());
    }

    public function testSet()
    {
        $t = (new CalculatedValue())->getTable();
        $register = new Register();
        $register->id = 123;
        $other = new Order();
        $other->id = $register->id;
        $key = 'a.b';
        $value = -12.65;

        // We set another entry with the same id (different class) to be sure it will not be deleted
        $this->repo->set($other, $key, 1);

        $queryAll = DB::table($t);
        $query = DB::table($t)
            ->where('instance_id', $register->id)
            ->where('class', get_class($register))
            ->select('instance_id', 'key', 'value', 'class');

        $this->repo->set($register, $key, $value);
        $res = $query->first();
        $this->assertEquals([
            'instance_id' => $register->id,
            'key' => $key,
            'value' => $value,
            'class' => get_class($register),
        ], (array) $res);

        // Test the $other is still there
        $this->assertEquals(2, $queryAll->count());

        $value = $value * 2;
        $this->repo->set($register, $key, $value);
        $this->assertEquals(2, $queryAll->count());
        $res = $query->first();
        $this->assertEquals([
            'instance_id' => $register->id,
            'key' => $key,
            'value' => $value,
            'class' => get_class($register),
        ], (array) $res);
    }

    public function testGet()
    {
        $key = 'test.key';
        $register = new Register();
        $register->id = 896;

        // We make another instance of another class but with the same id to be sure get discriminates with the class
        $other = new Order();
        $other->id = $register->id;
        $this->repo->set($other, $key, 1);

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
