<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;

class ClientDashboardController extends Controller
{
    /**
     * @Route("/clientdashboard", name="clientdashboard")
     */
    public function clientdashboardAction(Request $request)
    {
        /**
         * This will check if the user is logged in
         * and if not direct to the login screen.
         */
        $em = $this->getDoctrine()->getManager();
        $session = $this->get('Commonservices')->getsessiondata();
        //$userID = $session->get('id');
        //$user = new User();
        //$userID = $user->getId();
        
        $user = $this->getUser();
        $sql =
            "
            SELECT
                `u`.`id`
            FROM
                `user` u
            WHERE
                `u`.`username` = '$user'
            LIMIT 1
            "
        ;
        $userID = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $userID = $row['id'];
        }

        $report_year = $request->request->get('report_year');
        if ($report_year == "") {
            $year1 = date("Y") - 10;
            $year2 = date("Y");
        } else {
            $year1 = $report_year;
            $year2 = $report_year;
        }

        // states
        $sql =
            "
            SELECT
                u.states
            FROM
                AppBundle\Entity\User u
            WHERE
                u.id = :id
            "
        ;
        $query = $em->createQuery($sql);
        $query->setParameter('id', $userID);
        $results = $query->execute();

        $state_list = "";
        $states = unserialize($results[0]['states']);
        foreach ($states as $key => $value) {
            $state_list .= "'$value',";
        }
        $state_list = substr($state_list, 0, -1);

        if ($state_list == "") {
            $this->addFlash('info', 'You do not have access to this state.');
            return $this->redirectToRoute('homepage');
        }

        // load DOT
        $sql = "
        SELECT
            `d`.`id` AS 'dotID',
            `d`.`name`,
            `d`.`logo`

        FROM
            `dots` d

        WHERE
            `d`.`stateID` IN ($state_list)

        LIMIT 1
        ";
        
