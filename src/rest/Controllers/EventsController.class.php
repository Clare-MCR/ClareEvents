<?php namespace Rest\Controllers;

/**
 * Created by PhpStorm.
 * User: rjgun
 * Date: 10/04/2016
 * Time: 19:24
 */
use Jacwright\RestServer\RestException;
use PDOException;
use Rest\Classes\Emailer;
use Rest\Classes\EmailTemplate;
use Rest\Functions;

/*
 * Database Structures
 * eventsList
 *  id              // numerical event id
 *  name            // name of event
 *  category        // category of event
 *  costMain        // Cost of Main Ticket
 *  costSecond      // Cost of Second Ticket
 *  costExtra       // Optional Additional charge, i.e. Wine
 *  currentGuests   // Current Guest count
 *  total           // Total number of tickets on sale
 *  maxGuests       // Max Guests Allowed
 *  eventDate       // Date of Event
 *  openDate        // Date of Ticket Sale Opening
 *  closeDate       // Date of Ticket Sale Closing
 *  guestDate       // Date Guest limit is removed
 *  sent            // Event Billing Sent
 *
 * bookings
 *  id              // numerical booking id
 *  eventid         // numerical event id
 *  bookingid       // Booking ID (TODO: Remove)
 *  admin           // Is Admin Booking
 *  booker          // Booker CRSID
 *  diet            // Diet
 *  extra           // Add Extra charge
 *  name            // Booking Name
 *  other           // Additional Dietary information
 *  ticketAssigned  // has ticket or in queue
 *  type            // Main or Second Ticket
 */






class EventsController extends DefaultController
{

