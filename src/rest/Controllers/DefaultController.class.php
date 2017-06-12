<?php namespace Rest\Controllers;

/**
 * Created by PhpStorm.
 * User: rjgun
 * Date: 10/04/2016
 * Time: 19:24
 */
use Jacwright\RestServer\RestException;
use Rest\Classes\Database;


class DefaultController {
	protected $db;
	protected $user;
	protected $type;
	protected $admin;
	protected $logger;

	public function __construct() {
		global $logger;
		$this->db     = new Database();
		$this->logger = $logger;
		$logger->info( sprintf( "%s Initialised", get_called_class() ) );
	}

	/**
	 * Checks whether user is authorised
	 */
	public function authorize() {
		$this->db   = new Database();
		$this->user = ( 'rjg70' );//$_SERVER['REMOTE_USER']);
		$this->db->query( 'SELECT * FROM users WHERE crsid = :id' );
		$this->db->bind( ':id', $this->user );
		$row = $this->db->single();

		//print_r($row);
		if ( $row ) {
			if ( $row["authorised"] ) {
				$this->admin = $row["admin"];
				$this->type  = $row["type"];

				return true;
			}
		}

		return false;
	}

	/**
	 * Gets the user by id or current user
	 *
	 * @url GET /
	 *
	 * @return
	 */
	public function getData() {
		$id = $this->user;
		$this->db->query( 'SELECT * FROM users WHERE crsid = :id' );
		$this->db->bind( ':id', $id );
		$row = $this->db->single();

		if ( ! $row["name"] ) {
			$ds      = ldap_connect( "ldap.lookup.cam.ac.uk" );
			$lsearch = ldap_search( $ds, "ou=people,o=University of Cambridge,dc=cam,dc=ac,dc=uk", "uid=" . $id . "" );
			$info    = ldap_get_entries( $ds, $lsearch );
			$name    = $info[0]["cn"][0];
			$this->db->query( 'UPDATE users SET name=:name WHERE crsid=:id' );
			$this->db->bind( ':id', $id );
			$this->db->bind( ':name', $name );
			$this->db->execute();
			$row["name"] = $name;
		}

		return $row;
	}

	/**
	 * Throws an error
	 *
	 * @url GET /error
	 */
	public function throwError() {
		throw new RestException( 401, "Empty password not allowed" );
	}
}
