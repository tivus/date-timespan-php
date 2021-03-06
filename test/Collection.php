<?php
require_once __DIR__ . '/../src/Timespan/Timespan.php';
require_once __DIR__ . '/../src/Timespan/Collection.php';

use Timespan\Timespan;
use Timespan\Collection;

class CollectionTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $collection = new Collection();

        $this->assertTrue($collection->isEmpty());

        $start = new DateTime('last monday');
        $end = clone $start;
        $end->modify('+1 week');
        $collection[] = new Timespan($start, $end);

        $start = clone $start;
        $start->modify('+3 week');
        $end = clone $start;
        $end->modify('+1 week');
        $collection[] = new Timespan($start, $end);

        $start = clone $start;
        $start->modify('+3 week');
        $end = clone $start;
        $end->modify('+1 week');
        $collection[] = new Timespan($start, $end);

        $this->assertEquals(3, count($collection));

        return $collection;
    }

    /**
     * @depends testConstructor
     */
    public function testDiff($collection)
    {
        $new = clone $collection;
        foreach ($new as &$span) {
            $span->start->modify('+5 days');
            $span->end->modify('+5 days');
        }

        $diff = $collection->diff($new);
        $this->assertEquals(3, count($diff));
        $this->assertEquals($collection[0]->start, $diff[0]->start);
        $firstEnd = clone $collection[0]->end;
        $this->assertEquals($firstEnd->modify('-2 days'), $diff[0]->end);

        $c1 = new Collection();
        $c1[] = new Timespan(new DateTime('T9:00:00'), new DateTime('T11:30:00'));

        $c2 = new Collection();
        $c2[] = new Timespan(new DateTime('T8:45:00'), new DateTime('T9:30:00'));
        $c2[] = new Timespan(new DateTime('T9:40:00'), new DateTime('T10:30:00'));

        $d = $c1->diff($c2);

        $this->assertEquals(2, count($d));
        $this->assertEquals($d[0]->start->format('H:i'), '09:30');
        $this->assertEquals($d[0]->end->format('H:i'), '09:40');
        $this->assertEquals($d[1]->start->format('H:i'), '10:30');
        $this->assertEquals($d[1]->end->format('H:i'), '11:30');

        $c1 = new Collection();
        $c1[] = new Timespan(new DateTime('T9:00:00'), new DateTime('T10:00:00'));
        $c1[] = new Timespan(new DateTime('T10:15:00'), new DateTime('T10:30:00'));
        $c1[] = new Timespan(new DateTime('T10:45:00'), new DateTime('T11:30:00'));
        $c1[] = new Timespan(new DateTime('T11:45:00'), new DateTime('T13:00:00'));

        $c2 = new Collection();
        $c2[] = new Timespan(new DateTime('T8:30:00'), new DateTime('T8:45:00'));
        $c2[] = new Timespan(new DateTime('T9:00:00'), new DateTime('T9:30:00'));
        $c2[] = new Timespan(new DateTime('T9:45:00'), new DateTime('T11:00:00'));
        $c2[] = new Timespan(new DateTime('T11:15:00'), new DateTime('T12:00:00'));

        $d = $c1->diff($c2);

        $this->assertEquals(3, count($d));
        $this->assertEquals($d[0]->start->format('H:i'), '09:30');
        $this->assertEquals($d[0]->end->format('H:i'), '09:45');
        $this->assertEquals($d[1]->start->format('H:i'), '11:00');
        $this->assertEquals($d[1]->end->format('H:i'), '11:15');
        $this->assertEquals($d[2]->start->format('H:i'), '12:00');
        $this->assertEquals($d[2]->end->format('H:i'), '13:00');
    }

    /**
     * Tests sort,compress and merge
     * @depends testConstructor
     */
    public function testMerge($original)
    {
        $collection = clone $original;
        $new = clone $collection;
        foreach ($new as &$span) {
            $span->start->modify('+2 days');
            $span->end->modify('+2 days');
        }
        $new->exchangeArray(array_reverse($new->getArrayCopy()));
        $collection->merge($new);

        $this->assertEquals(count($original), count($collection));
        $this->assertEquals($original[0]->start, $collection[0]->start);
        $firstEnd = clone $original[0]->end;
        $this->assertEquals($firstEnd->modify('+2 days'), $collection[0]->end);

        $collection = clone $original;
        $tmp = array();
        foreach ($collection as $span) {
            $n = clone $span;
            $n->start->modify('+2 days');
            $n->end->modify('+2 days');
            $tmp[] = $n;
            $n = clone $span;
            $n->start->modify('+1 days');
            $n->end->modify('+3 days');
            $tmp[] = $n;
            $n = clone $span;
            $n->start->modify('+5 days');
            $n->end->modify('+7 days');
            $tmp[] = $n;
        }
        $new = new Collection($tmp);
        $collection->merge($new);

        $this->assertEquals(count($original), count($collection));
        $this->assertEquals($original[0]->start, $collection[0]->start);
        $firstEnd = clone $original[0]->end;
        $this->assertEquals($firstEnd->modify('+7 days'), $collection[0]->end);
    }

    /**
     * @depends testConstructor
     */
    public function testToArray($collection)
    {
        $this->assertTrue(count($collection->toArray()) > 0);
    }

    /**
     * @depends testConstructor
     */
    public function testToString($collection)
    {
        $this->assertTrue(is_string((string)$collection));
    }
}
