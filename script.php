<?php
function peek($array)
{
    if (empty($array))
    {
        return null;
    }
    else
    {
        return $array[count($array) - 1];
    }
}

function leftPad($number, $targetLength)
{
    $output = $number . '';
    while (strlen($output) < $targetLength)
    {
        $output = '0' . $output;
    }
    return $output;
}

function formatDate($date){
    $mints = $date/60000;
    $m = $mints%60;
    $mints -= $m;
    $h = $mints/60;
    return leftPad($h, 2) . ":" . leftPad($m, 2);
}

function addAll($array){
    $temp = [];
    foreach($array as $ar){
        $temp[] = $ar;
    }
    return $temp;
}

class HDate
{
    function __construct($input)
    {
        $this->date = $this->todayMillis();
        if (gettype($input) == 'string')
        {
            $reg = '/(\d{4})\/(\d{1,2})\/(\d{1,2})/';
            preg_match($reg, $input, $arr, PREG_OFFSET_CAPTURE);
            if (is_array($arr) && count($arr) == 4)
            {
                $y = intval($arr[1][0]);
                $m = intval($arr[2][0]);
                $d = intval($arr[3][0]);
                $this->date = strtotime($y . '-' . $m . '-' . $d);
            }
        }
        elseif (gettype($input) == 'integer')
        {
            $this->date = $input;
        }
    }
    function formatted()
    {
        return date('Y/m/d', $this->date);
    }

    function todayMillis()
    {
        return strtotime(date('Y-m-d'));
    }

    function cloneTheObject()
    {
        return new HDate($this->date);
    }
    const DAY_VALUE = 86400000;

    function equals($other)
    {
        return ($other == $this);
    }
}

class HDateRange
{
    function __construct($input1, $input2)
    {
        // $this->starting = new HDate();
        // $this->ending = new Hdate();
        if (gettype($input1) == 'string')
        {
            $parts = explode('-', $input1);
            if (count($parts) > 0)
            {
                $s = $parts[0];
                $e = $parts[count($parts) - 1];
                $this->starting = new HDate($s);
                $this->ending = new HDate($e);
            }
        }
        if (gettype($input2) == 'string')
        {
            $this->ending = new HDate($input2);
        }
        if (gettype($input1) == 'integer')
        {
            $this->starting = new HDate($input1);
            $this->ending = $this
                ->starting
                ->cloneTheObject();
        }
        if (gettype($input2) == 'integer')
        {
            $this->ending = new HDate($input2);
        }
        if ($input1 instanceof HDate)
        {
            $this->starting = $input1->cloneTheObject();
            $this->ending = $this
                ->starting
                ->cloneTheObject();
        }
        if ($input2 instanceof HDate)
        {
            $this->ending = $input2->cloneTheObject();
        }
        if ($input1 instanceof HDateRange)
        {
            $this->starting = $input1
                ->starting
                ->cloneTheObject();
            $this->ending = $input1
                ->ending
                ->cloneTheObject();
        }
        if ($this->ending < $this->starting)
        {
            $temp = $this->starting;
            $this->starting = $this->ending;
            $this->ending = $temp;
        }
    }

