<?php
namespace FluentSQL;
use PDO;
use PDOException;
use LoggerApp\LoggerApp;

class SQL extends LoggerApp {

    function __construct( 
        $host,
        $db,
        $user,
        $passwd,
        private $username   = "INDEFINIDO",
        public $rows        = 0,
        public $error       = null,
        public $params      = null,
        public $stmt        = null,
        public $conn        = null,
        public $types       = array(
            "normal"        => PDO::PARAM_STR,
            "upper"         => PDO::PARAM_STR,
            "int"           => PDO::PARAM_INT 
        )
    ) 
    {

        date_default_timezone_set( 'America/Sao_Paulo' );
        $this->conn = new PDO( "mysql:host=$host;dbname=$db;charset=utf8", $user, $passwd );

        parent::__construct( date("dmY"), "SQL" );
        
    }

    function prepareQuery( $rawQuery, $params = array() ) {

        $this->params   = $params;
        $this->stmt     = $this->conn->prepare( $rawQuery );
        foreach( $params as $key => $value ) {
            $this->setParam( $value );
        }

        return $this;
    }

    function execQuery() {

        try {
            $this->stmt->execute();

            preg_match( "/^(?<command>SELECT)+/i", $this->stmt->queryString, $match );
            $this->rows += isset( $match["command"] ) && strtolower( $match["command"] ) === "select" ? 0 : $this->stmt->rowCount();
            
            
        }
        catch( PDOException $e ) {
            $this->error = array(
                "codeError" => $e->getCode(),
                "msg"       => $e->getMessage()
            );
        }
        
        return $this;

    }

    function sqlCommand() {

        ob_start();
            $this->stmt->debugDumpParams();
            $command = ob_get_contents();
        ob_end_clean();

        $this->push("info", "Dados enviados por ".$this->username .": " . json_encode($this->params) );
        $this->push("info",  $command );
        $this->push("info", "Erros : " . json_encode($this->stmt->errorInfo()) . "\n" );

        return $this;
    }

    function build($returnData = false) {
        if($this->error === null) 
        {
            if($returnData === true) {
                return $this->stmt->fetchAll( PDO::FETCH_ASSOC );
            }
            return $this->stmt;
        }
        return $this->error;

    }

    function transformValue( $tpys, $value ) {

        $transformed = array(
            "normal"    => function($value){ return $value; },
            "int"       => function($value){ return $value; },
            "upper"     => function($value){ return mb_strtoupper($value); },
        );
        return $transformed[ $tpys ]($value);
     }


     function setParam($value) : void {

        $transform  =  $this->transformValue( $value["type"], $value["value"] );
        $tpys       = $this->types[ $value["type"] ];
        $this->stmt->bindParam(
            $value["key"],
            $transform,
            $tpys
        );


     }

}


?>
