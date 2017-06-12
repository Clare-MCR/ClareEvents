<?php namespace Rest\Controllers;

/**
 * Created by PhpStorm.
 * User: rjgun
 * Date: 10/04/2016
 * Time: 19:24
 */
use Jacwright\RestServer\RestException;
use PDOException;
use Rest\Functions;

class UsersController extends DefaultController
{

	/**
	 * Gets the user by id or current user
	 *
	 * @url GET /
	 * @url GET /$id
	 *
	 * @param string $id
	 *
	 * @return
	 */
	public function getData( $id = null ) {
		if ( ( ! $this->admin && $id ) || ! $id ) {
			$id = $this->user;
		}

		if ( $id == '*' ) {
			$this->db->query( 'SELECT * FROM users' );
			$row = $this->db->resultset();

			return $row;
		} else {
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
	}

    /**
     * update the user
     *
     * @url PUT /
     * @url PUT /$id
     * @param string $id
     * @param array $data set (name) or (admin and/or authorised and/or bill)
     * @throws RestException
     */
    public function updateUser($id = null, $data)
    {
        if (!($this->admin && $id)) {
            $id = $this->user;
        }

        if ($this->admin && $id != $this->user) {
            // Updating permissions
            $this->db->beginTransaction();
            $error = false;
            if (!$error && isset($data->admin)) {
                $this->db->query('UPDATE users SET admin=:admin  WHERE crsid=:id;' );
	            $this->db->bind( ':id', $id );
	            $this->db->bind( ':admin', Functions\test_input( $data->admin));
                try {
                    $this->db->execute();
                } catch (PDOException $e) {
                    $error = true;
                }
            }
            if (!$error && isset($data->authorised)) {
                $this->db->query('UPDATE users SET authorised=:authorised  WHERE crsid=:id;' );
	            $this->db->bind( ':id', $id );
	            $this->db->bind( ':authorised', Functions\test_input( $data->authorised));
                try {
                    $this->db->execute();
                } catch (PDOException $e) {
                    $error = true;
                }
            }
            if (!$error && isset($data->bill)) {
                $this->db->query('UPDATE users SET bill=:bill  WHERE crsid=:id;' );
	            $this->db->bind( ':id', $id );
	            $this->db->bind( ':bill', Functions\test_input( $data->bill));
                try {
                    $this->db->execute();
                } catch (PDOException $e) {
                    $error = true;
                }
            }
            if ($error) {
                $this->db->cancelTransaction();
                throw new RestException(409, 'Error inserting');
            } else {
                $this->db->endTransaction();
            }


        } else {
	        // Updating Name
            if (isset($data->name)) {
                $this->db->query('UPDATE users SET name=:name  WHERE crsid=:id;' );
	            $this->db->bind( ':id', $id );
	            $this->db->bind( ':name', Functions\test_input( $data->name ) );
	            $this->db->execute();
            } else {
                throw new RestException(204, 'No Content');
            }
        }

        $this->db->query('SELECT * FROM users WHERE crsid = :id');
        $this->db->bind(':id', $id);
        $row = $this->db->single();

        return $row;
    }

    /**
     * Adds the list of users
     *
     * @url POST /
     * @param $data
     * @throws RestException
     */
    public function addUsers($data)
    {
        if (!$this->admin ) {
	        throw new RestException( 404, 'User Must be admin to modify Events Users');
        }

        if (!isset($data->type)) {
            throw new RestException(404, 'Type required');
        }
        if (!isset($data->users) || !is_array($data->users)) {
            throw new RestException(404, 'Users array required');
        }
        $error = false;
        $this->db->beginTransaction();
        foreach ($data->users as $user) {
            $user = Functions\test_input($user);
            $this->db->query('INSERT INTO users (crsid, type) VALUES (:crsid, :type)');
            $this->db->bind(':crsid', $user);
            $this->db->bind(':type', Functions\test_input($data->type));
            try {
                $this->db->execute();
            } catch (PDOException $e) {
                // If something went wrong then conflicting crsid Try updating the user
                try {
                    $this->db->query('UPDATE users SET  type=:type  WHERE crsid=:crsid;');
                    $this->db->bind(':crsid', $user);
                    $this->db->bind(':type', Functions\test_input($data->type));
                    $this->db->execute();
                } catch (PDOException $e) {
                    $error = true;
                    break;
                }
            }
        }
        if ($error) {
            $this->db->cancelTransaction();
            throw new RestException(409, 'Error inserting');
        } else {
            $this->db->endTransaction();
        }

        return;
    }

    /**
     * Delete the user by id or current user
     *
     * @url DELETE /$id
     * @url DELETE /type/$type
     * @param string $id
     * @param string $type
     * @throws RestException
     */
    public function deleteUser($id = '*', $type = null)
    {
        $error = false;
        if (!$this->admin) {
            throw new RestException(404, 'User Must be admin to modify Users');
        }

        if (!isset($id)) {
            throw new RestException(204, 'No Content');
        }

        if ($id === '*' && isset($type)) {
            $this->db->query('DELETE FROM users WHERE type=:type AND admin=0');
            $this->db->bind('type', Functions\test_input($type));
            $this->db->execute();
        } elseif ($id === '*' && !isset($type)) {
            $this->db->query('DELETE FROM users WHERE admin=0');
            $this->db->execute();
        } else {
            $this->db->query('DELETE FROM users WHERE crsid=:crsid');
            $this->db->bind(':crsid', $id);
            try {
                $this->db->execute();
            } catch (PDOException $e) {
                $error = true;
            }
        }
        if ($error) {
            throw new RestException(404, 'Not Found');
        }
        return;
    }

}