    function formatted()
    {
        return $this
            ->starting
            ->formatted() . ' to ' . $this
            ->ending
            ->formatted();
    }
    function cloneTheObject()
    {
        return new HDateRange($this
            ->starting
            ->cloneTheObject() , $this
            ->ending
            ->cloneTheObject());
    }
    function isMergable($other)
    {
        $HDate = new HDate(strtotime(date('Y-m-d')));
        if ($other instanceof HDateRange)
        {
            $os = max($this
                ->starting->date, $other
                ->starting
                ->date);
            $oe = min($this
                ->ending->date, $other
                ->ending
                ->date);
            return $oe >= $os || $os - $oe == $HDate::DAY_VALUE;
        }
        return false;
    }
    function isOverlapping($other)
    {
        if ($other instanceof HDateRange)
        {
            $os = max($this
                ->starting->date, $other
                ->starting
                ->date);
            $oe = min($this
                ->ending->date, $other
                ->ending
                ->date);
            return $oe >= $os;
        }
        else
        {
            return false;
        }
    }
    static function merge($first, $second)
    {
        $HDate = new HDate(strtotime(date('Y-m-d')));
        if ($first instanceof HDateRange && $second instanceof HDateRange)
        {
            $os = max($first
                ->starting->date, $second
                ->starting
                ->date);
            $oe = min($first
                ->ending->date, $second
                ->ending
                ->date);
            if ($oe >= $os || $os - $oe == $HDate::DAY_VALUE)
            {
                $s = min($first
                    ->starting->date, $second
                    ->starting
                    ->date);
                $e = max($first
                    ->ending->date, $second
                    ->ending
                    ->date);
                return new HDateRange($s, $e);
            }
        }
        return null;
    }
    static function intersect($first, $second)
    {
        if ($first instanceof HDateRange && $second instanceof HDateRange)
        {
            $os = max($first->starting->date, $second->starting->date);
            $oe = min($first->ending->date, $second->ending->date);
            if ($oe >= $os)
            {
                return new HDateRange($os, $oe);
            }
        }
        return null;
    }
    function equals($other)
    {
        return ($other instanceof HDateRange && $this
            ->starting
            ->equals($other->starting) && $this
            ->ending
            ->equals($other->ending));
    }
    function includes($other)
    {
        $i = self::intersect($this, $other);
        return $other->equals($i);
    }
    function forEachData($callback)
    {

        if (gettype($callback) == 'resource')
        {
            $s = $this
                ->starting->date;
            $e = $this
                ->ending->date;
            $j = - 1;

            for ($i = $s;$i <= $e;$i = $i + HDate . DAY_VALUE)
            {
                $d = new HDate($i);
                $callback($d, ++$j);
            }
        }
    }
}

// $fun_var = function(){
//  echo 'Hii';
// };
// $hd = new HDateRange('2022-02-07', '2022-02-14');
// $hd->forEachData($fun_var);


class HDateRanges
{
    function __construct($items)
    {
        $this->items = [];
        foreach ($items as $item)
        {
            if ($item instanceof HDateRange)
            {
                $this->items[] = $item;
            }
        }
    }
    function forEach ($callback)
    {
        $c = 0;
        foreach ($this->items as $item)
        {
            if (gettype($callback) == 'resource')
            {
                $callback($item, $c);
                $c++;
            }
        }
    }
    function peek()
    {
        if (count($this->items) > 0)
        {
            return $this->items[count($this->items) - 1];
        }
        else
        {
            return null;
        }
    }
    function isEmpty()
    {
        return (count($this->items) == 0);
    }
    function length()
    {
        return count($this->items);
    }
    function push($item)
    {
        if ($item instanceof HDateRange)
        {
            $this->item[] = $item;
        }
    }
    function cloneTheObject()
    {
        $ret = [];
        foreach ($this->items as $item)
        {
            $ret[] = $item->cloneTheObject();
        }
        $r = new HDateRanges($this->items);
        $r->items = $ret;
        return $r;
    }
    function sorted()
    {
        return $this->cloneTheObject()->sort();
    }
    function sort()
    {
        // print_r($this->items);
        // $this->items->sort = function($a, $b){
        //     return $a->starting->date - $b->starting->date;
        // };
        sort($this->items);
        return $this->items;
    }
    function merge(){
        $ret = null;
        $c = 0;
        foreach($this->sort() as $item)
        {
            if($c == 0)
            {
                $ret = $item;
            }
            else
            {
                $HDateRange = new HDateRange();
                $ret = $HDateRange->merge($ret, $item);
            }
        }
        $c++;
        return $ret;
    }
    function merged()
    {
        return $this->cloneTheObject()->merge();
    }
    function optimize()
    {
        $HDateRange = new HDateRange('2022/03/02 - 2022/04/17', '2022/03/02 - 2022/04/17');
        $stack = [];
        foreach($this->sort() as $item){
            if(empty($stack)|| $item->starting->date > peek($stack)->ending->date)
            {
                $t = $item->cloneTheObject();
                $stack[] = $t;
            }
            // $stack = (object)$stack;
            if(peek($stack)->ending->date < $item->ending->date)
            {
                peek($stack)->ending = $item->ending->cloneTheObject();
            }
        }
        $this->items = $stack;
        $opt = [];
        foreach($this->items as $item){
            if(empty($opt)){
                $opt[] = $item->cloneTheObject();
            }
            else{
                $m = $HDateRange->merge(peek($opt), $item->cloneTheObject());
                if($m == null){
                    $opt[] = $item->cloneTheObject();
                }
                else{
                    peek($opt)->start = $m->starting;
                    peek($opt)->end = $m->ending;
                }
            }
        }
        $this->items = $opt;
        return $this;
    }

    
    function formatted()
    {
        $ret = "";
        print_r($this->items);
        foreach ($this->items as $item)
        {
            if ($ret == "") $ret = $item->formatted();
            else $ret .= "\n" . $item->formatted();
        }
        return $ret;
    }
    function adopt($other)
    {
        if (other instanceof HDateRanges) $this
            ->items
            ->addAll($other->items);
    }
    function getAt($index)
    {
        // print_r($this->items);
        if (count($this->items) > $index && $index > - 1)
        {
            return $this->items[$index];
        }
    }
    static function intersect($f, $s)
    {
        $fo = $f->cloneTheObject()->optimize();
        $so = $s->cloneTheObject()->optimize();
        $i = $j = 0;
        $n = count($fo->items);
        $m = count($so->items);
        $ret = [];
        while ($i < $n && $j < $m)
        {
            $l = max($fo->items[$i]
                ->starting->date, $so->items[$j]->starting->date);
            $r = min($fo->items[$i]
                ->starting->date, $so->items[$j]->starting->date);
            if ($l <= $r)
            {
                $ret->push(new HDateRange($l, $r));
            }
            if ($fo->items[$i]->ending->date < $so->items[$j]->ending->date) $i++;
            else $j++;
        }
        $r = new HDateRanges(new HDateRange('2022/02/02', '2022/02/07'), new HDateRange('2022/02/02', '2022/02/07'));
        $r->items = $ret;
        return $r->optimize();
    }
    function equals($other)
    {
        if ($other instanceof HDateRanges)
        {
            $t = $this->cloneTheObject();
            $o = $other->cloneTheObject();
            $t->optimize();
            $o->optimize();
            if (count($t->items) == count($o->items))
            {
                for ($i = 0;$i < count($t->items);$i++)
                {
                    $tt = $t->items[$i];
                    $oo = $o->items[$i];
                    if (!$tt->equals($oo)) return false;
                }
                return true;
            }
        }
        return false;
    }
    function includes($other)
    {
        $i = $HDateRanges->intersect($this, $other);
        return othe . equals($i);
    }
}

