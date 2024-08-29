<?php
namespace FluentSQL;
use PDO;
use PDOException;

class SQL {

    public $rows = 0;
    public $stmt;
    public $conn;
    public $error = null;
    public $types = array(
        "normal" => PDO::PARAM_STR,
        "upper"  => PDO::PARAM_STR,
        "int"   => PDO::PARAM_INT
    );

    //CONSTRUTOR
    function __construct($host, $db, $user, $passwd) {

        date_default_timezone_set('America/Sao_Paulo');
        $this->conn = new PDO( "mysql:host=".$host.";dbname=".$db.";charset=utf8", $user, $passwd );

    }

    function prepareQuery( $rawQuery, $params = array() ) {

        $this->stmt = $this->conn->prepare( $rawQuery );
        foreach( $params as $key => $value ) {

            $this->setParam( $value );
        }

        return $this;
    }

    function execQuery() {

        try {
            $this->stmt->execute();
            $this->rows += $this->stmt->rowCount();
        }
        catch( PDOException $e ) {
            $this->error = array(
                "codeError" => $e->getCode(),
                "msg" => $e->getMessage()
            );
        }
        
        return $this;

    }

    function sqlCommand() {

        ob_start();
            $this->stmt->debugDumpParams();
            $sqlcomand = ob_get_contents();
        ob_end_clean();

        $file = fopen('sqlcomand.txt', 'w') or die('Unable to open file!');
        fwrite($file, $sqlcomand . "\n" . json_encode($this->stmt->errorInfo()) );
        fclose($file);

        return $this;
    }

    function build($returnData = false) {
        if($this->error === null) {
            if($returnData === true) {
                return $this->stmt->fetchAll( PDO::FETCH_ASSOC );
            }
            return $this->stmt;
        }
        return $this->error;

    }

    function transformValue( $tpys, $value ) {

        $transformed = array(
            "normal" => function($value){ return $value; },
            "int" => function($value){ return $value; },
            "upper"  => function($value){ return mb_strtoupper($value); },
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
