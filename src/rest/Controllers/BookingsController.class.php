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


class BookingsController extends DefaultController
{

	/**
	 * Gets the user by id or current user
	 *
	 * @url GET /
	 * @url GET /$eventid
	 *
	 * @param string $eventid
	 *
	 * @return
	 */
	public function getData( $eventid = null ) {
		if ( ! isset( $eventid ) ) {
			throw new RestException( 204, 'No Content' );
		}
		$this->db->query( 'SELECT * FROM bookings WHERE eventid=:eventid ORDER BY id ASC' );
		$this->db->bind( ':eventid', $eventid );
		try {
			$row = $this->db->resultset();
		} catch ( PDOException $e ) {
			throw new RestException( 404, 'Not Found' );
		}

		return $row;
	}

    /**
     * Adds booking
     *
     * @url POST /$id
     */
    public function addBooking($id,$data)
    {
    if(isset($data->eventid)){$id=Functions\test_input($data->eventid);}

        if (!isset($id)) {
            throw new RestException(404, 'eventid required');
        }

        if (!isset($data->bookings) || !is_array($data->bookings)) {
            throw new RestException(404, 'Bookings array required');
        }

        $this->db->beginTransaction();
        $error=false;
        $n_bookings=0;
        $event=array();
        //define who made the cut
        if(!$error)
        {
            $this->db->query('SELECT * FROM eventsList WHERE id=:id');
            $this->db->bind(':id',$id);
            try {
                $event = $this->db->single();
            } catch (PDOException $e) {
                $error=true;
                print("error getting event");
            }
        }
        if(!$error)
        {
            $this->db->query('SELECT * FROM bookings WHERE eventid=:id AND booker=:crsid AND admin=0');
            $this->db->bind(':id',$id);
            $this->db->bind(':crsid',$this->user);
            try {
                $n_bookings = $this->db->rowCount();
            } catch (PDOException $e) {
                $error=true;
                print("error getting event");
            }
        }

        if(!$error && $event['closeDate']<gmdate('Y-m-d H:i:s'))
        {
            //eventClosed
            throw new RestException(404, 'Event Closed, Cannot make booking');
        }
        if(!$error && $event['openDate']>gmdate('Y-m-d H:i:s') && (!$this->admin ||  ($this->admin && !$data->admin)))
        {
            // Open Date is in future and not admin or admin, but not admin booking
            throw new RestException(404, 'Event Not Yet Open, Cannot make booking');
        }

        foreach ($data->bookings as $booking)
        {
            if(!$error)
            {
                $this->db->query('INSERT INTO bookings (eventid,booker,admin,type,name,diet,other,extra) 
                                  VALUES (:eid, :crsid, :admin, :type, :name, :diet, :other, :extra)');
                $this->db->bind(':eid', $id);
                $this->db->bind(':crsid', $this->user); //TODO: allow admin to book for others?
                if(($this->admin && !$data->admin))
                {
                    $this->db->bind(':admin', 1);
                    $this->db->bind(':type',Functions\test_input($data->type));
                }
                else
                {
                    $this->db->bind(':admin', 0);
                    if($n_bookings==0)
                    {
                        $this->db->bind(':type',1);
                    }
                    else if($n_bookings>$event['maxGuests'])
                    {
                        //Too Many bookings
                        $error=true;
                        break;
                    }
                    else
                    {
                        $this->db->bind(':type',0);
                    }
                    $n_bookings++;
                }

	            $this->db->bind( ':name', Functions\test_input( $booking->name ) );
	            $this->db->bind( ':diet', Functions\test_input( $booking->diet ) );
	            $this->db->bind( ':other', Functions\test_input( $booking->other ) );
	            $this->db->bind( ':extra', Functions\test_input( $booking->extra));
                try {
                    $this->db->execute();
                } catch (PDOException $e) {
                    $error=true;
                    print("error making booking");
                }
            }
        }

        if ($error) {
            $this->db->cancelTransaction();
            throw new RestException(409, 'Transaction Error');
        } else {
            $this->db->endTransaction();
        }

        return;
    }


    /**
     * update the booking
     *
     * @url PUT /
     * @url PUT /$id
     * @param string $id
     * @param array $data set (name and/or diet and/or other and/or extra)
     * @throws RestException
     */
    public function updateBooking($id = null, $data ) {
	    if ( ! isset( $id ) ) {
		    throw new RestException( 404, 'bookingid required' );
	    }

	    $error = false;
	    $this->db->beginTransaction();
	    if ( ! $error && isset( $data->name ) ) {
		    $this->db->query( 'UPDATE bookings SET name=:name  WHERE id=:id AND booker=:crsid' );
		    $this->db->bind( ':id', $id );
		    $this->db->bind( ':crsid', $this->user );
		    $this->db->bind( ':name', Functions\test_input( $data->name ) );
		    try {
			    $this->db->execute();
		    } catch ( PDOException $e ) {
			    $error = true;
		    }
	    }
	    if ( ! $error && isset( $data->diet ) ) {
		    $this->db->query( 'UPDATE bookings SET diet=:diet  WHERE id=:id AND booker=:crsid' );
		    $this->db->bind( ':id', $id );
		    $this->db->bind( ':crsid', $this->user );
		    $this->db->bind( ':diet', Functions\test_input( $data->diet ) );
		    try {
			    $this->db->execute();
		    } catch ( PDOException $e ) {
			    $error = true;
		    }
	    }
	    if ( ! $error && isset( $data->other ) ) {
		    $this->db->query( 'UPDATE bookings SET other=:other  WHERE id=:id AND booker=:crsid' );
		    $this->db->bind( ':id', $id );
		    $this->db->bind( ':crsid', $this->user );
		    $this->db->bind( ':other', Functions\test_input( $data->other ) );
		    try {
			    $this->db->execute();
		    } catch ( PDOException $e ) {
			    $error = true;
		    }
	    }
	    if ( ! $error && isset( $data->extra ) ) {
		    $this->db->query( 'UPDATE bookings SET extra=:extra  WHERE id=:id AND booker=:crsid' );
		    $this->db->bind( ':id', $id );
		    $this->db->bind( ':crsid', $this->user );
		    $this->db->bind( ':extra', Functions\test_input( $data->extra ) );
		    try {
			    $this->db->execute();
		    } catch ( PDOException $e ) {
			    $error = true;
		    }
	    }
	    if ( $error ) {
		    $this->db->cancelTransaction();
		    throw new RestException( 409, 'Error inserting' );
	    } else {
		    $this->db->endTransaction();
	    }

	    $this->db->query( 'SELECT * FROM bookings WHERE id = :id');
        $this->db->bind(':id', $id);
        $row = $this->db->single();
        return $row;
    }



    /**
     * Delete the user by id or current user
     *
     * @url DELETE /$id
     */
    public function deleteBooking($id = '*', $type = null)
    {
        if (!isset($id)) {
            throw new RestException(204, 'No Content');
        }

        $error = false;
	    $this->db->query( 'DELETE FROM bookings WHERE booker=:crsid AND id=:id' );
	    $this->db->bind( ':crsid', $this->user );
	    $this->db->bind( ':id', $id);
        try {
            $this->db->execute();
        } catch (PDOException $e ) {
	        throw new RestException( 404, 'Not Found');
        }
        return;
    }

}