class HTime
{
    function __construct($input)
    {
        $this->time = 0;
        if(gettype($input) == 'string')
        {
            $reg = '/(\d{1,2}):(\d{1,2})/';
            preg_match($reg, $input, $parts, PREG_OFFSET_CAPTURE);
            $h = 0;
            $m = 0;
            // print_r($parts);
            if(count($parts) == 3){
                $h = intval($parts[1][0]);
                $m = intval($parts[2][0]);
                $this->time = ($h*60 + $m)*60000;
            }
            // $this->time = strtotime($input);
        }
        if(gettype($input) == 'integer')
        {
            $this->time = $input;
        }
    }
    function formatted()
    {
        // return date('H:i', $this->time);
        $mints = $this->time/60000;
        $m = $mints%60;
        $mints -= $m;
        $h = $mints/60;
        return leftPad($h, 2) . ":" . leftPad($m, 2);
    }
    function cloneTheObject()
    {
        return new HTime($this->time);
    }
    function equals($other)
    {
        return ($other instanceof HTime && $this->time == $other->time);
    }
}

class HTimeRange
{
    function __construct($input1, $input2)
    {
        $this->starting = 0;
        $this->ending = 0;
        if(gettype($input1) == 'string')
        {
            $parts = explode('-', $input1);
            if(count($parts) > 0)
            {
                $this->starting = new HTime($parts[0]);
                $this->ending = new HTime($parts[count($parts)-1]);
            }
        }
        if(gettype($input2) == 'string')
        {
            $this->ending = new HTime($input2);
        }
        if(gettype($input1) == 'integer')
        {
            $this->starting = new HTime($input1);
            $this->ending = $this->starting->cloneTheObject(); 
        }
        if(gettype($input2) == 'integer')
        {
            $this->ending = new HTime($input2);
        }
        if($input1 instanceof HTime)
        {
            $this->starting = $input1->cloneTheObject();
            $this->ending = $this->starting->cloneTheObject();
        }
        if($input2 instanceof HTime)
        {
            $this->ending = $input2->cloneTheObject();
        }
        if($input1 instanceof HTimeRange)
        {
            $this->starting = $input1.starting.cloneTheObject;
            $this->ending = $input1->ending.cloneTheObject();
        }
        if($this->ending->time < $this->starting->time)
        {
            $temp = $this->starting->time;
            $this->starting->time = $this->ending->time;
            $this->ending->time = $temp;
            $this->ending->time = $temp;
        }
    }
    function cloneTheObject()
    {
        return new HTimeRange($this->starting->cloneTheObject(), $this->ending->cloneTheObject());
    }
    function formatted(){
        return $this->starting->formatted() . " to " . $this->ending->formatted();
    }
    static function merge($first, $second)
    {
        if($first instanceof HTimeRange && $second instanceof HTimeRange)
        {
            $s = max($first->starting->time, $second->starting->time);
            $e = min($first->ending->time, $second->ending->time);
            if($e>=$s)
            {
                $start = min($first->starting->time, $second->starting->time);
                $end = max($first->ending->time, $second->ending->time);
                return new HTimeRange($start, $end);
            }
        }
        return null;
    }
    static function intersect($first,$second)
    {
        $s = max($first->starting->time, $second->starting->time);
        $e = min($first->ending->time, $second->ending->time);
        if($e >= $s)
            return new HTimeRange($s, $e);
        return null;
    }
    function equals($other)
    {
        $se = $this->starting->equals($other->starting);
        $ee = $this->ending->equals($other->ending);
        return ($other instanceof HTimeRange)&&$se&&$ee;
    }
    function includes($other)
    {
        $i = $this::intersect($this, $other);
        if(is_object($i))
            return $other->equals($i);
        else
            return false;
    }
    function slots($duration)
    {
        $ret = [];
        if(gettype($duration) == 'integer' && $duration>0){
            for($i=$this->starting->time; $i<= $this->ending->time; $i+=$duration)
            {
                $s = $i;
                $e = $s + $duration;
                if($e <= $this->ending->time)
                {
                    $ret[] = new HTimeRange($s, $e);
                }
            }
        $r = new HTimeRanges($this);
        $r->items = $ret;
        return $r;
        }   
    }
}
class HTimeRanges
{
    function __construct($items)
    {
        $this->items = [];
        foreach($items as $item)
        {
            if($item instanceof HTimeRange)
            {
                $this->items[] = $item;
            }
        }
    }
    function forEach($callback)
    {
        $c = 0;
        foreach($this->items as $item)
        {
            if(gettype($callback) == 'resource')
            {
                $callback($item, $c);
            }
            $c++;
        }
    }
    function peek()
    {
        if(count($this->items) > 0)
        {
            return $this->items[count($this->items) - 1];
        }
        else
        {
            return null;
        }
    }
    function isEmpty()
    {
        return (count($this->items) == 0);
    }
    
