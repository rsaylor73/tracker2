<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ReportsController extends Controller
{
    /**
     * @Route("/viewreport", name="viewreport")
     */
    public function viewreportAction(Request $request)
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        $dotID = $request->request->get('dotID');
        $postvars = $request->request->all();

		unset($postvars['dotID']);

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

        $projectID = "";
        $projects = "";

		if(is_array($postvars)) {
			foreach ($postvars as $key => $value) {
				$projectID = substr($key, 1);
				$projects .= "'$projectID',";
			}
			$projects = substr($projects, 0, -1);

		} else {
            $this->addFlash('info', 'You did not select any projects.');
            return $this->redirectToRoute('homepage');
		}

		// graph 1
		$data1 = "";
		$data3 = "";
		$category_data = $this->report_graphs_category($projects);
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

		$chart1 = $this->get('Commonservices')->pieChartDrill('container1',$data1,$data3,'Number of Comments by Category','Click the slices to view Comment Types of each Category.','Categories');

		// graph 2
		$data1 = "";
		$data2 = "";
		$data3 = "";
		$category_data = $this->report_graphs_discipline($projects);
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

		$chart2 = $this->get('Commonservices')->pieChartDrill('container2',$data1,$data3,'Number of Comments by Discipline','Click the slices to view Categories of each Discipline.','Discipline');

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

		$chart3 = $this->get('Commonservices')->barChartLine('container3','Number Comments per Phase', $sub1 = "Comments", $sub2 = "Comments", "Bar Chart", "Line Chart", $chart3_bar_data, $chart3_bar_label);

        return $this->render('reports/viewreport.html.twig', [
        	'dotID' => $dotID,
        	'logo' => $logo,
        	'postvars' => $postvars,
        	'chart1' => $chart1,
        	'chart2' => $chart2,
        	'chart3' => $chart3,
        ]);
    }

	private function report_graphs_category($projects) {
		$em = $this->getDoctrine()->getManager();

		$sql = "
		SELECT
			`r`.`reviewID`

		FROM
			`projects` p,
			`review` r

		WHERE
			`p`.`id` IN ($projects)
			AND `p`.`id` = `r`.`projectID`
		";
		$review = array();
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
			$review[] = $row['reviewID'];
		}

		$data = array();
		$data['data1'] = array();
		$data['data2'] = array();

		if(is_array($review)) {
			foreach ($review as $k => $v) {
				$series = "1";
				$sql2 = "SELECT DISTINCT `series` FROM `xml_data` WHERE 
				`reviewID` = '$v' ORDER BY `series` DESC LIMIT 1";
		        $result2 = $em->getConnection()->prepare($sql2);
		        $result2->execute();
		        while ($row2 = $result2->fetch()) {
					$series = $row2['series'];
				}

				$sql2 = "SELECT `Category`,`Comment_Type` FROM `xml_data` WHERE `reviewID` = '$v' AND `series` = '$series'";
		        $result2 = $em->getConnection()->prepare($sql2);
		        $result2->execute();
		        while ($row2 = $result2->fetch()) {
					$category = $row2['Category'];
					$type = $row2['Comment_Type'];
					$data['data1'][$category] = 0;
					$data['data2'][$category][$type] = 0;
				}

		        $result2 = $em->getConnection()->prepare($sql2);
		        $result2->execute();
		        while ($row2 = $result2->fetch()) {
					$category = $row2['Category'];
					$type = $row2['Comment_Type'];
					$data['data1'][$category]++;
					$data['data2'][$category][$type]++;
				}
			}
		}
		return($data);
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

	private function report_graph_avg_comments($projects)
	{
		$em = $this->getDoctrine()->getManager();

		$sql = "
		SELECT
			`r`.`review_type`,
			COUNT(`r`.`review_type`) AS 'avg_review_type'

		FROM
			`xml_data` x

		INNER JOIN `review` r ON `x`.`reviewID` = `r`.`reviewID`

		WHERE
			`x`.`projectID` IN ($projects)

		GROUP BY `r`.`review_type`
		";

		$data = array();
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        $i = "0";
        while ($row = $result->fetch()) {
        	foreach ($row as $key => $value) {
        		$data[$i][$key] = $value;
        	}
        	$i++;
        }
        return ($data);
	}

	private function report_graphs_discipline($projects) {
		$em = $this->getDoctrine()->getManager();

		$sql = "
		SELECT
			`r`.`reviewID`

		FROM
			`projects` p,
			`review` r

		WHERE
			`p`.`id` IN ($projects)
			AND `p`.`id` = `r`.`projectID`
		";
		$review = array();
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
        	$review[] = $row['reviewID'];
        }

		$data = array();
		$data['data1'] = array();
		$data['data2'] = array();

		if(is_array($review)) {
			foreach ($review as $k => $v) {
				$series = "1";
				$sql2 = "SELECT DISTINCT `series` FROM `xml_data` WHERE 
				`reviewID` = '$v' ORDER BY `series` DESC LIMIT 1";
		        $result2 = $em->getConnection()->prepare($sql2);
		        $result2->execute();
		        while ($row2 = $result2->fetch()) {
					$series = $row2['series'];
				}

				$sql2 = "SELECT `Discipline`,`Category` FROM `xml_data` WHERE `reviewID` = '$v' AND `series` = '$series'";
		        $result2 = $em->getConnection()->prepare($sql2);
		        $result2->execute();
		        while ($row2 = $result2->fetch()) {
					$category = $row2['Discipline'];
					$type = $row2['Category'];
					$data['data1'][$category] = 0;
					$data['data2'][$category][$type] = 0;
				}

		        $result2 = $em->getConnection()->prepare($sql2);
		        $result2->execute();
		        while ($row2 = $result2->fetch()) {
					$category = $row2['Discipline'];
					$type = $row2['Category'];
					$data['data1'][$category]++;
					$data['data2'][$category][$type]++;
				}
			}
		}
		return($data);
	}

}