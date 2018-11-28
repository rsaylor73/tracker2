<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ClientController extends Controller
{
    /**
     * @Route("/client_list_project/{dotID}", name="client_list_project")
     */
    public function clientlistprojectAction(Request $request, $dotID = '')
    {
        $em = $this->getDoctrine()->getManager();
        //$session = $this->get('Commonservices')->getsessiondata();
        //$userID = $session->get('id');

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

        $sql = "
        SELECT
            `id`,`project_type`
        FROM
            `project_type`
        WHERE
            1
        ORDER BY `project_type` ASC
        ";
        $projecttype = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $projecttype .= "<option value=\"$row[id]\">$row[project_type]</option>";
        }

        $region = $this->get('Commonservices')->getRegion(null, $state);

        $year = date("Y");
        $p_year = $year - 1;
        $n_year = $year + 1;

        $year_select = "
        <option>$p_year</option>
        <option>$year</option>
        <option>$n_year</option>
        ";

        return $this->render('modal/client_list_projects.html.twig', [
            'contacts' => $contacts,
            'dotID' => $dotID,
            'dotproject' => $dotproject,
            'projecttype' => $projecttype,
            'region' => $region,
            'year_select' => $year_select,
        ]);
    }

    /**
     * @Route("/client_search_project", name="client_search_project")
     */
    public function clientsearchprojectAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $this->get('Commonservices')->getsessiondata();
        $userID = $session->get('id');

        $region = $request->request->get('region');
        $dotproject = $request->request->get('dotproject');
        $start_date = $request->request->get('start_date');
        $end_date = $request->request->get('end_date');
        $year = $request->request->get('year');
        $quarter = $request->request->get('quarter');
        $project_type = $request->request->get('project_type');
        $contactID = $request->request->get('contactID');
        $dotID = $request->request->get('dotID');

        $logo = "";

        $sql = "
        SELECT
            `d`.`logo`

        FROM
            `dots` d

        WHERE
            `d`.`id` = '$dotID'
        ";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $logo = $row['logo'];
        }

        $sql_extra = "";
        $sql_date_extra = "";

        if ($region) {
            $sql_extra .= "AND `r`.`id` = '$region'";
        }

        if ($dotproject) {
            $sql_extra .= "AND `p`.`id` = '$dotproject'";
        }

        $date1 = "";
        $date2 = "";
        if (($start_date) && ($end_date)) {
            $date1 = date("Y-m-d", strtotime($start_date));
            $date2 = date("Y-m-d", strtotime($end_date));
            $sql_date_extra .= " AND `rw`.`date_completed` BETWEEN '$date1' AND '$date2'";
        }

        if ($year) {
            $sql_date_extra .= " AND DATE_FORMAT(`rw`.`date_completed`,'%Y') = '$year'";
        }

        $date1 = "";
        $date2 = "";
        if ($quarter) {
            $year = date("Y");
            switch ($quarter) {
                case "1":
                    $date1 = $year . "-01-01";
                    $date2 = $year . "-03-31";
                break;

                case "2":
                    $date1 = $year . "-04-01";
                    $date2 = $year . "-06-30";
                break;

                case "3":
                    $date1 = $year . "-07-01";
                    $date2 = $year . "-09-30";
                break;

                case "4":
                    $date1 = $year . "-10-01";
                    $date2 = $year . "-12-31";
                break;
            }
            $sql_date_extra .= " AND `rw`.`date_completed` BETWEEN '$date1' AND '$date2'";
        }

        if ($project_type) {
            $sql_extra .= "AND `p`.`projecttypeID` = '$project_type'";
        }

        if ($contactID != "") {
            $sql_extra .= "AND `p`.`contactID` = '$contactID'";
        }

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

        $data = array();
        $i = "0";

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

        return $this->render('projects/client_search_project.html.twig', [
            'logo' => $logo,
            'data' => $data,
            'dotID' => $dotID,
        ]);

    }

    /**
     * @Route("/client_load_project/{dotID}", name="client_load_project")
     */
    public function clientloadprojectAction(Request $request, $dotID = '')
    {

        $em = $this->getDoctrine()->getManager();
        $session = $this->get('Commonservices')->getsessiondata();
        $userID = $session->get('id');

        $options = "";
        $sql = "SELECT `id`,`dotproject` FROM `projects` WHERE `dotID` = '$dotID'";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $options .= "<option value=\"$row[id]\">$row[dotproject]</option>";
        }

        return $this->render('modal/client_load_project.html.twig', [
            'options' => $options,
        ]);
    }

    /**
     * @Route("/client_view_project/{id}/{file}", name="client_view_project")
     */
    public function clientviewprojectAction(Request $request, $id = '', $file = '')
    {

        $em = $this->getDoctrine()->getManager();
        $session = $this->get('Commonservices')->getsessiondata();
        $userID = $session->get('id');

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
            `d`.`id` AS 'dotID',
            `pt`.`project_type`,
            `r`.`name` AS 'regionName',
            `c`.`first`,
            `c`.`last`

        FROM
            `projects` p

        LEFT JOIN `dots` d ON `p`.`dotID` = `d`.`id`
        LEFT JOIN `project_type` pt ON `p`.`projecttypeID` = `pt`.`id`
        LEFT JOIN `region` r ON `p`.`regionID` = `r`.`id`
        LEFT JOIN `contacts` c ON `p`.`contactID` = `c`.`id`

        WHERE
            `p`.`id` = '$id'
        ";
        $data = array();
        $dotID = "";
        $contactID = "";
        $projectID = "";

        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            foreach($row as $key=>$value) {
                $data[$key] = $value;
            }
            $dotID = $row['dotID'];
            $contactID = $row['contactID'];
            $projectID = $row['projectID'];
        }

        // locate reviews
        $sql = "
        SELECT
            `s`.`Description`,
            `r`.`reviewID` AS 'id',
            `r`.`pdf_file_client`
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
            foreach ($row as $key=>$value) {
                $review[$i][$key] = $value;
            }
            $i++;
        }

        return $this->render('projects/client_view_project.html.twig', [
            'review' => $review,
            'data' => $data,
        ]);
    }

    /**
     * @Route("/client_view_report", name="client_view_report")
     */
    public function clientviewreportAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $this->get('Commonservices')->getsessiondata();
        $userID = $session->get('id');

        $dotID = $request->request->get('dotID');

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

        $projectID = "";
        $projects = "";

        $adata = $request->request;
        $found = "0";
        foreach ($adata as $key => $value) {
            if ($key != "dotID") {
                $found = "1";
                $projectID = substr($key,1);
                $projects .= "'$projectID',";
            }
        }
        $projects = substr($projects,0,-1);
        if ($found == "0") {
            // redirect
            $this->addFlash('danger', 'You did not select any projects.');
            return $this->redirectToRoute('dashboard');
        }

        // graph 1
        $data1 = "";
        $data2 = "";
        $data3 = "";
        $category_data = $this->get('Commonservices')->clientReportGraphsCategory($projects);
        $category_data1 = $category_data['data1'];
        if (is_array($category_data1)) {
            $total = array_sum($category_data1);
            foreach ($category_data1 as $key => $value) {
                $per = floor(($value / $total) * 100);
                $data1 .= "{name: '$key',y: $per,drilldown: '$key'},";

                $category_data2 = $category_data['data2'][$key];
                $data2 = "";
                if (is_array($category_data2)) {
                    $total2 = array_sum($category_data2);
                    foreach($category_data2 as $k2 => $v2) {
                        $per2 = floor(($v2 / $total2) * 100);
                        $data2 .= "['$k2',$per2],";
                    }
                    $data2 = substr($data2,0,-1);
                    $data3 .= "{name: '$key', id: '$key', data: [$data2]},";
                }
            }
            $data1 = substr($data1,0,-1);
            $data3 = substr($data3,0,-1);
        }
        $chart1 = $this->get('Commonservices')->pieChartDrill('container1',$data1,$data3,'Distribution of Comments by Category','Click the slices to view Comment Types of each Category.','Categories');


        // graph 2
        $data1 = "";
        $data2 = "";
        $data3 = "";
        $category_data = $this->get('Commonservices')->clientReportGraphsCategory($projects);
        $category_data1 = $category_data['data1'];
        if(is_array($category_data1)) {
            $total = array_sum($category_data1);
            foreach($category_data1 as $key=>$value) {
                $per = floor(($value / $total) * 100);
                $data1 .= "{name: '$key',y: $per,drilldown: '$key'},";

                $category_data2 = $category_data['data2'][$key];
                $data2 = "";
                if(is_array($category_data2)) {
                    $total2 = array_sum($category_data2);
                    foreach($category_data2 as $k2=>$v2) {
                        $per2 = floor(($v2 / $total2) * 100);
                        $data2 .= "['$k2',$per2],";
                    }
                    $data2 = substr($data2,0,-1);
                    $data3 .= "{name: '$key', id: '$key', data: [$data2]},";
                }
            }
            $data1 = substr($data1,0,-1);
            $data3 = substr($data3,0,-1);
        }

        $chart2 = $this->get('Commonservices')->pieChartDrill('container2',$data1,$data3,'Distribution of Comments by Discipline','Click the slices to view Categories of each Discipline.','Discipline');

        // graph 3
        $chart3_data = $this->report_graph_avg_comments_per_phase($projects);
        $chart3_bar_data = "";
        $chart3_bar_label = "";
        foreach ($chart3_data as $key => $value) {
            $chart3_bar_data .= $value['avg_review_type'] . ",";
            $chart3_bar_label .= "'" . $value['review_type'] . "',";
        }
        $chart3_bar_data = substr($chart3_bar_data, 0, -1);
        $chart3_bar_label = substr($chart3_bar_label, 0, -1);

        $chart3 = $this->get('Commonservices')->barChartLine('container3','Number of Comments per Phase', $sub1 = "Comments", $sub2 = "Comments", "Bar Chart", "Line Chart", $chart3_bar_data, $chart3_bar_label);

        return $this->render('projects/client_view_report.html.twig', [
            'logo' => $logo,
            'dotID' => $dotID,
            'category_data' => $category_data,
            'chart1' => $chart1,
            'chart2' => $chart2,
            'chart3' => $chart3,
        ]);

    }

    private function report_graph_avg_comments_per_phase($projects)
    {
        $em = $this->getDoctrine()->getManager();

        $sql = "
        SELECT
            `s`.`Description` AS 'review_type' ,
            COUNT(`r`.`review_type`) AS 'avg_review_type'

        FROM
            `xml_data` x

        INNER JOIN `review` r ON `x`.`reviewID` = `r`.`reviewID`
        INNER JOIN `SubmittalTypes` s ON `r`.`project_phase` = `s`.`id`

        WHERE
            `x`.`projectID` IN ($projects)

        GROUP BY `s`.`id`
        ";

        $data = array();
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        $i = "0";
        $type = "";
        while ($row = $result->fetch()) {


            foreach ($row as $key => $value) {
                $data[$i][$key] = $value;
            }
            $i++;
        }
        return ($data);
    }

}