        $logo = "";
        $dotID = "";
        $name = "";

        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $logo = $row['logo'];
            $dotID = $row['dotID'];
            $name = $row['name'];
        }

        // Get reviews
        $year = date("Y");
        $sql = "
        SELECT
            COUNT(`r`.`projectID`) AS 'total'

        FROM
            `projects` p,
            `review` r

        WHERE
            `p`.`dotID` = '$dotID'
            AND `p`.`id` = `r`.`projectID`
            AND DATE_FORMAT(`r`.`date_received`,'%Y') BETWEEN '$year1' AND '$year2'
        ";
        $total_reviews = "0";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $total_reviews = $row['total'];
        }

        // get total comments
        $sql = "
        SELECT
            `r`.*

        FROM
            `projects` p, `review` r

        WHERE
            `p`.`dotID` = '$dotID'
            AND `p`.`id` = `r`.`projectID`
            AND DATE_FORMAT(`r`.`date_received`,'%Y') BETWEEN '$year1' AND '$year2'
        ";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        $reviewID = "";
        $total_comments = "";
        while ($row = $result->fetch()) {
            $reviewID .= "$row[reviewID],";
        }
        $reviewID = substr($reviewID, 0, -1);
        if ($reviewID != "") {
            $sql = "
            SELECT COUNT(`Comments`) AS 'total' FROM `xml_data` WHERE `reviewID` IN ($reviewID)
            ";

            $result = $em->getConnection()->prepare($sql);
            $result->execute();
            while ($row = $result->fetch()) {
                $total_comments = $row['total'];
            }
        }

        $len = strlen($total_comments);
        switch ($len) {
            case "1":
                $pre = "00000";
                break;

            case "2":
                $pre = "0000";
                break;

            case "3":
                $pre = "000";
                break;

            case "4":
                $pre = "00";
                break;

            case "5":
                $pre = "0";
                break;

            case "6":
                $pre = "";
                break;
        }
        $total_comments = $pre . $total_comments;
        $total_comments_avg = "0";
        @$total_comments_avg = floor($total_comments / $total_reviews);


        $pre2 = "";
        $len2 = strlen($total_comments_avg);
        switch ($len2) {
            case "1":
                $pre2 = "00000";
                break;

            case "2":
                $pre2 = "0000";
                break;

            case "3":
                $pre2 = "000";
                break;

            case "4":
                $pre2 = "00";
                break;

            case "5":
                $pre2 = "0";
                break;

            case "6":
                $pre2 = "";
                break;
        }
        $total_comments_avg = $pre2 . $total_comments_avg;

        // cost savings
        $sql = "
        SELECT
            `r`.`reviewID`,
            `p`.`id`
        FROM
            `projects` p,
            `review` r

        WHERE
            `p`.`dotID` = '$dotID'
            AND `p`.`id` = `r`.`projectID`
            AND DATE_FORMAT(`r`.`date_received`,'%Y') BETWEEN '$year1' AND '$year2'
        ";
        $total_cost_reduction = "0";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $series = "";
            $sql2 = "SELECT DISTINCT `series` FROM `xml_data` WHERE `projectID` = '$row[id]' 
            AND `reviewID` = '$row[reviewID]' ORDER BY `series` DESC LIMIT 1";
            $result2 = $em->getConnection()->prepare($sql2);
            $result2->execute();
            while ($row2 = $result2->fetch()) {
                $series = $row2['series'];
            }

            $sql2 = "
            SELECT
                `x`.`Cost_Reduction`
            FROM
                `xml_data` x

            WHERE
                `x`.`projectID` = '$row[id]'
                AND `x`.`reviewID` = '$row[reviewID]'
                AND `x`.`series` = '$series'
            ";
            $cost_reduction = "0";
            $total_cost_reduction = "0";
            $result2 = $em->getConnection()->prepare($sql2);
            $result2->execute();
            while ($row2 = $result2->fetch()) {
                if ($row2['Cost_Reduction'] != "") {
                    $cost_reduction = $row2['Cost_Reduction'];
                    $cost_reduction = str_replace("$", "", $cost_reduction);
                    $cost_reduction = str_replace(",", "", $cost_reduction);
                    $total_cost_reduction = $total_cost_reduction + $cost_reduction;
                }
            }
        }

        // graph 1
        $category_data = $this->get('Commonservices')->clientDashBoardGraphsCategory($dotID, $year1, $year2);

        $per = "0";
        $per2 = "0";
        $data1 = "";
        $data2 = "";
        $data3 = "";

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
                    foreach ($category_data2 as $k2 => $v2) {
                        $per2 = floor(($v2 / $total2) * 100);
                        $data2 .= "['$k2',$per2],";
                    }
                    $data2 = substr($data2, 0, -1);
                    $data3 .= "{name: '$key', id: '$key', data: [$data2]},";
                }
            }
            $data1 = substr($data1, 0, -1);
            $data3 = substr($data3, 0, -1);
        }

        $chart1 = $this->get('Commonservices')->pieChartDrill('container1', $data1, $data3, 'Number of Comments by Category', 'Click the slices to view Comment Types of each Category.', 'Categories');

        // graph 2
        $per = "0";
        $per2 = "0";
        $data1 = "";
        $data2 = "";
        $data3 = "";
        $category_data = $this->get('Commonservices')->clientDashboardGraphsDiscipline($dotID, $year1, $year2);

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
                    foreach ($category_data2 as $k2 => $v2) {
                        $per2 = floor(($v2 / $total2) * 100);
                        $data2 .= "['$k2',$per2],";
                    }
                    $data2 = substr($data2, 0, -1);
                    $data3 .= "{name: '$key', id: '$key', data: [$data2]},";
                }
            }
            $data1 = substr($data1, 0, -1);
            $data3 = substr($data3, 0, -1);
        }

        $chart2 = $this->get('Commonservices')->pieChartDrill('container2', $data1, $data3, 'Number of Comments by Discipline', 'Click the slices to view Categories of each Discipline.', 'Discipline');

        // graph 3
        $chart3 = $this->get('Commonservices')->comboChart($dotID, $year1, $year2);

        // graph 4
        $y = date("Y");
        $title = "$y Year-to-Date Reviews";
        $chart4 = $this->get('Commonservices')->gauge('container4', $title, $total_reviews);

        $savingsOpportunities = $this->get('Commonservices')->savingsOpportunities($dotID, $year1, $year2);

        $date_report = array();
        $date_report[0]['year'] = date("Y");
        $date_report[1]['year'] = $date_report[0]['year'] - 1;
        $date_report[2]['year'] = $date_report[0]['year'] - 2;
        $date_report[3]['year'] = $date_report[0]['year'] - 3;

        return $this->render('dashboard/client.html.twig', [
            'dotID' => $dotID,
            'logo' => $logo,
            'total_comments' => $total_comments,
            'total_comments_avg' => $total_comments_avg,
            'total_cost_reduction' => $total_cost_reduction,
            'chart1' => $chart1,
            'chart2' => $chart2,
            'chart3' => $chart3,
            'chart4' => $chart4,
            'savingsOpportunities' => $savingsOpportunities,
            'date_report' => $date_report,
            'report_year' => $report_year,
        ]);
    }
}