    /**
     * Gets the events
     * If id supplied return event information only
     * otherwise return events with flag of whether user or admin has a booking
     *
     * @url GET /
     * @url GET /$id
     *
     * @param string $id
     * @param null $from
     * @param null $to
     *
     * @return
     */
    public function getData($id = null, $from = null, $to = null)
    {
        if($id)
        {
            $this->db->query('SELECT * FROM eventsList WHERE id=:id');
            $this->db->bind(':id', $id);
            return $this->db->single();
        }
        else
        {
            if ($from && $to) {
                if ($this->admin)
                {
                    $this->db->query( 'SELECT *
                                      FROM eventsList e
                                      LEFT JOIN (SELECT eventid,MAX(admin) as ad , IF(MIN(admin)=0,1,0) as user FROM bookings 
                                      			WHERE booker = :crsid OR admin = 1
                                      			GROUP BY eventid
                                      			) AS b
                                      ON e.id = b.eventid
                                      WHERE (eventDate BETWEEN :to AND :from) 
                                      ORDER BY  e.id');
                }
                else
                {
                    $this->db->query( 'SELECT *
                                      FROM eventsList e
                                      LEFT JOIN (SELECT eventid,MAX(admin) as ad , IF(MIN(admin)=0,1,0) as user FROM bookings 
                                      			WHERE booker = :crsid
                                      			GROUP BY eventid
                                      			) AS b
                                      ON e.id = b.eventid
                                      WHERE (eventDate BETWEEN :to AND :from) 
                                      ORDER BY  e.id');
                }
                $this->db->bind(':from', gmdate('Y-m-d H:i:s', Functions\test_input($from)));
                $this->db->bind(':to', gmdate('Y-m-d H:i:s', Functions\test_input($to)));
            }
            else
            {
                if ($this->admin) {
	                $this->db->query( 'SELECT *
                                      FROM eventsList e
                                      LEFT JOIN (SELECT eventid,MAX(admin) as ad , IF(MIN(admin)=0,1,0) as user FROM bookings 
                                      			WHERE booker = :crsid OR admin = 1
                                      			GROUP BY eventid
                                      			) AS b
                                      ON e.id = b.eventid
                                      ORDER BY  e.id');
                }
                else
                {
                    $this->db->query( 'SELECT *
                                      FROM eventsList e
                                      LEFT JOIN (SELECT eventid,MAX(admin) as ad , IF(MIN(admin)=0,1,0) as user FROM bookings 
                                      			WHERE booker = :crsid
                                      			GROUP BY eventid
                                      			) AS b
                                      ON e.id = b.eventid
                                      ORDER BY  e.id');
                }
            }
            $this->db->bind(':crsid', $this->user);
            return $this->db->resultset();
        }
    }


    /**
     * Add new Event
     *
     * @url POST /
     * @param $data
     * @return array
     * @throws RestException
     */
    public function addEvent($data)
    {
        //TODO: add guest restriction date.
        if (!$this->admin) {
            throw new RestException(404, 'User Must be admin to add Events');
        }

        if (!isset($data->name)) {
            throw new RestException(404, 'Name required');
        }

        if (!isset($data->category)) {
            throw new RestException(404, 'Category required');
        }

        if (!isset($data->total)) {
            throw new RestException(404, 'total required');
        }

        if (!isset($data->maxGuests)) {
            throw new RestException(404, 'max Guests required');
        }

        if (!isset($data->costMain)) {
            throw new RestException(404, 'cost main required');
        }

        if (!isset($data->costSecond)) {
            throw new RestException(404, 'cost Second required');
        }

        if (!isset($data->costExtra)) {
            throw new RestException(404, 'cost Extra required');
        }

        if (!isset($data->eventDate)) {
            throw new RestException(404, 'event date required');
        }

        if (!isset($data->openDate)) {
            throw new RestException(404, 'open date required');
        }

        if (!isset($data->closeDate)) {
            throw new RestException(404, 'close date required');
        }
        if (!isset($data->guestDate)) {
	        $data->guestDate = $data->closeDate;
        }

        $this->db->query('INSERT INTO eventsList 
                          (name,category, total,currentGuests, maxGuests, costMain,costSecond,costExtra,eventDate,openDate, closeDate,guestDate,sent) 
                          VALUES (:name, :category, :total, 0, :guests, :main, :second, :extra, :date, :open, :close, :guestDat, :sent)');
        $this->db->bind(':name', Functions\test_input($data->name));
        $this->db->bind(':category', Functions\test_input($data->category));
        $this->db->bind(':total', Functions\test_input($data->total));
        $this->db->bind(':guests', Functions\test_input($data->maxGuests));
        $this->db->bind(':main', Functions\test_input($data->costMain));
        $this->db->bind(':second', Functions\test_input($data->costSecond));
        $this->db->bind(':extra', Functions\test_input($data->costExtra));
        $this->db->bind(':date', gmdate('Y-m-d H:i:s', Functions\test_input($data->eventDate)));
        $this->db->bind(':open', gmdate('Y-m-d H:i:s', Functions\test_input($data->openDate)));
        $this->db->bind(':close', gmdate('Y-m-d H:i:s', Functions\test_input($data->closeDate ) ) );
	    $this->db->bind( ':guestDate', gmdate( 'Y-m-d H:i:s', Functions\test_input( $data->guestDate ) ) );
	    $this->db->bind(':sent', "N");
        $this->db->execute();
        return array("id" => $this->db->lastInsertId());
    }

    /**
     * Update Event
     *
     * @url PUT /$id
     * @param null $id
     * @param $data
     * @return bool
     * @throws RestException
     */
    public function updateEvent($id = null, $data)
    {
        //TODO: add guest restriction date.
        if (!$this->admin) {
            throw new RestException(404, 'User Must be admin to modify Events');
        }

        if (!isset($id)) {
            throw new RestException(404, 'Id required');
        }
        $this->db->beginTransaction();
        $error=false;
        if(!$error && isset($data->name))
        {
            $this->db->query('UPDATE eventsList SET name=:name WHERE id=:id');
            $this->db->bind(':name', Functions\test_input($data->name));
            $this->db->bind(':id', $id);
            try {
                $this->db->execute();
            } catch (PDOException $e) {
                $error = true;
            }
        }
        if(!$error && isset($data->category))
        {
            $this->db->query('UPDATE eventsList SET category=:category WHERE id=:id');
            $this->db->bind(':category', Functions\test_input($data->category));
            $this->db->bind(':id', $id);
            try {
                $this->db->execute();
            } catch (PDOException $e) {
                $error = true;
            }
        }
        if(!$error && isset($data->total))
        {
            $this->db->query('UPDATE eventsList SET total=:total WHERE id=:id');
            $this->db->bind(':total', Functions\test_input($data->total));
            $this->db->bind(':id', $id);
            try {
                $this->db->execute();
            } catch (PDOException $e) {
                $error = true;
            }
        }
        if(!$error && isset($data->maxGuests))
        {
            $this->db->query('UPDATE eventsList SET maxGuests=:maxGuests WHERE id=:id');
            $this->db->bind(':maxGuests', Functions\test_input($data->maxGuests));
            $this->db->bind(':id', $id);
            try {
                $this->db->execute();
            } catch (PDOException $e) {
                $error = true;
            }
        }
        if(!$error && isset($data->costMain))
        {
            $this->db->query('UPDATE eventsList SET costMain=:costMain WHERE id=:id');
            $this->db->bind(':costMain', Functions\test_input($data->costMain));
            $this->db->bind(':id', $id);
            try {
                $this->db->execute();
            } catch (PDOException $e) {
                $error = true;
            }
        }
        if(!$error && isset($data->costSecond))
        {
            $this->db->query('UPDATE eventsList SET costSecond=:costSecond WHERE id=:id');
            $this->db->bind(':costSecond', Functions\test_input($data->costSecond));
            $this->db->bind(':id', $id);
            try {
                $this->db->execute();
            } catch (PDOException $e) {
                $error = true;
            }
        }
        if(!$error && isset($data->costExtra))
        {
            $this->db->query('UPDATE eventsList SET costExtra=:costExtra WHERE id=:id');
            $this->db->bind(':costExtra', Functions\test_input($data->costExtra));
            $this->db->bind(':id', $id);
            try {
                $this->db->execute();
            } catch (PDOException $e) {
                $error = true;
            }
        }
        if(!$error && isset($data->eventDate))
        {
            $this->db->query('UPDATE eventsList SET eventDate=:eventDate WHERE id=:id');
            $this->db->bind(':eventDate', gmdate('Y-m-d H:i:s', Functions\test_input($data->eventDate)));
            $this->db->bind(':id', $id);
            try {
                $this->db->execute();
            } catch (PDOException $e) {
                $error = true;
            }
        }
        if(!$error && isset($data->openDate))
        {
            $this->db->query('UPDATE eventsList SET openDate=:openDate WHERE id=:id');
            $this->db->bind(':openDate', gmdate('Y-m-d H:i:s', Functions\test_input($data->openDate)));
            $this->db->bind(':id', $id);
            try {
                $this->db->execute();
            } catch (PDOException $e) {
                $error = true;
            }
        }
        if(!$error && isset($data->closeDate))
        {
            $this->db->query('UPDATE eventsList SET closeDate=:closeDate WHERE id=:id');
            $this->db->bind(':closeDate', gmdate('Y-m-d H:i:s', Functions\test_input($data->closeDate)));
            $this->db->bind(':id', $id);
            try {
                $this->db->execute();
            } catch (PDOException $e) {
                $error = true;
            }
        }
	    if ( ! $error && isset( $data->guestDate ) ) {
		    $this->db->query( 'UPDATE eventsList SET guestDate=:guestDate WHERE id=:id' );
		    $this->db->bind( ':guestDate', gmdate( 'Y-m-d H:i:s', Functions\test_input( $data->guestDate ) ) );
		    $this->db->bind( ':id', $id );
		    try {
			    $this->db->execute();
		    } catch ( PDOException $e ) {
			    $error = true;
		    }
	    }

        if ($error) {
            $this->db->cancelTransaction();
            throw new RestException(409, 'Error inserting');
        } else {
            $this->db->endTransaction();
            return true;
        }
    }

    /**
     * Delete event
     *
     * @url DELETE /$id
     * @param $id
     * @return bool
     * @throws RestException
     */
    public function deleteEvent($id)
    {
        if (!$this->admin) {
            throw new RestException(404, 'User Must be admin to delete Events');
        }
        if (!isset($id)) {
            throw new RestException(404, 'Id required');
        }
        $this->db->query('DELETE FROM eventsList WHERE id=:id');
        $this->db->bind(':id', $id);
        try {
            $this->db->execute();
	        $this->db->query( 'DELETE FROM bookings WHERE eventid=:id' );
	        $this->db->bind( ':id', $id );
	        try {
		        $this->db->execute();

		        return true;
	        } catch ( PDOException $e ) {
		        throw new RestException( 409, 'Error deleting Bookings' );
	        }
        } catch ( PDOException $e ) {
	        throw new RestException( 409, 'Error deleting Event' );
        }
    }

    /**
     * Send Event
     *
     * @url GET /send/$id
     * @param $id
     * @param bool $email
     * @return bool
     * @throws RestException
     */
    public function sendEvent($id,$email=true)
    {
        $event= array();
        $officialBill = array();
        $otherBill = array();
        $guestList=array();

        if (!$this->admin) {
            throw new RestException(404, 'User Must be admin to send Events');
        }
        if (!isset($id)) {
            throw new RestException(404, 'Id required');
        }

        $this->db->beginTransaction();
        $error=false;

        //define who made the cut
        if(!$error && $email)
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
        if(!$error && $email)
        {
            $this->db->query('UPDATE bookings SET ticketAssigned=0 WHERE eventid=:id ');
            $this->db->bind(':id',$id);
            try {
                $this->db->execute();
            } catch (PDOException $e) {
                $error=true;
                print("error resetting tickets");
            }
        }
        if(!$error && $email)
        {
            $this->db->query('UPDATE bookings SET ticketAssigned=1 WHERE eventid=:id ORDER BY id LIMIT :total');
            $this->db->bind(':id',$id);
            $this->db->bind(':total',(int)$event['total']);
            try {
                $this->db->execute();
            } catch (PDOException $e) {
                $error=true;
                $this->db->debugDumpParams();
                print("error setting tickets");
            }
        }
        if(!$error && $email)
        {
            $this->db->query('SELECT \'MCR\' AS booker,\'MCR Admin Booking\' as name,COUNT(type) AS total,SUM(type) AS Main, 
                              COUNT(type)-SUM(type) AS Second, SUM(extra) AS Extra
                              FROM `bookings` WHERE eventid=:id AND admin=1 AND ticketAssigned=1
                              GROUP BY admin
                              UNION ALL
                              SELECT booker, users.name,COUNT(bookings.type) AS total,SUM(bookings.type) AS Main, 
                              COUNT(bookings.type)-SUM(bookings.type) AS Second, SUM(extra) AS Extra
                              FROM bookings 
                              LEFT JOIN users 
                              ON users.crsid=bookings.booker 
                              WHERE eventid=:id AND bookings.admin=0 AND bill=1 AND ticketAssigned=1
                              GROUP BY booker');
            $this->db->bind(':id',$id);
            try {
                $officialBill = $this->db->resultset();
            } catch (PDOException $e) {
                $error=true;
                print("error getting billing");
            }
        }

        if(!$error && $email)
        {
            $this->db->query('SELECT booker, users.name,COUNT(bookings.type) AS total,SUM(bookings.type) AS Main, 
                              COUNT(bookings.type)-SUM(bookings.type) AS Second, SUM(extra) AS Extra
                              FROM bookings 
                              LEFT JOIN users 
                              ON users.crsid=bookings.booker 
                              WHERE eventid=:id AND bookings.admin=0 AND bill=0 AND ticketAssigned=1
                              GROUP BY booker');
            $this->db->bind(':id',$id);
            try {
                $otherBill = $this->db->resultset();
            } catch (PDOException $e) {
                $error=true;
                print("error getting other billing");
            }
        }
        // TODO: Make sure number of guests per person does not exceed event limits
        if(!$error)
        {
            $this->db->query('
                          SELECT *
                          FROM bookings 
                          WHERE eventid=:id AND ticketAssigned=1');
            $this->db->bind(':id',$id);
            try {
                $guestList = $this->db->resultset();
            } catch (PDOException $e) {
                $error=true;
                print("error getting guests");
            }
        }
        if(!$error){
            $this->db->query('UPDATE eventsList SET sent=\'Y\',currentGuests=:guests WHERE id=:id');
            $this->db->bind(':id',$id);
            $this->db->bind(':guests',$this->db->rowCount());
            try {
                $this->db->execute();
            } catch (PDOException $e) {
                $error=true;
            }
        }
        if ($error) {
            $this->db->cancelTransaction();
            throw new RestException(409, 'Transaction Error');
        } else {
            $this->db->endTransaction();
        }

        if(!$email){return true;}
        $emails = array(
            $this->user."@cam.ac.uk"
        );

        $Emailer = new Emailer($emails);
        //More code here

        $Template = new EmailTemplate('Templates/BillingList.html');
        $Template->name = $event['name'];
        $Template->eventDate = $event['eventDate'];
        $Template->costMain= $event['costMain'];
        $Template->costSecond= $event['costSecond'];
        $Template->costExtra= $event['costExtra'];
        $Template->rows = $officialBill;
        //...

        $Emailer->SetTemplate($Template); //Email runs the compile
        $Emailer->send();

        $Template = new EmailTemplate('Templates/NonCollegeBillingList.html');
        $Template->name = $event['name'];
        $Template->eventDate = $event['eventDate'];
        $Template->costMain= $event['costMain'];
        $Template->costSecond= $event['costSecond'];
        $Template->costExtra= $event['costExtra'];
        $Template->rows = $otherBill;
        //...

        $Emailer->SetTemplate($Template); //Email runs the compile
        $Emailer->send();

        $Template = new EmailTemplate('Templates/GuestList.html');
        $Template->name = $event['name'];
        $Template->eventDate = $event['eventDate'];
        $Template->category= $event['category'];
        $Template->rows = $guestList;
        //...

        $Emailer->SetTemplate($Template); //Email runs the compile
        $Emailer->send();

        foreach($officialBill as $row)
        {
            if($row['booker']=="MCR"){continue;}
            $emails = array(
                $row['booker']."@cam.ac.uk"
            );
            $Emailer = new Emailer($emails);
            //More code here
            $Template = new EmailTemplate('Templates/ConfirmationEmail.html');
            $Template->name = $event['name'];
            $Template->eventDate = $event['eventDate'];
            $Template->costMain= $event['costMain'];
            $Template->costSecond= $event['costSecond'];
            $Template->costExtra= $event['costExtra'];
            $Template->noBill= false;
            $Template->row = $row;
            //...

            $Emailer->SetTemplate($Template); //Email runs the compile
            $Emailer->send();

        }
        foreach($otherBill as $row)
        {
            if($row['booker']=="MCR"){continue;}
            $emails = array(
                $row['booker']."@cam.ac.uk",
                "mcr-treasurer@clare.cam.ac.uk"
            );

            $Emailer = new Emailer($emails);
            //More code here
            $Template = new EmailTemplate('Templates/ConfirmationEmail.html');
            $Template->name = $event['name'];
            $Template->eventDate = $event['eventDate'];
            $Template->costMain= $event['costMain'];
            $Template->costSecond= $event['costSecond'];
            $Template->costExtra= $event['costExtra'];
            $Template->noBill= true;
            $Template->row = $row;
            //...

            $Emailer->SetTemplate($Template); //Email runs the compile
            $Emailer->send();

        }

        return true; // serializes object into JSON
    }



}