    function cloneTheObject()
    {
        $ret = [];
        foreach($this->items as $item)
        {
            $ret[] = $item->cloneTheObject();
        }
        $r = new HTimeRanges($this);
        $r->items = $ret;
        return $r;
    }
    function sorted()
    {
        return $this->cloneTheObject()->sortArr();
    }
    function sortArr()
    {
        sort($this->items);
        return $this->items;
    }
    function merge()
    {
        $this->sortArr();
        $stack = [];
        foreach($this->items as $item)
        {
            if(empty($stack) || $item->starting->time > $stack[count($stack) - 1]->ending->time)
            {
                $stack[] = $item->cloneTheObject();
            }
            else
            {
                if($stack[count($stack) - 1]->ending->time < $item->ending->time)
                {
                    $stack[] = $item->cloneTheObject();
                }
                else
                {
                    if($stack[count($stack) - 1]->ending->time < $item->ending->time)
                    {
                        $stack[count($stack) - 1]->end()->time = $item->end->time;
                    }
                }
            }
        }
        $this->items = $stack;
        return $this;
    }
    function merged()
    {
        return $this->cloneTheObject()->merge();
    }
    function formatted()
    {
        $ret = "";
        foreach($this->items as $item)
        {
            if($ret == "")
            {
                $ret = $item->formatted();
            }
            else
            {
                $ret = $ret . "\n" . $item->formatted();
            }
        }
        return $ret;
    }
    function adopt($other)
    {
        if($other instanceof HTimeRanges){
            $this->items->addAll($other->items);
        }
    }
    function getAt($index)
    {
        if(count($this->items) > $index && $index > -1)
        {
            return $this->items[$index];
        }
    }
    static function intersect($f, $s)
    {
        $fo = $f->cloneTheObject()->merge();
        $so = $s->cloneTheObject()->merge();
        $i = $j = 0;
        
        $n = count($fo->items);
        $m = count($so->items);
        
        $ret = [];
        while($i < $n && $j < $m)
        {
            $l = max($fo->items[$i]->starting->time, $so->items[$j]->starting->time);
            $r = min($fo->items[$i]->ending->time, $so->items[$j]->ending->time);
            if($l <= $r)
            {
                $ret[] = new HTimeRange($l, $r);
            }
            if($fo->items[$i]->ending->time < $so->items[$j]->ending->time)
                $i++;
            else
                $j++;
        }
        $r = new HTimeRanges(new HTimeRange('10:00', '12:00'));
        $r->items = $ret;
        return $r->merge();
    }
    function equals($other)
    {
        if($other instanceof HTimeRanges)
        {
            $t = $this->cloneTheObject();
            $o = $other->cloneTheObject();
            $t->merger();
            $o->merger();
            if(count($t) == count($o))
            {
                for($i = 0; $i < count($t); $i++)
                {
                    $tt = $t->items[$i];
                    $oo = $o->items[$i];
                    if(!$tt.equals($oo))
                    {
                        return false;
                    }
                }
                return true;
            }
        }
        return false;
    }
    function includes($other)
    {
        $i = $HTimeRanges->intersect($this, $other);
        return $other.euals($i);
    }
    function sessions($duration)
    {
        $t = $this->cloneTheObject();
        $ret = [];
        
        if(gettype($duration) == 'integer')
        {
            foreach($t->items as $item)
            {
                $slots = $item->slots($duration);
                // print_r($slots);
                $ret = addAll($slots);
            }
        }
        $r = new HTimeRanges(new HTimeRange('10:00 - 12:00', '18:00 - 20:00'));
        $r->items = $ret;
        return $r;
    }
}

