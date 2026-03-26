<?php
namespace FluentSQL;
use PDO;
use PDOException;

class SQL {

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
    )  {
        
        date_default_timezone_set( 'America/Sao_Paulo' );
        $this->conn = new PDO( "mysql:host=$host;dbname=$db;charset=utf8", $user, $passwd );
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



    function registerFile($infoAdicional = "") {
        ob_start();
            $this->stmt->debugDumpParams();
            $command = ob_get_contents();
        ob_end_clean();

        $errors = $this->stmt->errorInfo();

        $this->saveCommand( "file", $command, $errors, $infoAdicional );

        return $this;
    }

    function registerDb($infoAdicional = "") {

        ob_start();
            $this->stmt->debugDumpParams();
            $command = ob_get_contents();
        ob_end_clean();

        $errors = $this->stmt->errorInfo();

        $this->saveCommand( "db", $command, $errors, $infoAdicional );

        return $this;
    }

    function saveCommand($type = "db", $command, $errors, $infoAdicional ) :void
        {
            try {
                $paramsDecode  = defined("PARAMETERS") ? constant("PARAMETERS")["params"] : $this->params;
                
                $regex = '/Sent SQL:(?<SQL>.*)(?=Params)/ms';
                preg_match($regex, $command, $matches);
                if($type === "db") 
                    {
                        $params = array(
                            ":LOG_DESCRICAO"    => $matches["SQL"] ?? "INDEFINIDO",
                            ":LOG_DATAHORA"     => date("Y-m-d H:i:s"),
                            ":LOG_PARAMETROS"   => json_encode($paramsDecode, JSON_PRETTY_PRINT),
                            ":LOG_ERRORS"       => json_encode($errors),
                            ":LOG_INFOADICIONAL"=> $infoAdicional,
                            ":USU_NOME"         => $this->username
                        );
                        $stmt   = $this->conn->prepare( "INSERT INTO log ( log_descricao, log_datahora, log_parametros, log_errors, log_infoadicional, usu_nome ) values ( :LOG_DESCRICAO, :LOG_DATAHORA, :LOG_PARAMETROS, :LOG_ERRORS, :LOG_INFOADICIONAL, :USU_NOME )" );
                        $stmt->execute($params);
                        $stmt->rowCount() === 0 ? throw new \Exception("Erro ao registrar log de $this->username.") : "";
                    }
                else 
                    {
                        $params = array(
                            "log_descricao"    => $matches["SQL"] ?? "INDEFINIDO",
                            "log_datahora"     => date("Y-m-d H:i:s"),
                            "log_parametros"   => $paramsDecode,
                            "log_errors"       => $errors,
                            "log_infoadicional"=> $infoAdicional,
                            "usu_nome"         => $this->username
                        );
                        $dir      = "./logs" . DIRECTORY_SEPARATOR . "daily";
                        $filename = date("dmY").".txt";
                        $path     = $dir . DIRECTORY_SEPARATOR . $filename;
                        if(is_dir($dir) === false) {
                            if(mkdir($dir, 0775, true) === false) {
                                error_log("Não foi possivel gerar o path do arquivo.");
                                return;
                            }
                        }

                        $resultWrite = file_put_contents($path, json_encode($params) . PHP_EOL, FILE_APPEND | LOCK_EX);
                        if($resultWrite === false) {
                            error_log("Nao foi possivel gravar no arquivo: " . $path);
                            return;
                        }
                    }


            }

            catch(\Exception $e) {
                error_log( $e->getMessage() );
            }
                
        }

    function sqlCommand($infoAdicional = "") { // para manter compatibilidade com versoes antigas
        $this->registerDb($infoAdicional);
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
