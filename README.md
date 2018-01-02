# MicroSql

Es un gestor de base de datos que utiliza PDO para gestionar consultas SQL, este entrega una interfaz simple y predesible que aprobecha PDO sea en seguridad o universalidad frente a SQL con la finalidad de generar consultas escalables, mantenibles y predesibles.

## Ejemplo Basico

```PHP
const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'test';
const DB_PREFIX = 'prefix_'; 
const DB_TYPE_DB = 'mysql';

$db = new \MicroSql\Connect(
   DB_HOST,
   DB_USER,
   DB_PASS,
   DB_NAME,
   DB_PREFIX, // opcional
   DB_TYPE_DB // opcional
);
/**
* Seleciona toda las filas de la tabla 'prefix_mi_tabla';
*/
$db->mi_tabla->select();
/**
* inserta una nueva fila a la tabla 'prefix_mi_tabla'
* el nuevo valor field:valor
*/
$db->mi_tabla->insert([
  'field'=>'valor'
]);
/**
* actualiza todas las filas de la tabla 'prefix_mi_tabla'
* por el nuevo valor field:nuevo valor
*/
$db->mi_tabla->update([
  'field'=>'nuevo valor'
]);
/**
* elimina todas las filas de la tabla 'prefix_mi_tabla'
* que posean el campo igual a field:nuevo valor
*/
$db->mi_tabla->delete([
  'field'=>'nuevo valor'
]);

```



### Select

Select permite generar la consulta de selecion, ud podra usar una serie de propiedades para complejizar su consulta SQL

### select@and

```php
// SELECT * FROM mi_tabla AS mi_tabla  WHERE ID = :ID AND USER = :USER
$db->mi_tabla->select([
	'ID'  => 'valor 1',
  	'USER'  => 'valor 2'
]);

// SELECT * FROM mi_tabla AS mi_tabla WHERE id = :and_id
$db->mi_tabla->select([
  '@and'=>[
     'id'=>1
  ]
]);

// SELECT ID FROM mi_tabla AS mi_tabla  WHERE ID = :ID AND USER = :USER
$db->mi_tabla->select([
    'ID'  => 'valor 1',
    'USER'  => 'valor 2'
],['ID']);

```

### select@or

```php
// SELECT * FROM mi_tabla AS mi_tabla  WHERE ( ID = :or_0_ID ) OR ( ID = :or_1_ID )
$db->mi_tabla->select([
    '@or' => [
        [
            'ID'  => 1,
        ],
        [ 
            'ID'  => 2
        ]
    ]
]);
```

### select@limit

```php
// SELECT * FROM mi_tabla AS mi_tabla LIMIT 10
$db->mi_tabla->select([
	'@limit'=>10
]);
```

### select@join

```php
// SELECT * FROM mi_tabla AS mi_tabla INNER JOIN mi_otra_tabla AS mi_otra_tabla ON mi_otra_tabla.ID = mi_tabla.ID
$db->mi_tabla->select([
  '@join'=>[
    'mi_otra_tabla'=>['mi_otra_tabla.ID'=>'mi_tabla.ID']
  ]
]);
```

### select@between

```php
// SELECT * FROM mi_tabla AS mi_tabla  WHERE id BETWEEN :between_id_0 AND :between_id_1
$db->mi_tabla->select([
  '@between'=>[
    'id'=>[1,10]
  ]
]);
```

### select@in

```php
// SELECT * FROM mi_tabla AS mi_tabla  WHERE id IN (:in_id_0, :in_id_1)
$db->mi_tabla->select([
  '@in'=>[
    'id'=>[1,10]
  ]
]);
```
### select@like

```php
// SELECT * FROM mi_tabla AS mi_tabla  WHERE id LIKE :like_id
$db->mi_tabla->select([
  '@like'=>[
    'id'=>'1'
  ]
]);
```


### select@not

```php
// SELECT * FROM mi_tabla AS mi_tabla  WHERE NOT ( id = :not_0_and_id  ) 
$db->mi_tabla->select([
  '@not'=>[
    [
      '@and'=>[
          'id'=>'1'
      ]
    ]
  ]
]);
```




