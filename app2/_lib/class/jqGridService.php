<?php

class JqGridService {
	
	private static function vsord($val)
	{
		return  ($val=='asc' || $val=='desc')?$val:'desc';
	}
	
	private static function vsidx($val)
	{
		//cauta coloana ???
		return $val;
	}
	
	private static function getOp($filter=[])
	{
        $cond = "";
		if($filter['data'] == "''") return $cond; //bug <select> all
		switch ($filter['op'])
		{
				case 'eq': //equal
					$cond .= " and ".$filter['field']." = ".$filter['data'];
					break;
				case 'ne': //not equal
                    $cond .= " and ".$filter['field']." != ".$filter['data'];
					break;
				case 'lt': //less
                    $cond .= " and ".$filter['field']." < ".$filter['data'];
					break;
				case 'le': //less or equal
                    $cond .= " and ".$filter['field']." <= ".$filter['data'];
					break;
				case 'gt': //greater
                    $cond .= " and ".$filter['field']." > ".$filter['data'];
					break;
				case 'ge': //greater or equal
                    $cond .= " and ".$filter['field']." >= ".$filter['data'];   
					break;
				case 'bw': //begins with
                    $cond .= " and ".$filter['field']." like '".$filter['data']."%'";
					break;
				case 'bn': //does not begin with
                    $cond .= " and ".$filter['field']." not like '".$filter['data']."%'";
					break;
				case 'ew': //ends with
                    $cond .= " and ".$filter['field']." like '%".$filter['data']."'";
					break;
				case 'en': //does not end with
                    $cond .= " and ".$filter['field']." not like '%".$filter['data']."'";
					break;
				case 'cn': //contains
                    $cond .= " and ".$filter['field']." like '%".$filter['data']."%'";
					break;
				case 'nc': //does not contains
                    $cond .= " and ".$filter['field']." not like '%".$filter['data']."%'";
					break;
        }
        return $cond;
}
	
	public static function getJqGridFiltersCondition($filters)
	{
        $cond = "";
		//filters
		if(!empty($filters))
		{
			$filters = json_decode($filters, true);
			if($filters !== false && isset($filters['rules']) && is_array($filters['rules']))
			{
				foreach ($filters['rules'] as $filter)
				{
					if($filter['op'] == 'is in')
					{
						$cond .= " and ".$filter['field']." in (". explode(',',$filter['data']).")";
						continue;
					}
					if($filter['op'] == 'is not in')
					{
						$cond .= " and ".$filter['field']." not in (". explode(',',$filter['data']).")";
						continue;
					}
					$cond .= self::getOp($filter);
				}
			}
		}
		//error_log($cond);
		return $cond;
	}
}
