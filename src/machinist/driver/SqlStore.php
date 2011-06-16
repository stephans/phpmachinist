<?php
namespace machinist\driver;

/**
 * Should provide *most* for the vendor agnostic functionality
 * for dealing with an SQL based store.
 */
abstract class SqlStore implements Store {
	protected $pdo;

	public function __construct(\PDO $pdo) {
		$this->pdo = $pdo;
	}

	public function insert($table, $data) {
		$query = 'INSERT INTO '.$table.' ('.join(',', array_keys($data)).') VALUES('.trim(str_repeat('?,', count($data)),',').')';
		$stmt = $this->pdo()->prepare($query);
		$stmt->execute(array_values($data));
		return $this->pdo->lastInsertId();
	}

	public function find($table, $key) {
		$primary_key = $this->primaryKey($table);
		$query = $this->pdo()->prepare('SELECT * from '.$table.' WHERE '.$primary_key.' = ?');
		$query->execute(array($key));
		return $query->fetch(\PDO::FETCH_OBJ);
	}

	/**
	 * Wipe all data in the data store for the provided table
	 * @param string $table Name of table to remove all data
	 * @param bool $truncate Will use truncate to delete data from table when set
	 * to true
	 */
	public function wipe($table, $truncate) {
		if ($truncate) {
			$query = 'TRUNCATE TABLE '.$table;
		} else {
			$query = 'DELETE FROM '.$table;
		}
		return $this->pdo->exec($query);
	}

	/**
	 * Method which should return a PDO connection for me to like do things with
	 * @return \PDO
	 */
	protected function pdo() {
		return $this->pdo;
	}

	/**
	 * Finds the correct SQLStore implementation based on a PDO connection.
	 * @static
	 * @throws \InvalidArgumentException
	 * @param \PDO $pdo
	 * @return \machinist\driver\Store
	 */
	public static function fromPdo(\PDO $pdo) {
		$driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
		switch ($driver) {
			case 'sqlite':
				require_once(__DIR__.DIRECTORY_SEPARATOR.'Sqlite.php');
				return new \machinist\driver\Sqlite($pdo);
			case 'mysql':
				require_once(__DIR__.DIRECTORY_SEPARATOR.'Mysql.php');
				return new \machinist\driver\Mysql($pdo);
			default:
				throw new \InvalidArgumentException("Unsupported PDO drive {$driver}.");
		}
	}

}