class HAvailability
{
    function __construct($dateRange, $timeRange)
    {
        $this->dateRange = null;
        $this->timeRange = null;
        if($dateRange instanceof HDateRange && $timeRange instanceof HTimeRanges)
        {
            $this->dateRange = $dateRange;
            $this->timeRange = $timeRange;
        }
    }
    function cloneTheObject()
    {
        return new HAvailability($this->dateRange->cloneTheObject(), $this->timeRange->cloneTheObject());
    }
    function formatted()
    {
        $a = explode("\n",$this->dateRange->formatted() . "\n". $this->timeRange->formatted());
        $b = [];
        foreach($a as $aItems)
        {
            $b[] = "\t".$aItems;
        }
        return implode("\n", $b);
    }
    function optimize()
    {
        $this->timeRange->merge();
        return $this;
    }
    static function instersect($first, $second)
    {
        $f = $first->cloneTheObject();
        $s = $second->cloneTheObject();
        $d = $HDateRange->intersect($f->dateRange, $s->dateRange);
        if($d == null)
            return null;
        $t = $HTimeRanges->intersect($f->timeRange, $s->timeRange);
        if($t == null || count($t) == 0)
            return null;
        return new HAvailability($d, $t);
    }
    function equals($other)
    {
        if($other instanceof HAvailability)
        {
            $t = $this->cloneTheObject();
            $o = $other->cloneTheObject();
            $t->optimize();
            $o->optimize();
            $de = $t->dateRange->equals(o.dateRange);
            $te = $t->timeRange->equals(o.timeRange);
            return de&&te;
        }
        return false;
    }
    function includes($other)
    {
        $i = $HAvailability->intersect($this, $other);
        return $other->equals($i);
    }
    function buildSessions($duration)
    {
        $ret = [];
        $dr = $this->dateRange->cloneTheObject();
        $tr = $this->timeRange->cloneTheObject();
        $sessions = $tr->sessions($duration);
        if((gettype($duration) == 'integer') && ($duration > 0))
        {
            $dr->forEachData = function($date, $index)
            {
                echo 'inside';
                $dr = new HDateRange($date->cloneTheObject(), $date->cloneTheObject());
                $a = new HAvailability($dr, $sessions);
                $ret.push($a);
            };
        }
        $ha = new HAvailabilities($this);
        $ha->items = $ret;
        return $ha;
    }
}

