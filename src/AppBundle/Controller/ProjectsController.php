<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\User;

class ProjectsController extends Controller
{

    /**
     * @Route("/save_project", name="save_project")
     */
    public function saveprojectAction(Request $request)
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        $dotID = $request->request->get('dotID');
        $dotproject = $request->request->get('dotproject');
        $subaccount = $request->request->get('subaccount');
        $description = $request->request->get('description');
        $projecttypeID = $request->request->get('projecttypeID');
        $est_const_cost = $request->request->get('est_const_cost');
        $est_ad_date = $request->request->get('est_ad_date');
        $date_received = $request->request->get('date_received');
        $date_completed = $request->request->get('date_completed');
        $contactID = $request->request->get('contacts');
        $regionID = $request->request->get('regionID');

        $sql = "
        INSERT INTO `projects` 
        (`dotproject`,`subaccount`,`projecttypeID`,
        `est_const_cost`,`est_ad_date`,
        `contactID`,`regionID`,`description`,`dotID`)
        VALUES
        (?,?,?,?,?,?,?,?,?)
        ";

        $result = $em->getConnection()->prepare($sql);
        $result->bindValue(1, $dotproject);
        $result->bindValue(2, $subaccount);
        $result->bindValue(3, $projecttypeID);
        $result->bindValue(4, $est_const_cost);
        $result->bindValue(5, $est_ad_date);
        $result->bindValue(6, $contactID);
        $result->bindValue(7, $regionID);
        $result->bindValue(8, $description);
        $result->bindValue(9, $dotID);
        $result->execute();

        $newprojectID = $em->getConnection()->lastInsertId();

