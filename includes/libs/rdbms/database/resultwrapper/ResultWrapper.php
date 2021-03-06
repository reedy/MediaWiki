<?php

namespace Wikimedia\Rdbms;

use stdClass;
use RuntimeException;

/**
 * Result wrapper for grabbing data queried from an IDatabase object
 *
 * Note that using the Iterator methods in combination with the non-Iterator
 * DB result iteration functions may cause rows to be skipped or repeated.
 *
 * By default, this will use the iteration methods of the IDatabase handle if provided.
 * Subclasses can override methods to make it solely work on the result resource instead.
 * If no database is provided, and the subclass does not override the DB iteration methods,
 * then a RuntimeException will be thrown when iteration is attempted.
 *
 * The result resource field should not be accessed from non-Database related classes.
 * It is database class specific and is stored here to associate iterators with queries.
 *
 * @ingroup Database
 */
class ResultWrapper implements IResultWrapper {
	/** @var resource|array|null Optional underlying result handle for subclass usage */
	public $result;

	/** @var IDatabase|null */
	protected $db;

	/** @var int */
	protected $pos = 0;
	/** @var stdClass|bool|null */
	protected $currentRow;

	/**
	 * Create a row iterator from a result resource and an optional Database object
	 *
	 * Only Database-related classes should construct ResultWrapper. Other code may
	 * use the FakeResultWrapper subclass for convenience or compatibility shims, however.
	 *
	 * @param IDatabase|null $db Optional database handle
	 * @param ResultWrapper|array|resource $result Optional underlying result handle
	 */
	public function __construct( IDatabase $db = null, $result ) {
		$this->db = $db;
		if ( $result instanceof ResultWrapper ) {
			$this->result = $result->result;
		} else {
			$this->result = $result;
		}
	}

	public function numRows() {
		return $this->getDB()->numRows( $this );
	}

	public function fetchObject() {
		return $this->getDB()->fetchObject( $this );
	}

	public function fetchRow() {
		return $this->getDB()->fetchRow( $this );
	}

	public function seek( $pos ) {
		$this->getDB()->dataSeek( $this, $pos );
		$this->pos = $pos;
	}

	public function free() {
		$this->db = null;
		$this->result = null;
	}

	function rewind() {
		if ( $this->numRows() ) {
			$this->getDB()->dataSeek( $this, 0 );
		}
		$this->pos = 0;
		$this->currentRow = null;
	}

	function current() {
		if ( $this->currentRow === null ) {
			$this->currentRow = $this->fetchObject();
		}

		return $this->currentRow;
	}

	function key() {
		return $this->pos;
	}

	function next() {
		$this->pos++;
		$this->currentRow = $this->fetchObject();

		return $this->currentRow;
	}

	function valid() {
		return $this->current() !== false;
	}

	/**
	 * @return IDatabase
	 * @throws RuntimeException
	 */
	private function getDB() {
		if ( !$this->db ) {
			throw new RuntimeException( static::class . ' needs a DB handle for iteration.' );
		}

		return $this->db;
	}
}

/**
 * @deprecated since 1.29
 */
class_alias( ResultWrapper::class, 'ResultWrapper' );
