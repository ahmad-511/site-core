<?php
declare (strict_types = 1);
namespace App\Core;

use App\Core\DB;

/**
 * Base model class
 */
abstract class Model{
    /**
     * Query the database for specified sql string
     * @param string $sql SQL string to be executed
     * @param array $params Array of the sql named params
     * @return array of Rowsets if sql has multi result or one rowset if it contains one result
     */
    protected function query($sql, Array $params=[]){
        return DB::query($sql, $params);
    }

    /**
     * Sanitize comma separated string to be safely used inside an IN function
     * @param string $csv Comma separated values
     * @return string Safe comma separated values with strings being quoted
     */
    protected function sanitizeInParam($csv){
        return DB::sanitizeInParam($csv);
    }

    /**
     * @param array $searchParams array of search params (name: value)
     * @param array $criteria array of (search param name => [logical operator if occurs first, logical operator if occurs after, sql condition, [allowed empty]])
     * - allowed empty: the empty() function considers (0, '0', 0.0, null, false) as empty so the criteria will not be included, we can tell the builder to include the filter if one or more of these empty values appeared in the specified search param value
     * @param string $prefix prefix search params names to prevent collision with other query params that uses columns name 
     * @return QueryFilter Query filter object
     */
    protected function buildSQLFilter($searchParams, $criteria, $prefix = ''){
        return DB::buildSQLFilter($searchParams, $criteria, $prefix);
    }
}
