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
            "int"           => PDO::PARAM_INT,
            "date"          => PDO::PARAM_STR
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
                
                $parameters  = defined("PARAMETERS") ? constant("PARAMETERS") : array();
                $queryParams = $parameters["params"] ?? array();
                $action      = $parameters["action"] ?? "INDEFINIDO";
                $router      = $parameters["router"] ?? "INDEFINIDO";

                $regex = '/Sent SQL:(?<SQL>.*)(?=Params)/ms';
                preg_match($regex, $command, $matches);
                
                $sqlDesc = str_replace(["\r", "\n", "\t"], '', ($matches["SQL"] ?? "INDEFINIDO") );
                $sqlDesc = preg_replace('/\s+/', ' ', $sqlDesc);
                $sqlDesc = trim($sqlDesc);
                $sqlDesc = preg_replace('/^\[\d+\]\s*/', '', $sqlDesc);

                if($type === "db") 
                    {
                        $dbParams = array(
                            ":LOG_DESCRICAO"    => $sqlDesc,
                            ":LOG_DATAHORA"     => date("Y-m-d H:i:s"),
                            ":LOG_PARAMETROS"   => json_encode($queryParams, JSON_PRETTY_PRINT),
                            ":LOG_ERRORS"       => json_encode($errors),
                            ":LOG_INFOADICIONAL"=> $infoAdicional,
                            ":USU_NOME"         => $this->username
                        );
                        $stmt   = $this->conn->prepare( "INSERT INTO log ( log_descricao, log_datahora, log_parametros, log_errors, log_infoadicional, usu_nome ) values ( :LOG_DESCRICAO, :LOG_DATAHORA, :LOG_PARAMETROS, :LOG_ERRORS, :LOG_INFOADICIONAL, :USU_NOME )" );
                        $stmt->execute($dbParams);
                        $stmt->rowCount() === 0 ? throw new \Exception("Erro ao registrar log de $this->username.") : "";
                    }
                else 
                    {
                        $filePayload = array(
                            "log_action"       => $action,
                            "log_router"       => $router,
                            "log_datahora"     => date("Y-m-d H:i:s"),
                            "log_descricao"    => $sqlDesc,
                            "log_parametros"   => $queryParams,
                            "log_errors"       => $errors,
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

                        $resultWrite = file_put_contents($path, json_encode($filePayload) . PHP_EOL, FILE_APPEND | LOCK_EX);
                        if($resultWrite === false) {
                            error_log("Nao foi possivel gravar no arquivo: " . $path);
                            return;
                        }
                    }

            }

            catch(\Throwable $e) {
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
            "date"      => function($value){ return $value === "" ? null : $value; },    
            "normal"    => function($value){ return $value; },
            "int"       => function($value){ return $value; },
            "upper"     => function($value){ return mb_strtoupper($value); },
        );
        return $transformed[ $tpys ]($value);
     }


     function setParam($value) : void {

        $transform  =  $this->transformValue( $value["type"], $value["value"] );
        $tpys       = $transform === null ? PDO::PARAM_NULL : $this->types[ $value["type"] ];
        $this->stmt->bindParam(
            $value["key"],
            $transform,
            $tpys
        );


     }

}


?>
