# FluentSQL
API para gerenciamento de conexoes e operações com o banco mySQL, usando design patters FluentAPI!<br/>
> [!NOTE]
> É necessário configurar para gravação de _[logs](#logs)_
## Instalação
```
composer require rafaelssucupira/fluent-sql
```
## Logs
o pacote registra os logs em um db particular. Para que a `função sqlCommand` funcione é necessário criar um database para o armazenamento dos logs
```
CREATE TABLE `log` (
  `log_codigo` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `log_descricao` text NOT NULL,
  `log_parametros` text,
  `log_errors` text,
  `log_datahora` datetime DEFAULT NULL,
  `usu_nome` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`log_codigo`)
) ENGINE=MyISAM AUTO_INCREMENT=43335 DEFAULT CHARSET=utf8 COMMENT='Logs';
```
## Exemplo
```
<?php
require_once ("vendor/autoload.php");
use FluentSQL\SQL;

$conn = new SQL( "localhost", "db", "user", "passwd", "username" ?? null );

$result = $conn
            ->prepareQuery( "SELECT * FROM users" )
            ->sqlCommand()
            ->execQuery()
            ->build(true);

echo json_encode($result)    ;

?>
```

