<?php
namespace App\Core;
use PDO;
use Exception;
use PDOException;
use RuntimeException;

class QueryFilter{
    public string $Query = '';
    public array $Params = [];

    public function __construct(string $query = '', array $params = [])
    {
        $this->Query = $query;
        $this->Params = $params;
    }
}

class DB {
    private static string $DNS = '';
    private static string $User = '';
    private static string $Password = '';
    private static string $TimezoneOffset = 'SYSTEM';
    private static $Conn = null;
    private static int $TransactionLevel = 0;
    private static int $RowCount = 0;

    public static function setDNS(string $host, string $dbName){
        self::$DNS = 'mysql:dbname=' . $dbName . ';host=' . $host . ';charset=utf8';
    }

    public static function setUser(string $user, string $password){
        self::$User = $user;
        self::$Password = $password;
    }

    public static function setTimezone(string $timezone){
        self::$TimezoneOffset = self::getTimezoneOffset($timezone);
    }

    public static function getRowCount(): int{
        return self::$RowCount;
    }

    /**
     * Get timezone offset for specified timezone (use system default timezone if omitted)
     * @param string|null $timezone Valid timezone
     * @return string timezone offset as +/- H:i compatible with MySql/MariaDB time_zone variable
     */
    private static function getTimezoneOffset($timezone = null){
        // Use system default timezone
        if(empty($timezone)){
            $timezone = date_default_timezone_get();
        }

        $sysTz = new \DateTimeZone($timezone);
        $tzo = $sysTz->getOffset(new \DateTime());
        return (($tzo < 0)?'-':'+').gmdate("H:i", $tzo);
    }

    public static function beginTransaction(){
        if(self::$Conn === null){
            self::connect();
        }

		if(self::$TransactionLevel == 0){
			self::$Conn->beginTransaction();
		}else{
			self::$Conn->exec("SAVEPOINT LVL_" . self::$TransactionLevel);
		}

		self::$TransactionLevel++;
		
		return true;
	}
	
	public static function commit(){
		self::$TransactionLevel--;

		if(self::$TransactionLevel < 0){
			return false;
		}

		if(self::$TransactionLevel == 0){
			try{
				self::$Conn->commit();
			}catch(PDOException $e){
				throw new RuntimeException(["PDO commit failed", $e->getMessage()]);
			}
		}else{
			self::$Conn->exec("RELEASE SAVEPOINT LVL_" . self::$TransactionLevel);
		}

		return true;
	}

	public static function RollBack(){
		self::$TransactionLevel--;

		if(self::$TransactionLevel<0){
			return false;
		}

		if(self::$TransactionLevel==0){
			self::$Conn->rollBack();
		}else{
			self::$Conn->exec("ROLLBACK TO SAVEPOINT LVL_".self::$TransactionLevel);
		}

		return true;
	}

