# FluentSQL


### API para gerenciamento de conexoes e operações com o banco mySQL


### INSTALAÇÃO

```
composer require rafaelssucupira/fluent-sql
```

### EXEMPLO

```
<?php
require_once ("vendor/autoload.php");
use FluentSQL\SQL;

$conn = new SQL( "localhost", "db", "user", "passwd" );

$result = $conn
            ->prepareQuery( "SELECT * FROM users" )
            ->sqlCommand()
            ->execQuery()
            ->build(true);

echo json_encode($result)    ;

?>
```

