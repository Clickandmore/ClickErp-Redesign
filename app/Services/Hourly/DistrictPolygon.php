<?php

namespace App\Services\Hourly;

use Illuminate\Validation\ValidationException;

class DistrictPolygon
{
    /** A single ring in [latitude, longitude] order. Null explicitly clears the boundary. */
    public static function validate($points): ?array
    {
        if ($points === null) return null;
        $fail=fn()=>throw ValidationException::withMessages(['polygon'=>'ارسم مضلعًا صالحًا بثلاث نقاط مختلفة على الأقل، دون تقاطع الأضلاع.']);
        if(!is_array($points) || count($points)<3 || count($points)>1000) $fail();
        $ring=[];
        foreach($points as $point) {
            if(!is_array($point) || array_keys($point)!==[0,1] || !is_numeric($point[0]) || !is_numeric($point[1]) || abs((float)$point[0])>90 || abs((float)$point[1])>180) $fail();
            $ring[]=[round((float)$point[0],7),round((float)$point[1],7)];
        }
        if($ring[0]===end($ring)) array_pop($ring);
        $n=count($ring);
        if($n<3 || count(array_unique(array_map('json_encode',$ring)))!==$n) $fail();
        $cross=fn($a,$b,$c)=>($b[0]-$a[0])*($c[1]-$a[1])-($b[1]-$a[1])*($c[0]-$a[0]);
        $area=0;
        for($i=0;$i<$n;$i++) {
            $a=$ring[$i];$b=$ring[($i+1)%$n];$area+=$a[0]*$b[1]-$b[0]*$a[1];
            for($j=$i+2;$j<$n;$j++) {
                if($i===0 && $j===$n-1) continue;
                $c=$ring[$j];$d=$ring[($j+1)%$n];
                $overlap=max(min($a[0],$b[0]),min($c[0],$d[0]))<=min(max($a[0],$b[0]),max($c[0],$d[0])) && max(min($a[1],$b[1]),min($c[1],$d[1]))<=min(max($a[1],$b[1]),max($c[1],$d[1]));
                if($overlap && $cross($a,$b,$c)*$cross($a,$b,$d)<=0 && $cross($c,$d,$a)*$cross($c,$d,$b)<=0) $fail();
            }
        }
        if(abs($area)<1e-12) $fail();
        return $ring;
    }
}