        $this->addFlash('info', 'The project was added.');
        return $this->redirectToRoute('create_review', [
            'dotID' => $dotID,
            'dotproject' => $newprojectID,
        ]);
    }

    /**
     * @Route("/delete_project/{id}", name="delete_project")
     */
    public function deleteprojectAction($id)
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        // get dotID
        $dotID = "";
        $sql = "SELECT `dotID` FROM `projects` WHERE `id` = '$id'";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $dotID = $row['dotID'];
        }

        $sql = "DELETE FROM `review` WHERE `projectID` = '$id'";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();

        $sql = "DELETE FROM `projects` WHERE `id` = '$id'";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();

        $sql = "DELETE FROM `xml_data` WHERE `projectID` = '$id'";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();

        $this->addFlash('success', 'The project was deleted.');
        return $this->redirectToRoute('dots',[
            'id' => $dotID,
        ]);
    }

    /**
     * @Route("/check_dot", name="check_dot")
     */
    public function checkdotAction(Request $request)
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        $dotID = $request->query->get('dotID');
        $dotproject = $request->query->get('dotproject');

        $sql = "
        SELECT
            `dotproject`

        FROM
            `projects`

        WHERE
            `dotID` = '$dotID'
        ";
        $output = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            if ($row['dotproject'] == $dotproject) {
                $output = "<div class=\"alert alert-danger\">The DOT Project # has been used</div>";
            }
        }
        return new Response($output);

    }

    /**
     * @Route("/list_projects/{dotID}", name="list_projects")
     */
    public function listprojectsAction(Request $request, $dotID = '')
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        $sql = "
        SELECT
            `c`.`id`,
            `c`.`first`,
            `c`.`last`,
            `c`.`email`

        FROM
            `dots` d, `contacts` c

        WHERE
            `d`.`id` = '$dotID'
            AND `d`.`stateID` = `c`.`stateID`

        ORDER BY `c`.`last` ASC, `c`.`first` ASC
        ";
        $contacts = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $contacts .= "<option value=\"$row[id]\">$row[first] $row[last]</option>";
        }

        $sql = "SELECT `stateID` FROM `dots` WHERE `id` = '$dotID'";
        $stateID = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $stateID = $row['stateID'];
        }

        $sql = "SELECT `state` FROM `state` WHERE `state_id` = '$stateID'";
        $state = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $state = $row['state'];
        }

        $sql = "SELECT `id`,`dotproject` FROM `projects` WHERE `dotID` = '$dotID' ORDER BY `dotproject` ASC";
        $dotproject = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $dotproject .= "<option value=\"$row[id]\">$row[dotproject]</option>";
        }

        $year_start = "2016";
        $year_end = date("Y");

        $year_select = "";
        for ($i = $year_start; $i < $year_end; $i++) {
            $year_select .= "<option>$i</option>";
        }
        $year_select .= "<option>$year_end</option>";

        return $this->render('modal/listprojects.html.twig', [
            'dotproject' => $dotproject,
            'dotID' => $dotID,
            'projecttype' => $this->get('Commonservices')->getProjectTypes(''),
            'region' => $this->get('Commonservices')->getRegion('', $state),
            'contacts' => $contacts,
            'year_select' => $year_select,
        ]);
    }

    /**
     * @Route("/search_projects", name="search_projects")
     */
    public function searchprojectsAction(Request $request)
    {
        /**
         * This will display the results from the modal search window
         */
        $em = $this->getDoctrine()->getManager();
        $session = $this->get('Commonservices')->getsessiondata();
        $userID = $session->get('id');

        $region = $request->query->get('region');
        $dotproject = $request->query->get('dotproject');
        $start_date = $request->query->get('start_date');
        $end_date = $request->query->get('end_date');
        $year = $request->query->get('year');
        $quarter = $request->query->get('quarter');
        $project_type = $request->query->get('project_type');
        $contactID = $request->query->get('contactID');
        $dotID = $request->query->get('dotID');

        /**
         * ToDo: permissions
         */

        // get logo
        $sql = "
        SELECT
            `d`.`logo`

        FROM
            `dots` d

        WHERE
            `d`.`id` = '$dotID'
        ";
        $logo = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $logo = $row['logo'];
        }

        $sql_extra = "";
        $sql_date_extra = "";

        // filters
        if ($region != "") {
            $sql_extra .= "AND `r`.`id` = '$region'";
        }

        if ($dotproject != "") {
            $sql_extra .= "AND `p`.`id` = '$dotproject'";
        }

        $date1 = "";
        $date2 = "";
        if (($start_date != "") && ($end_date != "")) {
            $date1 = date("Y-m-d", strtotime($start_date));
            $date2 = date("Y-m-d", strtotime($end_date));
            $sql_date_extra .= " AND `rw`.`date_completed` BETWEEN '$date1' AND '$date2'";
        }

        if ($year != "") {
            $sql_date_extra .= " AND DATE_FORMAT(`rw`.`date_completed`,'%Y') = '$year'";
        }

        $year_test = date("Y");
        if ($quarter != "") {
            switch ($quarter) {
                case "1":
                    $date1 = $year_test . "-01-01";
                    $date2 = $year_test . "-03-31";
                    break;

                case "2":
                    $date1 = $year_test . "-04-01";
                    $date2 = $year_test . "-06-30";
                    break;

                case "3":
                    $date1 = $year_test . "-07-01";
                    $date2 = $year_test . "-09-30";
                    break;

                case "4":
                    $date1 = $year_test . "-10-01";
                    $date2 = $year_test . "-12-31";
                    break;
            }
            $sql_date_extra .= " AND `rw`.`date_completed` BETWEEN '$date1' AND '$date2'";
        }

        if ($project_type != "") {
            $sql_extra .= "AND `p`.`projecttypeID` = '$project_type'";
        }

        if ($contactID != "") {
            $sql_extra .= "AND `p`.`contactID` = '$contactID'";
        }

        // query data
        $data = array();
        $i = "0";
        $sql = "
        SELECT
            `p`.`id`,
            `p`.`dotproject`,
            `p`.`subaccount`,
            `p`.`projecttypeID`,
            `pt`.`project_type`,
            `p`.`regionID`,
            `r`.`name` AS 'region_name',
            `p`.`description`,
            `rw`.`projectID`,
            `p`.`est_const_cost`

        FROM
            `projects` p
            #`project_type` pt,
            #`region` r

        LEFT JOIN `review` rw ON `p`.`id` = `rw`.`projectID` $sql_date_extra
        LEFT JOIN `project_type` pt ON `p`.`projecttypeID` = `pt`.`id`
        LEFT JOIN `region` r ON `p`.`regionID` = `r`.`id`

        WHERE
            `p`.`dotID` = '$dotID'
            #AND `p`.`projecttypeID` = `pt`.`id`
            #AND `p`.`regionID` = `r`.`id`
            $sql_extra

        GROUP BY `p`.`id`
        ";
        if ($sql_date_extra != "") {
            $sql .= "HAVING `rw`.`projectID` > 0";
        }

        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            foreach ($row as $key => $value) {
                $data[$i][$key] = $value;
            }

            // Get a list of reviews
            $SubmittalTypes = "";
            $sql2 = "
            SELECT
                `s`.`Description`
            FROM
                `review` r,
                `SubmittalTypes` s

            WHERE
                `r`.`projectID` = '$row[id]'
                AND `r`.`project_phase` = `s`.`id`
            ";
            $result2 = $em->getConnection()->prepare($sql2);
            $result2->execute();
            while ($row2 = $result2->fetch()) {
                $SubmittalTypes .= "$row2[Description], ";
            }
            $SubmittalTypes = substr($SubmittalTypes, 0, -2);
            $data[$i]['SubmittalTypes'] = $SubmittalTypes;
            $i++;
        }

        return $this->render('projects/search_projects.html.twig', [
            'logo' => $logo,
            'dotID' => $dotID,
            'data' => $data,
        ]);
    }

    /**
     * @Route("/view_project/{id}", name="view_project")
     */
    public function viewprojectAction(Request $request, $id = '')
    {
        /**
         * This will display the results from the modal search window
         */
        $em = $this->getDoctrine()->getManager();
        $session = $this->get('Commonservices')->getsessiondata();
        $userID = $session->get('id');

        if ($id == "") {
            $id = $request->query->get('id');
        }
      
        $sql = "
        SELECT
            `p`.`id` AS 'projectID',
            `p`.`dotproject`,
            `p`.`subaccount`,
            `p`.`projecttypeID`,
            `p`.`description`,
            `p`.`est_const_cost`,
            `p`.`est_ad_date`,
            `p`.`regionID`,
            `p`.`contactID`,
            `d`.`logo`,
            `d`.`id` AS 'dotID'

        FROM
            `projects` p,
            `dots` d

        WHERE
            `p`.`id` = '$id'
            AND `p`.`dotID` = `d`.`id`
        ";
        $data = array();
        $ditID = "";
        $contactID = "";
        $projecttypeID = "";
        $regionID = "";
        $projectID = "";

        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            foreach ($row as $key => $value) {
                $data[$key] = $value;
            }
            $dotID = $row['dotID'];
            $contactID = $row['contactID'];
            $projecttypeID = $row['projecttypeID'];
            $regionID = $row['regionID'];
            $projectID = $row['projectID'];
        }

        // contact
        $contacts = "";
        $c_found = "0";
        $sql = "SELECT `c`.* FROM `contacts` c WHERE `c`.`id` = '$contactID'";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $c_found = "1";
            $contacts .= "<option selected value=\"$row[id]\">$row[first] $row[last]</option>";
        }
        if ($c_found != "1") {
            $contacts .= "<option value=\"\" selected>Select</option>";
        }

        $sql = "
        SELECT
            `c`.`id`,
            `c`.`first`,
            `c`.`last`,
            `c`.`email`

        FROM
            `dots` d, `contacts` c

        WHERE
            `d`.`id` = '$dotID'
            AND `d`.`stateID` = `c`.`stateID`

        ORDER BY `c`.`last` ASC, `c`.`first` ASC
        ";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $contacts .= "<option value=\"$row[id]\">$row[first] $row[last]</option>";
        }

        // states
        $stateID = "";
        $state = "";

        $sql = "SELECT `stateID` FROM `dots` WHERE `id` = '$dotID'";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $stateID = $row['stateID'];
        }

        $sql = "SELECT `state` FROM `state` WHERE `state_id` = '$stateID'";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $state = $row['state'];
        }

        // locate reviews
        $sql = "
        SELECT
            `s`.`Description`,
            `r`.`reviewID` AS 'id'
        FROM
            `review` r,
            `SubmittalTypes` s

        WHERE
            `r`.`projectID` = '$projectID'
            AND `r`.`project_phase` = `s`.`id`
        ";
        $i = "0";
        $review = array();

        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            foreach ($row as $key => $value) {
                $review[$i][$key] = $value;
            }
            $i++;
        }

        return $this->render('projects/viewproject.html.twig', [
            'data' => $data,
            'review' => $review,
            //'dotproject' => $dotproject,
            'dotID' => $dotID,
            'projecttype' => $this->get('Commonservices')->getProjectTypes(''),
            'region' => $this->get('Commonservices')->getRegion('', $state),
            'contacts' => $contacts,
            'id' => $id,
        ]);
    }

    /**
     * @Route("/new_project/{dotID}", name="new_project")
     */
    public function newprojectAction(Request $request, $dotID = '')
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();
        $session = $this->get('Commonservices')->getsessiondata();
        $userID = $session->get('id');

        $stateID = "";
        $sql = "SELECT `stateID` FROM `dots` WHERE `id` = '$dotID'";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $stateID = $row['stateID'];
        }

        $state = "";
        $sql = "SELECT `state` FROM `state` WHERE `state_id` = '$stateID'";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $state = $row['state'];
        }

        $projecttype = $this->get('Commonservices')->getProjectTypes();
        $region = $this->get('Commonservices')->getRegion('',$state);

        $sql = "
        SELECT
            `c`.`id`,
            `c`.`first`,
            `c`.`last`,
            `c`.`email`

        FROM
            `dots` d, `contacts` c

        WHERE
            `d`.`id` = '$dotID'
            AND `d`.`stateID` = `c`.`stateID`

        ORDER BY `c`.`last` ASC, `c`.`first` ASC
        ";
        $contacts = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $contacts .= "<option value=\"$row[id]\">$row[first] $row[last]</option>";
        }

        return $this->render('modal/new_project.html.twig', [
            'projecttype' => $projecttype,
            'region' => $region,
            'dotID' => $dotID,
            'contacts' => $contacts,
        ]);
    }

}