    /**
     * Create PDO connection to the DB and store it in $Conn var
     */
    private static function connect()
    {
        try{
            $initCom = "SET NAMES utf8mb4, wait_timeout=DEFAULT, interactive_timeout=DEFAULT, time_zone='". self::$TimezoneOffset . "';";
    
            self::$Conn = new PDO(self::$DNS, self::$User, self::$Password,array(PDO::MYSQL_ATTR_INIT_COMMAND => $initCom));
            
            // Force PDO Errors to throw exception instead of Raising E_WARNING (this is useful to catch warning)
            self::$Conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$Conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);// It's true by default
        }catch(PDOException $e){
            throw new RuntimeException('Failed to connect to Database: '. $e->getMessage());
        }
    }

    /**
     * Query DB for specified sql string
     * @param string $sql sql string to be executed
     * @param array $params associative array contains sql string required named params
     * @return array|int|boolean array for select result, int for inserted, boolean otherwise (execution success/fail)
     */
    public static function query($sql, $params=[]){
        self::$RowCount = 0;
        
        if(self::$Conn === null){
            self::connect();
        }

        // prepare the sql statement
		try{
			$stmt = self::$Conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		}catch (PDOException $e){
			throw new RuntimeException('Failed to prepare sql query: '. $e->getMessage());
		}

        // Bind named sql params
        try{
            foreach($params as $p=>$v){
                $p = trim($p);

                if(strpos($p, ':') !== 0){
                    $p = ':'.$p;
                }

                switch(true){
                    case is_null($v):
                        $stmt->bindValue($p, $v, PDO::PARAM_NULL);
                    break;

                    case is_array($v):
                        $stmt->bindValue($p, json_encode($v), PDO::PARAM_STR);
                    break;
    
                    case is_bool($v):
                         $stmt->bindValue($p, $v, PDO::PARAM_BOOL);
                    break;
    
                    case is_int($v):
                         $stmt->bindValue($p, $v, PDO::PARAM_INT);
                    break;
                   
                    case is_string($v):
                        $stmt->bindValue($p, $v, PDO::PARAM_STR);
                    break;
                    
                    case strlen($v) > 65535:
                        $stmt->bindValue($p, $v, PDO::PARAM_LOB);
                    break;
    
                    default:
                        $stmt->bindValue($p, $v, PDO::PARAM_STR);
                }
            }
        }catch(Exception $e){
            throw new RuntimeException('Faild to bind param : '. $e->getMessage());
        }
    
        $isOk = false;

        // Exexute the statement
        try{
            $isOk = $stmt->execute();
        }catch(Exception $e){
            throw new RuntimeException('Statement execution error: '. $e->getMessage());
        }

        // Get any execution errors
		$err = $stmt->errorInfo();
		if(isset($err[2])){
			throw new RuntimeException("Statement execution error:\n{$sql}");
        }
        
        // Update affected rows count
        self::$RowCount = $stmt->rowCount();

        // Get last inserted id if any
        $insertId = 0;
        $rowsets = [];

        if($isOk){
            $insertId = self::$Conn->lastInsertId();

            // Start looping available Rowsets
             do{
                 // Check if columns available (result available) before fetching the rows (which will cause error if no result returned)
                 if($stmt->columnCount()>0){
                     // Fetch rowsets
                     $rowsets[] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                 }
             }while($stmt->nextRowset());
     
             // close the statement
             $stmt->closeCursor();
        }
        
        // Return all rowsets or first one if no others exist
        if(count($rowsets) > 0){
            // If a query returned one rowset only we use the first result on rowset 0 otherwise we continue using an array of results
            return (count($rowsets)==1)?$rowsets[0]:$rowsets;
        }
		
        // Return last inserted id if any, otherwise return isOk
        if($insertId > 0){
            return $insertId;
        }

        return $isOk;
    }

    /**
     * Sanitize comma separated string to be safely used inside an IN function
     * @param string $csv Comma separated values
     * @return string Safe comma separated values with strings being quoted
     */
    public static function sanitizeInParam($csv){
        if(self::$Conn === null){
            self::connect();
        }

        if(is_string($csv)){
            $csv = explode(',', $csv);
        }
    
        $sanitaizedCSV = array_map(function($item){
            $item=trim($item);
            
            return is_numeric($item)?$item:self::$Conn->quote($item);
        }, $csv);
    
        return implode(',', $sanitaizedCSV);
    }

    /**
     * @param array $searchParams array of search params (name: value)
     * @param array|string $criteria array of (search param name(s) => [logical operator (AND/OR), sql condition, [allowed empty]]), when string it's the sql condition
     * - param name: comma separated, when empty the condition part will be always included (used to add default filter),
     * - allowed empty: array, the empty() function considers (0, '0', 0.0, null, false) as empty so the criteria will not be included, we can tell the builder to include the filter if one or more of these empty values appeared in the specified search param value
     * @param string $prefix prefix search params names to prevent collision with other query params that uses columns name 
     * @return QueryFilter Query filter object
     */
    public static function buildSQLFilter($searchParams, $criteria, $prefix = ''){
        if(is_null($searchParams)){
            return new QueryFilter();
        }

        // Trim string params values
        $searchParams = array_map(function($item){
            if(gettype($item) == 'string'){
                return trim($item);
            }

            return $item;
        }, $searchParams);
        
        $sqlFilter = [];
        $sqlParams = [];
        
        $operator = '';

        foreach($criteria as $pn => $crt){
            $allowedEmpty = [];
            $injected = false;
            
            if(empty($crt)){ // Nothing in the criteria
                continue;
                
            }elseif(is_string($crt)){
                $condition = $crt;

            }elseif(count($crt) == 1){
                $condition = $crt[0];
                
            }elseif(count($crt) == 2){
                $operator = $crt[0];
                $condition = $crt[1];
                
            }elseif(count($crt) == 3){
                $operator = $crt[0];
                $condition = $crt[1];
                $allowedEmpty = $crt[2];// allowed empty values array 
            }else{
                $operator = $crt[0];
                $condition = $crt[1];
                $allowedEmpty = $crt[2];
                $injected = !!$crt[3];// Injected param is a direct replace of the content and it will not be part of the PDO params
            }
            
            // $pn may have comma separated param names
            $pns = explode(',', $pn);
            
            foreach($pns as $pn){
                $pn = trim($pn);

                // Check if parameter name exists in searchParams (ignore the empty special case)
                if(!($pn == '' || array_key_exists($pn, $searchParams))){
                    // Continue the upper loop
                    continue 2;
                }
                
                // Ignore empty parameter values and include existing ones in the result
                // For some reasons in_array() always returns true for empty string, so we add an extra condition for that
                // Also be aware that 0 == '' but 0 !== ''
                if($pn !== ''){
                    if((!in_array('', $allowedEmpty) && $searchParams[$pn] === '') || (empty($searchParams[$pn]) && !in_array($searchParams[$pn], $allowedEmpty))){
                        // Continue the upper loop
                        continue 2;
                    }
                    
                    if($injected){
                        $condition = str_replace(":$prefix$pn", $searchParams[$pn], $condition);
                    }

                    // Adding existing params
                    if(!$injected){
                        $sqlParams[$prefix.$pn] = $searchParams[$pn];
                    }
                }
            }
            
            if(!empty($condition)){
                $sqlFilter[] = (empty($sqlFilter)?'WHERE': strtoupper($operator)).' '.$condition;
            }
        }

        return new QueryFilter("\r\n".implode("\r\n", $sqlFilter), $sqlParams);
    }

    public static function tableFromArray($arr, $colNames = []){
        if(self::$Conn === null){
            self::connect();
        }

        $tbl = [];

        foreach($arr as $r){
            $vals = [];

            for($i = 0; $i < count($r); $i++){
                $v = $r[$i];
                $n = $colNames[$i]?? $i;

                if(!is_numeric($v)){
                    $v = self::$Conn->quote($v);
                }

                $vals[] = "$v AS `$n`";
            }

            $tbl[] = 'SELECT ' . implode(', ', $vals);
        }

        return implode("\nUNION\n", $tbl);
    }

    public static function tableFromAssocArray($arr, $keyColName = 'key', $valueColName = 'value'){
        if(self::$Conn === null){
            self::connect();
        }

        $tbl = [];

        foreach($arr as $k => $v){
            if(!is_numeric($k)){
                $k = self::$Conn->quote($k);
            }

            if(!is_numeric($v)){
                $v = self::$Conn->quote($v);
            }

            $tbl[] = "SELECT $k AS `$keyColName`, $v as `$valueColName`";
        }

        return implode("\nUNION\n", $tbl);
    }
}
?>