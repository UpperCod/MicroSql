<?php
namespace MicroSql;

require "utils.php";

class Query{
    const FETCH    = \PDO::FETCH_ASSOC;
    public $config = [];
    public $table  = "";
    public $lastQuery = "";
    function __construct($config){
        $this->config = (object)$config;
        $this->table  = $this->config->prefix.$this->config->table;
    }

    function where($where,$prefix="",$concat=" WHERE "){
        $and   = [];
        $bind  = [];
        $end   = [];
        $start = [];
        foreach($where as $index => $value){
            $alias = param(":".$prefix.$index);
            switch($index){
                case "@like":
                    foreach($value as $index => $value ){
                        $index = param($index);
                        $alias = ":{$prefix}like_{$index}";
                        $bind[$alias] = $value;
                        array_push($and,"{$index} LIKE {$alias}");
                    }
                break;
                case "@between":
                    foreach( $value as $index => $value ){
                        $index = param($index);
                        $alias = ":{$prefix}between_{$index}";
                        array_push($and,"{$index} BETWEEN {$alias}_0 AND {$alias}_1");
                        $bind[$alias."_0"] = $value[0];
                        $bind[$alias."_1"] = $value[1];
                    }
                break;
                case "@limit":
                    $alias = (int)$value;
                    array_push($end,"LIMIT {$alias}");
                break;
                case "@order":
                    $order = map(function($value,$index) use (&$bind){
                        $by    = $value >0 ? "ASC" : "DESC";
                        $index = param($index);
                        $alias = ":{$prefix}order_by_{$index}";
                        $bind[$alias] = $index;
                        return "{$alias} {$by}";
                    },$value);
                    array_unshift($end,"ORDER BY ".join(", ",$order));
                break;
                case "@join":
                    $join = map(function($value,$index){
                        $index = param($index);
                        $alias = $this->config->prefix.$index;
                        $query = "INNER JOIN {$alias} AS {$index}";
                        $on    = map(function($table_2,$table_1){
                            return " ON ".param($table_1)." = ".param($table_2);
                        },$value);
                        return $query.join("",$on);
                    },$value);
                    array_push($start,join(" ",$join));
                break;
                case "@or":
                    $or = map(function($value,$index) use ($prefix,&$bind){
                        $index = param($index);
                        list($condition,$subBind) = $this->where($value,"{$prefix}or_{$index}_","");
                        $bind = array_merge( $bind, $subBind);
                        return "( {$condition} )";
                    },$value);
                    array_push($and,join(" OR ",$or));
                break;
                case "@not":
                    $not = map(function($value,$index) use ($prefix,&$bind){
                        $index = param($index);
                        list($condition,$subBind) = $this->where($value,"{$prefix}not_{$index}_","");
                        $bind = array_merge( $bind, $subBind);
                        return "NOT ( {$condition} ) ";
                    },$value);
                    array_push($and,join(" AND ",$not));
                break;
                case '@in':
                    $in = map(function($values,$index) use ($prefix,&$bind){
                        $groups = [];
                        $index  = param($index);
                        foreach($values as $subIndex => $value){
                            $subIndex  = param($subIndex);
                            $cursor = ":{$prefix}in_{$index}_{$subIndex}";
                            $bind[$cursor] = $value;
                            array_push($groups,$cursor);
                        }
                        $groups = join(", ",$groups);
                        return "{$index} IN ({$groups})";
                    },$value);
                    array_push($and,join(" AND ",$in));
                break;
                case '@and':
                    $nextAnd = map(function($value,$index) use($prefix,&$bind){
                        $index = param($index);
                        if(is_array($value)){
                            $index = param($index);
                            list($condition,$subBind) = $this->where($value,"{$prefix}and_{$index}_","");
                            $bind = array_merge( $bind, $subBind);
                            return " ( {$condition} ) ";
                        }else{
                            $alias = ":{$prefix}and_{$index}";
                            $bind[$alias] = $value;
                            return "{$index} = {$alias} ";
                        }
                    },$value);
                    $and = array_merge($and,$nextAnd);
                break;
                default : 
                    $index = param($index);
                    array_push($and,"{$index} = {$alias}");
                    $bind[$alias] = $value;
            }
        }
        
        $query = join(" ",[
            join(" ",$start),
            count($and) ? $concat.join(" AND ",$and) : "" ,
            join(" ",$end)
        ]);
      
        return [ 
            $query,$bind
        ];
    }

    function bindValues($cursor,$values,$prefix=""){
        foreach($values as $index => $value){
            $cursor->bindValue($prefix.$index,$value);
        }
        return $cursor;
    }
    function serialize($params,$join=", "){
        return join($join,map(
            function($value){return param($value);},$params
        ));
    }
    function createQuery($type,$group_1,$group_2=""){
        $query = "";
        $this->lastQuery = "";
        switch($type){
            case "select":
                $query = "SELECT {$group_1} FROM {$this->table} AS {$this->config->table} {$group_2}";
            break;

            case "insert":
                $query = "INSERT INTO {$this->table} ({$group_1}) VALUES ({$group_2})";
            break;

            case "update":
                $query = "UPDATE {$this->table} SET {$group_1} {$group_2}";
            break;

            case "delete":
                $query = "DELETE FROM {$this->table} {$group_1}";
            break;
        }
        return $this->lastQuery = $query;
    }
    function select($where = false,$select=false){
        $select = $select ? $this->serialize($select): "*";

        if( $where ){

            foreach(
                ['count','avg','sum','min','max']
                as $value ){
                $index = "@{$value}";
                if( isset($where[$index]) ){
                    $alias = param($where[$index]);
                    $select = strtoupper($value)."({$alias}) AS {$value}_{$alias}";
                    unset($where[$index]);
                }
            }

            list($string,$bind) = $this->where($where);

            $cursor = $this->config->db->prepare(
                $this->createQuery("select",$select,$string)
            );

            $this->bindValues($cursor,$bind);
        }else{
            $cursor = $this->config->db->prepare(
                $this->createQuery("select",$select)
            );
        }
        
        return $cursor->execute() ? $cursor->fetchAll(self::FETCH) : [];
    }

    function update(array $update = [],$where = false){

        $set = map(function($value,$index){
            return param($index)." = :".param($index);
        },$update);

        $set   = join(", ",$set);

        $query = "UPDATE {$this->table} SET {$set}";

        if($where){
            list($string,$bind) = $this->where($where,"where_");
            $cursor = $this->config->db->prepare(
                $this->createQuery("update",$set,$string)
            );
            $this->bindValues($cursor,$bind);
        }else{
            $cursor = $this->config->db->prepare($query);
        }
        $this->bindValues($cursor,$update,":");
        return $cursor->execute();
    }

    function delete($where = false){
        if( $where ){
            list($string,$bind) = $this->where($where,"WHERE_");
            $cursor = $this->config->db->prepare(
                $this->createQuery("delete",$string)
            );
            $this->bindValues($cursor,$bind);
        }else{
            $cursor = $this->config->db->prepare(
                $this->createQuery("delete")
            );
        }
        return $cursor->execute();
    }

    function insert(array $insert){
        $string = $this->serialize(array_keys($insert));
        $repeat = join(", ",array_fill(0,count($insert),"?"));

        $cursor = $this->config->db->prepare(
            $this->createQuery(
                "insert",$string,$repeat
            )
        );

        return $cursor->execute(array_values($insert));
    }
}