class HAvailabilities
{
    function __construct($items)
    {
        $this->items = [];
        foreach($items as $item)
        {
            if($item instanceof HAvailability)
            {
                $this->items[] = $item;
            }
        }
    }
    function forEach($callback)
    {
        $c = 0;
        foreach($this->items as $item)
        {
            if(gettype($callback) == 'resource')
            {
                $callback($item, $c);
            }
        }
        $c++;
    }
    function peek()
    {
        if(count($this->items) > 0)
        {
            return $this->items[count($this->items)-1];
        }
        else
        {
            return null;
        }
    }
    function isEmpty()
    {
        return (count($this->items) == 0);
    }
    function length()
    {
        return count($this->items);
    }
    function push($item)
    {
        if($item instanceof HAvailability)
        {
            $this->items[] = $item;
        }
    }
    function cloneTheObject()
    {
        $ret = [];
        foreach($this->items as $item)
        {
            $ret[] = $item->cloneTheObject();
        }
        $r = new HAvailabilities($this);
        $r->items = $ret;
        return $r;
    }
    function getAt($index)
    {
        if(count($this->items) > $index && $index > -1)
        {
            return $this->items[$index];
        }
    }
    function formatted()
    {
        $ret = "";
        foreach($this->items as $item)
        {
            if($ret == "")
            {
                $ret = $item->formatted();
            }
            else
            {
                $ret = $ret."\n".$item->formatted();
            }
        }
        return $ret;
    }
    function sort()
    {
        sort($this->items);
        return $this->items;
    }
    function optimize()
    {
        foreach($this->items as $item)
        {
            $item->timeRange->merge();
        }
        
        $this->sort();
        $numbers = [];
        
        foreach($this->items as $item){
            $numbers[]=$item->dateRange->starting->date;
            $numbers[]=$item->dateRange->ending->date;
        }
        sort($numbers);
        $cutters = $numbers;
        
        $splitted = [];
        function splitRange($range, $cutters)
        {
            $start = $range->dateRange->starting->date;
            $end = $range->dateRange->ending->date;
            $times = $range->timeRanges;
            
            $startIndex = array_search($start, $cutters);
            $endIndex = array_search($end, $cutters);
            
            $count =($endIndex - $startIndex) + 1;
            $myCutters = array_slice($cutters, $startIndex, $endIndex+1);
            $ret = [];
            if($count == 1)
            {
                $first = $myCutters[0];
                $pp = new HAvailability(new HDateRange(new HDate($first), new HDate($first)), $times->cloneTheObject());
                $ret[] = $pp;
            }
            else
            {
                for($i=0; $i<count-1; $i++)
                {
                    $first = myCutters[$i];
                    $last = myCutters[$i+1];
                    if($i==0)
                    {
                        $pp = new HAvailability(new HDateRange(new HDate($first), new HDate($first)), $times.cloneTheObject());
                        $ret[] = $pp;
                    }
                    $f = $first + $HDate->DAY_VALUE;
                    $l = $last- $HDate->DAY_VALUE;
                    if($f<=$l&&$f<$last&&$l>$first)
                    {
                        $pp = new HAvailability(new HDateRange(new HDate($f), new HDate($l)), $times.cloneTheObject());
                        $ret[] = $pp;
                    }
                    $pp = new HAvailability(new HDateRange(new HDate($last), new HDate($last)), $times->cloneTheObject);
                    $ret[] = $pp;
                }
            }
            return $ret;
            for($i = 0; $i < count($this->items); $i++)
            {
                $item = $this->items[$i];
                $s = splitRange($item, $cutters)->$splitted->$addAll($s);
            }
            $splitted->sort = function($a, $b){
                return $a->dateRange->starting->date - $b->dateRange->starting->date;
            };
            
            $ret = [];
            function mergeTimes($times)
            {
                if(!is_array($times))
                {
                    return [];
                }
                $count = count($times);
                
                if($count == 0)
                {
                    return [];
                }
                
                $times->sort = function($a, $b){
                    return $a->starting->time - $b->starting->time;
                };
                $ret = [];
                
                for($i = 0; $i < count; $i++)
                {
                    $timeRange = $times[$i];
                    if($ret->isEmpty() || $timeRange->starting->time > $ret->peek()->ending->time)
                    {
                        $ret[] = $timeRange;
                    }
                    if($ret->peek()->ending->time < $thistimeRange->ending->time)
                    {
                        $ret->peek()->ending->time = $timeRange->ending->time;
                    }
                }
                return $ret;
            }
            for ($i = 0; $i < count($splitted); $i++)
            {
                $range = $splitted[$i];
            
                if($ret->isEmpty())
                {
                    $ret[] = $range;
                }
                else
                {
                    if($ret->peek()->dateRange->starting->date == $range->dateRange->start->date && $ret->peek()->dateRange->end->date == $range->dateRange->end->date)
                    {
                        $ret->peek()->timeRanges->items = mergeTimes([$ret->peek()->timeRanges->items, $range->timeRanges->items]);
                    }
                    else{
                        $ret[] = $range;
                    }
                }
            }
            $ret2 = [];
            function timeRangeArraysEqual($first, $second)
            {
                $fc = count($first);
                $sc = count($second);
                if($fc != $sc)
                {
                    return false;
                }
                for($i=0; $i<count($first); $i++)
                {
                    $f = $first->items[$i];
                    $s = $second->items[$i];
                    if($f->starting->time != $s->starting->time||$f->ending->time != $s->ending->time)
                    {
                        return false;
                    }
                }
                return true;
            }
            for($i = 0; $i < count($ret); $i++)
            {
                $item = $ret[$i];
                if($ret2->isEmpty())
                {
                    $ret2[] = $item;
                }
                else
                {
                    $last = $ret2->peek();
                    $gap = $item->dateRange->starting->date;
                    if($gap == HDate.DAY_VALUE)
                    {
                        if(timeRangeArraysEqual($last->timeRanges, $item->timeRanges))
                        {
                            $ret2->peek()->dateRange->end = $item->dateRange->end;
                            $ret2->peek()->dateRange->end = $item->dateRange->end;
                            $ret2->peek()->timeRanges = $item->timeRanges;
                        }
                        else{
                            $ret2[] = $item;
                        }
                    }
                    else{
                        $ret2[] = $item;
                    }
                }
            }
            $this->items = $ret2;
            return $this;
        }
        function intersect($first, $second)
        {
            $htimerange3 = new HTimeRange('13:00', '15:00');
            $htimerange4 = new HTimeRange('16:00', '20:00');
            $HTimeRanges = new HTimeRanges([$htimerange3, $htimerange4]);
            $f = $first;
            $s = $second;
            
            $i = $j = 0;
            
            $n = count($f->items);
            $m = count($s->items);
            
            $ret = [];
            while($i < $n && $j < $m)
            {
                $l = max($f->items[$i]->dateRange->starting->date, $s->items[$j]->dateRange->starting->date);
                $r = min($f->items[$i]->dateRange->ending->date, $s->items[$j]->dateRange->ending->date);
                if($l <= $r)
                {
                    $t1 = $f->items[$i]->timeRange;
                    $t2 = $s->items[$i]->timeRange;
                    $t = $HTimeRanges->intersect($t1, $t2);
                    if($t !== null)
                    {
                        $ret[] = new HAvailability(new HDateRange($l, $r), $t);
                    }
                }
                if($f->items[$i]->dateRange->ending->date < $s->items[$j]->dateRange->ending->date)
                    $i++;
                else
                    $j++;
            }
            $h = new HAvailabilities(new HAvailabilities(['xcv','cv']));
            $h->items = $ret;
            return $h;
        }
        function equals($other)
        {
            if($other instanceof HAvailabilities)
            {
                $t = $this->cloneTheObject();
                $o = $other->cloneTheObject();
                $t->optimize();
                $o->optimize();
                if(count($t) == count($o))
                {
                    for($i=0;$i<count($t); $i++)
                    {
                        $tt = $t->items[$i];
                        $oo = $o->items[$i];
                        if(!$tt->equals($oo)){
                            return false;
                        }
                    }
                    return true;
                }
            }
            return false;
        }
        function includes($other)
        {
            $i = $HAvailabilities->intersect($this->other);
            return $other->equals($i);
        }
        function buildSessions($required, $duration)
        {
            $r = new HAvailabilities(['df', 'fddf']);
            $ret = [];
            if($required instanceof HAvailabilities)
            {
                $i = intersect($this, $required);
                foreach($i->items as $item)
                {
                    $a = $item->buildSessions($duration);
                    $ret = addAll($a->items);
                }
            }
            $r->items = $ret;
            return $r;
        }
    }
}
