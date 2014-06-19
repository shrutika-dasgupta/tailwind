<?php

/**
 * Class CollectionTest
 */
class CollectionTest extends TestCase
{


    protected $collection;

    /**
     * @author  Will
     */
    public function setUp()
    {
        parent::setUp();

        $this->collection = new Collection();

        $model_a = new Model();
        $model_b = new Model();
        $model_c = new Model();
        $model_d = new Model();
        $model_e = new Model();

        $model_a->column_x = 0;
        $model_b->column_x = 1;
        $model_c->column_x = 2;
        $model_d->column_x = 3;
        $model_e->column_x = 4;

        $model_a->column_y = 'C';
        $model_b->column_y = 'C';
        $model_c->column_y = 'A';
        $model_d->column_y = 'A';
        $model_e->column_y = 'B';

        $this->collection->add($model_a);
        $this->collection->add($model_b);
        $this->collection->add($model_c);
        $this->collection->add($model_d);
        $this->collection->add($model_e);
    }

    /**
     * @author Will
     */
    public function testSortBySortsCollection()
    {

        $expected_collection = new Collection;

        $model_a = new Model();
        $model_b = new Model();
        $model_c = new Model();
        $model_d = new Model();
        $model_e = new Model();

        $model_a->column_x = 4;
        $model_b->column_x = 3;
        $model_c->column_x = 2;
        $model_d->column_x = 1;
        $model_e->column_x = 0;

        $model_a->column_y = 'B';
        $model_b->column_y = 'A';
        $model_c->column_y = 'A';
        $model_d->column_y = 'C';
        $model_e->column_y = 'C';

        $expected_collection->add($model_a);
        $expected_collection->add($model_b);
        $expected_collection->add($model_c);
        $expected_collection->add($model_d);
        $expected_collection->add($model_e);

        $this->collection->sortBy('column_x', SORT_DESC);

        $this->assertEquals($expected_collection, $this->collection);
    }

    /**
     * @author Will
     */
    public function testMulitSortBySortsCollection()
    {

        $expected_collection = new Collection;

        $model_a = new Model();
        $model_b = new Model();
        $model_c = new Model();
        $model_d = new Model();
        $model_e = new Model();

        $model_a->column_x = 2;
        $model_b->column_x = 3;
        $model_c->column_x = 4;
        $model_d->column_x = 0;
        $model_e->column_x = 1;

        $model_a->column_y = 'A';
        $model_b->column_y = 'A';
        $model_c->column_y = 'B';
        $model_d->column_y = 'C';
        $model_e->column_y = 'C';

        $expected_collection->add($model_a);
        $expected_collection->add($model_b);
        $expected_collection->add($model_c);
        $expected_collection->add($model_d);
        $expected_collection->add($model_e);

        $this->collection->sortBy('column_y', SORT_ASC, 'column_x', SORT_ASC);

        $this->assertEquals($expected_collection, $this->collection);
    }

    /**
     * @author  Will
     */
    public function testNotPassingDirSortsByDefault()
    {

        $expected_collection = new Collection;

        $model_a = new Model();
        $model_b = new Model();
        $model_c = new Model();
        $model_d = new Model();
        $model_e = new Model();

        $model_a->column_x = 0;
        $model_b->column_x = 1;
        $model_c->column_x = 2;
        $model_d->column_x = 3;
        $model_e->column_x = 4;

        $model_a->column_y = 'C';
        $model_b->column_y = 'C';
        $model_c->column_y = 'A';
        $model_d->column_y = 'A';
        $model_e->column_y = 'B';

        $expected_collection->add($model_a);
        $expected_collection->add($model_b);
        $expected_collection->add($model_c);
        $expected_collection->add($model_d);
        $expected_collection->add($model_e);

        $this->collection->sortBy('column_x');

        $this->assertEquals($expected_collection, $this->collection);
    }

    /**
     * @author  Will
     */
    public function testPassingArrayToSortByInsteadOfArguments()
    {

        $expected_collection = new Collection;

        $model_a = new Model();
        $model_b = new Model();
        $model_c = new Model();
        $model_d = new Model();
        $model_e = new Model();

        $model_a->column_x = 0;
        $model_b->column_x = 1;
        $model_c->column_x = 2;
        $model_d->column_x = 3;
        $model_e->column_x = 4;

        $model_a->column_y = 'C';
        $model_b->column_y = 'C';
        $model_c->column_y = 'A';
        $model_d->column_y = 'A';
        $model_e->column_y = 'B';

        $expected_collection->add($model_a);
        $expected_collection->add($model_b);
        $expected_collection->add($model_c);
        $expected_collection->add($model_d);
        $expected_collection->add($model_e);

        $this->collection->sortBy(array('column_x', SORT_ASC));

        $this->assertEquals($expected_collection, $this->collection);

    }

    /**
     * @author  Will
     */
    public function testPassingNothingToSortByThrowsException()
    {

        try {
            $this->collection->sortBy();
        }
        catch (CollectionException $e) {
            return;
        }

        $this->fail('There was no exception thrown when we tried to sort');
    }

}