# Full Text Search for CakePHP3.x
Builds a FullText query, sortable by relevance and giving you the possibility to provide weights for different columns.

## Requirements
1. CakePHP >= 3.0
1. PHP >= 5.4
1. MySQL >= 5.0 if using MyIsam tables, or >= 5.6 if using it on InnoDB.

## Setup


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

## Usage

1. Create the query using `$this->ExempleModel->textSearch($query, $order|null)` 
2. Run it using `execute()`, `$this->paginate($query)`, etc. Just like any other QueryBuilder -designed query.

## Roadmap

1. Add defaults so that if the array is a list of fields with no weight specified, it treats all equally without generating the more complex query
1. Add tests
1. Better usage of boolean, natural language, etc modes
1. ...

### See also

This behavior was done to answer our requirements, specifically adding field weights, while we left other aspects simpler. You might want to also have a look at [@calin's Searchable-Behavior-for-CakePHP](https://code.google.com/archive/p/searchable-behaviour-for-cakephp/) / [@connrs's fork](https://github.com/connrs/Searchable-Behaviour-for-CakePHP) and see which one is more fitting - or even combine both!