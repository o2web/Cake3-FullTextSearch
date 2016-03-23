<?php

namespace App\Model\Behavior;
use Cake\ORM\Behavior;

class SearchBehavior extends Behavior {

	const
		MODE_BOOLEAN = "BOOLEAN",
		MODE_NATURAL_LANGUAGE = "NATURAL LANGUAGE",
		MODE_DEFAULT = "DEFAULT";

	private
		$weights  = [],
		$contain  = [],
		$select   = [],
		$mode     = self::MODE_BOOLEAN,
		$tableName;

	/**
	 * @param array $config, where:
	 *  [
	 *      'weights' => [<column name> => <column weight, from 0 to 100>],
	 *      'contain' => [<related tables columns]
	 *  ]
	 */
	public function initialize(array $config)
	{
		$this->weights = $this->maybeGetConfig($config, 'weights');
		$this->contain = $this->maybeGetConfig($config, 'contain');
		$this->select  = $this->maybeGetConfig($config, 'select');
		$this->tableName = $this->_table->table();
	}

	public function textSearch($searchString, $order = [])
	{
		if (is_string($searchString) && !empty($searchString))
		{
			$select = $this->searchQuery();
			$order = $this->orderingQuery($order);
			$where = $this->whereRelevance();

			// _table fonctionne, à confirmer si c'est la bonne façon...
			$query = $this->_table  ->find      ()
						->select    ( $select )
						->autoFields( true )
			                        ->contain   ( $this->contain )
			                        ->where     ( $where )
			                        ->order     ( $order, false )
			                        ->bind      ( ':searchQuery', $this->stemSearchQuery($searchString) );

			return $query;
		} else {
			return false;
		}

	}

	private function whereRelevance()
	{
		return ["\n\tMATCH({$this->tableName}.".implode(",{$this->tableName}.", array_keys($this->weights)).") AGAINST(:searchQuery {$this->appendMode()})"];
	}

	private function selectEachRelevance($colName, $colWeight)
	{
		return !empty($colName) ? ["{$colName}Relevance" => "\n\tMATCH({$this->tableName}.{$colName}) AGAINST(:searchQuery {$this->appendMode()})"] : null;
	}

	private function selectCombinedRelevance($colName, $colWeight)
	{
		return !empty($colName) ? "\t\n\t(SELECT {$colName}Relevance * {$colWeight})" : null;
	}

	private function searchQuery()
	{
		// Calculated fields to sort by weight
		$calculated = array_map([$this, 'selectEachRelevance'], array_keys($this->weights), array_values($this->weights));

		return array_merge(
			// Fields to return, specified in the table definition
			$this->select,
			$this->flattenArray($calculated),
			['CombinedRelevance' => "\t\n(".implode("+", array_map([$this, 'selectCombinedRelevance'], array_keys($this->weights), array_values($this->weights)))." \t\n)"]
		);
	}

	private function maybeGetConfig($config, $key)
	{
		if (is_array($config) && array_key_exists($key, $config)){
			return $config[$key];
		} elseif(property_exists($this, $key)) {
			return $this->{$key};
		} else {
			return null;
		}
	}

	private function orderingQuery($order)
	{
		$alreadyThere = array_filter($order, function($el) {
			return ( strpos($el, 'relevance') !== false );
		}, ARRAY_FILTER_USE_KEY);

		return array_merge(array_diff_key($order, $alreadyThere), ["CombinedRelevance" => 'DESC']);
	}

	/**
	 * TODO: ensure full support for both associative & sequencial arrays
	 * TODO: check if there's already something in Cake that does it / would make it simpler; or if it's appropriate to move to utility class or something
	 * @param $array
	 * @return array|mixed
	 */
	private function flattenArray($array)
	{
		if (is_array($array))
		{
			return array_reduce($array, function ($arr, $a) {
				$k = array_keys($a)[0];
				$v = array_values($a)[0];

				if (array_keys($arr) !== range(0, count($arr) - 1)) { // Associative
					$arr[$k] = $v;
				} else { // Numeric & sequencial
					$arr[] = $v;
				}

				return $arr;
			}, []);
		} else {
			return [$array];
		}
	}

	private function appendMode()
	{
		switch ($this->mode)
		{
			case self::MODE_NATURAL_LANGUAGE:
				return "IN ".self::MODE_NATURAL_LANGUAGE." MODE";
				break;
			case self::MODE_BOOLEAN:
				return "IN ".self::MODE_BOOLEAN." MODE";
				break;
			default:
				return "";
		}
	}

	private function stemSearchQuery($searchQuery)
	{
		//TODO: sanitize?

		if ($this->mode == self::MODE_BOOLEAN)
		{
			$queryArray = explode(" ", $searchQuery);
			$searchQuery = implode("* ", $queryArray);
		}

		return $searchQuery."*";
	}
}
