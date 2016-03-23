# How to use `SearchBehavior` Full Text Search for CakePHP3.x

Builds a FullText query, sortable by relevance and giving you the possibility to provide weights for different columns.

1. Create necessary indexes. Any column that will be given a weight on requires its own index.

```
CREATE FULLTEXT INDEX idx_ft_some_column ON Exemple(`some_column`);
```
or, using CakePHP's migration system,

```
$table->addIndex($column, [
		'name' => 'idx_ft_'.$column,
		'type' => 'fulltext'])
	->update();
```


2. Add to the entity's table `Model\Table\ExempleTable.php`:

```
$this->addBehavior('Search', [	    
	'weights' => [
		// Required. Field name & field weight, any int will do
		'exemple_field' => 50, 
	],
	'contain' => [
		// Optional. Names of other tables to get with it. Same thing as $query->contain()
		'ExempleRelatedTable', 		    
	],
	'select'  => [
		// Optional. Columns to return. Same as $query->select()
    	'Exemple.some_field'		    
    ],
]);
```
3. Create the query using `$this->ExempleModel->textSearch($query, $order|null)` (possibly assign it to a variable)
4. Run it using `execute()`, `$this->paginate($query)`, etc. Just like any other QueryBuilder -designed query.