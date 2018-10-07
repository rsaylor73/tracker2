<?php
/* This is a service class */
namespace AppBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use \Datetime;

class Commonservices extends Controller
{
	
	protected $em;
	protected $container;
	protected $mailer;

	public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container, \Swift_Mailer $mailer)
	{
		$this->em = $entityManager;
		$this->container = $container;
		$this->mailer = $mailer;
	}

	public function getsessiondata()
	{
		$session = new Session();
		$em = $this->em;
		$container = $this->container;
		$username = $container->get('security.token_storage')->getToken()->getUser();
		$sql = "SELECT `id`,`userType` FROM `user` WHERE `username_canonical` = '$username'";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
        	$session->set('id', $row['id']);
        	$session->set('userType', $row['userType']);
        }
		//$session->start();
		return($session);
	}

	public function checklogin()
	{
		/**
		 * This will redirect to the login
		 * if the user is not logged in.
		 * This is still TBD
		 */
		return $this->redirectToRoute('userlogin');
	}


	/**
	 * This returns a data set to
	 * be used in a graph
	 */
	public function clientDashBoardGraphsCategory($dotID, $year1 = '', $year2 = '')
	{
		$em = $this->em;
		if ($year1 == "") {
			$year1 = date("Y");
			$year2 = date("Y");
		}
		$sql = "
		SELECT
			`r`.`reviewID`

		FROM
			`projects` p,
			`review` r

		WHERE
			`p`.`dotID` = '$dotID'
			AND `p`.`id` = `r`.`projectID`
			AND DATE_FORMAT(`r`.`date_completed`,'%Y') BETWEEN '$year1' AND '$year2'
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

		if (is_array($review)) {
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
				$category = "";
				$type = "";
				$result2 = $em->getConnection()->prepare($sql2);
				$result2->execute();

				$test = "0";
				$cat_test = "";
				while ($row2 = $result2->fetch()) {
					$category = $row2['Category'];
					if (($cat_test != $category) && ($cat_test != "")) {
						$data['data1'][$category] = "0";
						$cat_test = $category;
					}
					if ($cat_test == "") {
						$data['data1'][$category] = "0";
						$cat_test = $category;
					}

					$test = $data['data1'][$category];
					if ($test == "") {
						$test = "1";
						$data['data1'][$category] = $test;
					} else {
						$test = $test + 1;
						$data['data1'][$category] = $test;
					}
					$type = $row2['Comment_Type'];
					$data['data2'][$category][$type] = "0";
				}

				$result2 = $em->getConnection()->prepare($sql2);
				$result2->execute();
				$test = 0;
				while ($row2 = $result2->fetch()) {
					$category = $row2['Category'];
					$type = $row2['Comment_Type'];
					$test = $data['data2'][$category][$type];
					if ($test == "") {
						$test = "1";
						$data['data2'][$category][$type] = $test;
					} else {
						$test = $test + 1;
						$data['data2'][$category][$type] = $test;
					}
				}
			}
		}
		return($data);
	}

	public function clientDashboardGraphsDiscipline($dotID, $year1 = '', $year2 = '')
	{
		$em = $this->em;
		if ($year1 == "") {
			$year1 = date("Y");
			$year2 = date("Y");
		}
		$sql = "
		SELECT
			`r`.`reviewID`

		FROM
			`projects` p,
			`review` r

		WHERE
			`p`.`dotID` = '$dotID'
			AND `p`.`id` = `r`.`projectID`
			AND DATE_FORMAT(`r`.`date_completed`,'%Y') BETWEEN '$year1' AND '$year2'
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

		if (is_array($review)) {
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
				$category = "";
				$type = "";
				$result2 = $em->getConnection()->prepare($sql2);
				$result2->execute();

				$test = "0";
				$cat_test = "";
				while ($row2 = $result2->fetch()) {
					$category = $row2['Discipline'];
					if (($cat_test != $category) && ($cat_test != "")) {
						$data['data1'][$category] = "0";
						$cat_test = $category;
					}
					if ($cat_test == "") {
						$data['data1'][$category] = "0";
						$cat_test = $category;
					}

					$test = $data['data1'][$category];
					if ($test == "") {
						$test = "1";
						$data['data1'][$category] = $test;
					} else {
						$test = $test + 1;
						$data['data1'][$category] = $test;
					}
					//$type = $row2['Discipline'];
					$type = $row2['Category'];
					$data['data2'][$category][$type] = "0";
				}

				$result2 = $em->getConnection()->prepare($sql2);
				$result2->execute();
				$test = 0;
				while ($row2 = $result2->fetch()) {
					$category = $row2['Discipline'];
					//$type = $row2['Discipline'];
					$type = $row2['Category'];
					$test = $data['data2'][$category][$type];
					if ($test == "") {
						$test = "1";
						$data['data2'][$category][$type] = $test;
					} else {
						$test = $test + 1;
						$data['data2'][$category][$type] = $test;
					}
				}
			}
		}
		return($data);
	}

	public function barChartLine($container, $title, $sub1, $sub2, $name1, $name2, $chart3_bar_data, $chart3_bar_label)
	{
		$bar_line = "
		<script>
		var chart1; // globally available
		$(function() {
			chart1 =
			Highcharts.chart('$container', {
		    chart: {
		        zoomType: 'xy'
		    },
		    credits: {
		        enabled: false
		    },
		    title: {
		        text: '$title'
		    },
		    xAxis: [{
		        categories: [$chart3_bar_label],
		        crosshair: true
		    }],
		    yAxis: [{ // Primary yAxis
		        labels: {
		            format: '{value} ',
		            style: {
		                color: Highcharts.getOptions().colors[1]
		            }
		        },
		        title: {
		            text: '$sub1',
		            style: {
		                color: Highcharts.getOptions().colors[1]
		            }
		        }
		    }, { // Secondary yAxis
		        title: {
		            text: '$sub2',
		            style: {
		                color: Highcharts.getOptions().colors[0]
		            }
		        },
		        labels: {
		            format: '{value} ',
		            style: {
		                color: Highcharts.getOptions().colors[0]
		            }
		        },
		        opposite: true
		    }],
		    tooltip: {
		        shared: true
		    },
		    legend: {
		        layout: 'vertical',
		        align: 'left',
		        x: 120,
		        verticalAlign: 'top',
		        y: 100,
		        floating: true,
		        backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'
		    },
		    series: [{
		        name: '$name1',
		        type: 'column',
		        yAxis: 1,
		        data: [$chart3_bar_data],
		        tooltip: {
		            valueSuffix: ' '
		        }

		    }, {
		        name: '$name2',
		        type: 'spline',
		        data: [$chart3_bar_data],
		        tooltip: {
		            valueSuffix: ' '
		        }
		    }]
			});
		});
		</script>
		";
		return ($bar_line);
	}

	public function pieChartDrill($id, $data1, $data2, $title, $title2, $label)
	{
		$pie_chart = "
		<script>
		Highcharts.setOptions({
     	colors: [
     		'#50B432', 
     		'#ED561B', 
     		'#DDDF00', 
     		'#24CBE5', 
     		'#64E572', 
     		'#FF9655', 
     		'#FFF263',
     		'#6AF9C4',
     		'#0026ff'
     		]
    	});
		var chart1; // globally available
		$(function() {
			chart1 =

			Highcharts.chart('$id', {
				chart: {
					type: 'pie'
				},
				title: {
					text: '$title'
				},
			    credits: {
			        enabled: false
			    },
				subtitle: {
					text: '$title2'
				},
				plotOptions: {
					series: {
						dataLabels: {
							enabled: true,
							format: '{point.name}: {point.y:.1f}%'
						}
					}
				},

				tooltip: {
					headerFormat: '<span style=\"font-size:11px\">{series.name}</span><br>',
					pointFormat: '<span style=\"color:{point.color}\">{point.name}</span>: <b>{point.y:.2f}%</b> of total<br/>'
				},
				series: [{
					name: '$label',
					colorByPoint: true,
					data: [
					$data1
					]
				}],
				drilldown: {
					series: [
					$data2
					]
				}
			});
		});
		</script>
		";
		return($pie_chart);
	}

	public function savingsOpportunities($dotID, $year1, $year2)
	{
		$em = $this->em;
		if ($year1 == "") {
			$year1 = date("Y");
			$year2 = date("Y");
		}
		$total = "0";
		$sql = "
		SELECT
			COUNT(`x`.`id`) AS 'total'
		FROM
			`projects` p,
			`xml_data` x,
			`review` r

		WHERE
			`p`.`dotID` = '$dotID'
			AND `p`.`id` = `x`.`projectID`
			AND `p`.`id` = `r`.`projectID`
			AND DATE_FORMAT(`r`.`date_completed`,'%Y') BETWEEN '$year1' AND '$year2'
			AND `x`.`Category` != 'Quality'
			AND `x`.`Importance` != 'Minor'
		";
		$result = $em->getConnection()->prepare($sql);
		$result->execute();
		while ($row = $result->fetch()) {
			$total = floor($row['total'] * .90);
		}
		return($total);
	}

	public function comboChart($dotID, $year1 = '', $year2 = '')
	{
		switch ($dotID) {
			case "1":
				$title = "Total Number of Comments by Project Phase and District";
				break;
			default:
				$title = "Total Number of Comments by Project Phase and Region";
				break;
		}

		$em = $this->em;
		if ($year1 == "") {
			$year1 = date("Y");
			$year2 = date("Y");
		}
		$sql = "
		SELECT
			`r`.`reviewID`,
			`s`.`Description`

		FROM
			`projects` p,
			`review` r,
			`SubmittalTypes` s

		WHERE
			`p`.`dotID` = '$dotID'
			AND `p`.`id` = `r`.`projectID`
			AND DATE_FORMAT(`r`.`date_completed`,'%Y') BETWEEN '$year1' AND '$year2'
			AND `r`.`project_phase` = `s`.`id`

		ORDER BY `s`.`numbering` ASC
		";
		$category = "";
		$review = array();
		$Description = array();
		$data1 = "";
		$data4 = "";
		$cat = "";
		$avg_1 = "0";
		$avg_2 = "0";
		$avg_3 = "0";
		$avg_4 = "0";
		$avg_5 = "0";
		$avg_6 = "0";
		$avg_7 = "0";
		$avg_8 = "0";
		$avg_9 = "0";
		$avg_10 = "0";
		$avg_1_c = "0";
		$avg_2_c = "0";
		$avg_3_c = "0";
		$avg_4_c = "0";
		$avg_5_c = "0";
		$avg_6_c = "0";
		$avg_7_c = "0";
		$avg_8_c = "0";
		$avg_9_c = "0";
		$avg_10_c = "0";

		$result = $em->getConnection()->prepare($sql);
		$result->execute();
		while ($row = $result->fetch()) {
			if ($category != $row['Description']) {
				$cat .= "'".$row['Description']."',";
				$Description[] = $row['Description'];
				$category = $row['Description'];
			}
			$review[] = $row['reviewID'];
		}
		$cat = substr($cat, 0, -1);
		$total_cats = count($Description);

		$combo = "
		<script>
		var chart1; // globally available
		$(function() {
			chart1 =
			Highcharts.chart('container3', {
				title: {
					text: '".$title."'
				},
			    credits: {
			        enabled: false
			    },
				xAxis: {
					categories: [".$cat."]
				},
				yAxis: {
					title: {
						text: 'Number of Comments'
					}
				},
				labels: {
					items: [{
						html: 'Total Comments by Region/District',
						style: {
							left: '50px',
							top: '18px',
							color: (Highcharts.theme && Highcharts.theme.textColor) || 'black'
						}
					}]
				},
				series: [
				";
				$y = "0";
				$sql2 = "
				SELECT
					`r`.`name` AS 'region_name'
				FROM
					`region` r,
					`state` s,
					`dots` d
				
				WHERE
					`r`.`category` = `s`.`state`
					AND `s`.`state_id` = `d`.`stateID`
					AND `d`.`id` = '$dotID'

				ORDER BY `region_name` ASC
				";
				$result2 = $em->getConnection()->prepare($sql2);
				$result2->execute();
				while ($row2 = $result2->fetch()) {
					$avg1 = "0";
					$avg2 = "0";
					$data1 .= "{type: 'column',name: '$row2[region_name]',";
					// look up each cat...
					$r = ""; // init

					// We loop here to assign the arrays
					$region_array = array();
					foreach ($Description as $key => $value) {
						$region_name = $row2['region_name'];
						$region_array[$region_name] = array();
					}

					foreach ($Description as $key => $value) {
						$total = "0";
						$sql3 = "
						SELECT
							COUNT(`x`.`Comments`) AS 'total',
							`s`.`Description`

						FROM
							`xml_data` x,
							`review` r,
							`SubmittalTypes` s,
							`projects` p,
							`region` rg

						WHERE
							`x`.`reviewID` = `r`.`reviewID`
							AND `r`.`project_phase` = `s`.`id`
							AND `s`.`Description` = '$value'
							AND DATE_FORMAT(`r`.`date_completed`, '%Y') BETWEEN '$year1' AND '$year2'
							AND `x`.`projectID` = `p`.`id`
							AND `p`.`regionID` = `rg`.`id`
							AND `rg`.`name` = '$row2[region_name]'

						GROUP BY `s`.`Description`
						";

						$result3 = $em->getConnection()->prepare($sql3);
						$result3->execute();
						while ($row3 = $result3->fetch()) {
							$total = $row3['total'];
						}
						$r .= $total . ",";

						$region_name = $row2['region_name'];
						$region_array[$region_name][] = $total;
					}

					$r = substr($r,0,-1);
					$data1 .= "data: [".$r."]},";
				}

				$combo .= $data1;

				foreach ($region_array as $key => $value) {
					foreach ($value as $key2 => $value2) {
						switch ($key2) {
							case "0":
								if ($value2 > 0) {
									$avg_1 = $avg_1 + $value2;
									$avg_1_c++;
								}
								break;

							case "1":
								if ($value2 > 0) {
									$avg_2 = $avg_2 + $value2;
									$avg_2_c++;
								}
								break;

							case "2":
								if ($value2 > 0) {
									$avg_3 = $avg_3 + $value2;
									$avg_3_c++;
								}
								break;

							case "3":
								if ($value2 > 0) {
									$avg_4 = $avg_4 + $value2;
									$avg_4_c++;
								}
								break;

							case "4":
								if ($value2 > 0) {
									$avg_5 = $avg_5 + $value2;
									$avg_5_c++;
								}
								break;

							case "5":
								if ($value2 > 0) {
									$avg_6 = $avg_6 + $value2;
									$avg_6_c++;
								}
								break;

							case "6":
								if ($value2 > 0) {
									$avg_7 = $avg_7 + $value2;
									$avg_7_c++;
								}
								break;

							case "7":
								if ($value2 > 0) {
									$avg_8 = $avg_8 + $value2;
									$avg_8_c++;
								}
								break;

							case "8":
								if ($value2 > 0) {
									$avg_9 = $avg_9 + $value9;
									$avg_9_c++;
								}
								break;

							case "9":
								if ($value2 > 0) {
									$avg_10 = $avg_10 + $value2;
									$avg_10_c++;
								}
								break;
						}
					}
				}

				$average_data = ""; // init
				if ($avg_1 > 0) {
					$avg_1_v = $avg_1 / $avg_1_c;
					$average_data .= "$avg_1_v,";
				}
				if ($avg_2 > 0) {
					$avg_2_v = $avg_2 / $avg_2_c;
					$average_data .= "$avg_2_v,";
				}
				if ($avg_3 > 0) {
					$avg_3_v = $avg_3 / $avg_3_c;
					$average_data .= "$avg_3_v,";
				}
				if ($avg_4 > 0) {
					$avg_4_v = $avg_4 / $avg_4_c;
					$average_data .= "$avg_4_v,";
				}
				if ($avg_5 > 0) {
					$avg_5_v = $avg_5 / $avg_5_c;
					$average_data .= "$avg_5_v,";
				}
				if ($avg_6 > 0) {
					$avg_6_v = $avg_6 / $avg_6_c;
					$average_data .= "$avg_6_v,";
				}
				if ($avg_7 > 0) {
					$avg_7_v = $avg_7 / $avg_7_c;
					$average_data .= "$avg_7_v,";
				}
				if ($avg_8 > 0) {
					$avg_8_v = $avg_8 / $avg_8_c;
					$average_data .= "$avg_8_v,";
				}
				if ($avg_9 > 0) {
					$avg_9_v = $avg_9 / $avg_9_c;
					$average_data .= "$avg_9_v,";
				}
				if ($avg_10 > 0) {
					$avg_10_v = $avg_10 / $avg_10_c;
					$average_data .= "$avg_10_v,";
				}
				$average_data = substr($average_data,0,-1);

				$combo .= "
				{
					type: 'spline',
					name: 'Average',
					data: [$average_data],
					marker: {
						lineWidth: 2,
						lineColor: Highcharts.getOptions().colors[3],
						fillColor: 'white'
					}
				}, 

				{
					type: 'pie',
					name: 'Total Comments',
					data: [
					";

					foreach ($region_array as $key => $value) {
						$t = $key;
						$t2 = array_sum($region_array[$t]);
						$data4 .= "{name: '$key',y:$t2},";
					}
					$data4 = substr($data4,0,-1);
					$combo .= $data4;
					$combo .= "

					],
					center: [100, 80],
					size: 100,
					showInLegend: false,
					dataLabels: {
						enabled: false
					}
				}]
			});
		});
		</script>
		";
		return($combo);
	}

	public function gauge($container,$title,$value) {
		$chart = "
		<script>
		var chart1; // globally available
		$(function() {
		chart1 = Highcharts.chart('$container', {

			chart: {
				type: 'gauge',
				plotBackgroundColor: null,
				plotBackgroundImage: null,
				plotBorderWidth: 0,
				plotShadow: false
			},

			title: {
				text: '$title'
			},
		    credits: {
		        enabled: false
		    },
			pane: {
				startAngle: -150,
				endAngle: 150,
				background: [{
					backgroundColor: {
						linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
						stops: [
							[0, '#FFF'],
							[1, '#333']
						]
					},
					borderWidth: 0,
					outerRadius: '109%'
				}, {
					backgroundColor: {
						linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
						stops: [
							[0, '#333'],
							[1, '#FFF']
						]
					},
					borderWidth: 1,
					outerRadius: '107%'
				}, {
					// default background
				}, {
					backgroundColor: '#DDD',
					borderWidth: 0,
					outerRadius: '105%',
					innerRadius: '103%'
				}]
			},

			// the value axis
			yAxis: {
				min: 0,
				max: 200,

				minorTickInterval: 'auto',
				minorTickWidth: 1,
				minorTickLength: 10,
				minorTickPosition: 'inside',
				minorTickColor: '#666',

				tickPixelInterval: 30,
				tickWidth: 2,
				tickPosition: 'inside',
				tickLength: 10,
				tickColor: '#666',
				labels: {
					step: 2,
					rotation: 'auto'
				},
				title: {
					text: '' // Reviews
				},
				plotBands: [{
					from: 0,
					to: 120,
					color: '#55BF3B' // green
				}, {
					from: 120,
					to: 160,
					color: '#DDDF0D' // yellow
				}, {
					from: 160,
					to: 200,
					color: '#DF5353' // red
				}]
			},

			series: [{
				name: 'Completed',
				data: [$value],
				tooltip: {
					valueSuffix: ' Reviews'
				}
			}]

		});

		});
		</script>
		";
		return($chart);
	}

    public function getProjectTypes($id = '')
    {
        $em = $this->em;

        $sql = "
        SELECT
        `id`,`project_type`
        FROM
        `project_type`
        WHERE
        1
        ORDER BY `project_type` ASC
        ";
        $option = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            if ($row['id'] == $id) {
                $option .= "<option selected value=\"$row[id]\">$row[project_type]</option>";
            } else {
                $option .= "<option value=\"$row[id]\">$row[project_type]</option>";
            }
        }
        return($option);
    }

    public function getRegion($id = '', $state = '')
    {
        $em = $this->em;

        $sql = "
		SELECT
			`id`,`name`,`category`
		FROM
			`region`
		WHERE
			1
			AND `category` = '$state'
		ORDER BY `category` ASC,`name` ASC
		";
        $option = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            if ($row['id'] == $id) {
                $option .= "<option selected value=\"$row[id]\">$row[name]</option>";
            } else {
                $option .= "<option value=\"$row[id]\">$row[name]</option>";
            }
        }
        return($option);
    }

    public function getSubmittalTypes($id='', $state='')
    {
        $em = $this->em;
        $option = "";
        $sql = "
		SELECT
			`id`,`Description`,`category`
		FROM
			`SubmittalTypes` s

		WHERE
			1
			AND `category` = '$state'

		ORDER BY `category` ASC, `Description` ASC
		";
        $category = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            if ($category != $row['category']) {
                if ($category != "") {
                    $category = $row['category'];
                }
            }
            if ($row['id'] == $id) {
                $option .= "<option selected value=\"$row[id]\">$row[Description]</option>";
            } else {
                $option .= "<option value=\"$row[id]\">$row[Description]</option>";
            }
        }
        return($option);
    }

	public function viewData($reviewID, $projectID, $search = '') {
        $em = $this->em;
        // get latest series #
        $data = array();
        $series = "";
        $sql = "
        SELECT 
        DISTINCT `series` 

        FROM `xml_data` 

        WHERE 
        `projectID` = '$projectID' 
        AND `reviewID` = '$reviewID' 

        ORDER BY `series` DESC LIMIT 1
        ";

        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $series = $row['series'];
        }

        if ($series != "") {
            $sql = "
            SELECT
            `d`.`id`,
            `d`.`Page_Label`,
            `d`.`Author`,
            `d`.`Comments`,
            `d`.`Category`,
            `d`.`Comment_Type`,
            `d`.`Discipline`,
            `d`.`Importance`,
            `d`.`Cost_Reduction`

            FROM
            `xml_data` d

            WHERE
            `d`.`projectID` = '$projectID'
            AND `d`.`reviewID` = '$reviewID'
            AND `d`.`series` = '$series'
            ";
            $result = $em->getConnection()->prepare($sql);
            $result->execute();
            $i = "0";
            while ($row = $result->fetch()) {
                foreach ($row as $key => $value) {
                    $i = $row['id'];
                    $data[$i][$key] = $value;
                }
            }
        }
        return($data);
    }

    public function generateStackedData($projectID, $reviewID)
    {
        $em = $this->em;
        $sql = "SELECT DISTINCT `series` FROM `xml_data` WHERE `projectID` = '$projectID' 
        AND `reviewID` = '$reviewID' ORDER BY `series` DESC LIMIT 1";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        $i = "0";
        while ($row = $result->fetch()) {
            $series = $row['series'];
        }    	

        // Chart Category
        $sql = "
        SELECT
    		COUNT(`Category`) AS 'CategoryValue',
    		`Category`

        FROM
			`xml_data`

        WHERE
            `projectID` = '$projectID'
            AND `reviewID` = '$reviewID'
            AND `series` = '$series'
            AND `Category` != ''
            AND `Discipline` != ''

        GROUP BY `Category`

        ORDER BY `Category` ASC
        ";

        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        $chart_category = "";

        while ($row = $result->fetch()) {
            $chart_category .= "'".$row['Category']."',";
        }
        $chart_category = substr($chart_category,0,-1);

        // Chart Discipline
        $sql = "
        SELECT
    		COUNT(`Discipline`) AS 'DisciplineValue',
    		`Discipline`,
    		COUNT(`Category`) AS 'CategoryValue',
    		`Category`

        FROM
			`xml_data`

        WHERE
            `projectID` = '$projectID'
            AND `reviewID` = '$reviewID'
            AND `series` = '$series'
            AND `Category` != ''
            AND `Discipline` != ''

        GROUP BY `Discipline`, `Category`

        ORDER BY `Discipline` ASC, `Category` ASC
        ";

        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        $chart_data = "";
        $value = "";

        while ($row = $result->fetch()) {
			$value = substr($value,0,-1);
			$chart_data .= "{name:'".$row['Discipline']."',data: [".$row['DisciplineValue']."]},";
        }
        $chart_data = substr($chart_data,0,-1);

        $data = array();
        $data['chart_data'] = $chart_data;
        $data['chart_category'] = $chart_category;
        return($data);
    }

    public function stackedColumn($container, $category, $title, $title2, $chart_data)
    {
        $stacked = "
        <script>
        var chart1; // globally available
        $(function() {
            chart1 = Highcharts.chart('".$container."', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: '".$title."'
                },
			    credits: {
			        enabled: false
			    },
                xAxis: {
                    categories: [".$category."]
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: '".$title2."'
                    },
                    stackLabels: {
                        enabled: true,
                        style: {
                            fontWeight: 'bold',
                            color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
                        }
                    }
                },

                tooltip: {
                    headerFormat: '<b>{point.x}</b><br/>',
                    pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
                },
                plotOptions: {
                    column: {
                        stacking: 'normal',
                        dataLabels: {
                            enabled: true,
                            color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white'
                        }
                    }
                },
                series: [

                ".$chart_data."

                ]
            });
        });
        </script>
        ";
        return($stacked);
    }

    public function generatePieData($projectID, $reviewID, $key)
    {
        $em = $this->em;
        $sql = "SELECT DISTINCT `series` FROM `xml_data` WHERE `projectID` = '$projectID' 
        AND `reviewID` = '$reviewID' ORDER BY `series` DESC LIMIT 1";
        $series = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $series = $row['series'];
        }

        // get category data:
        $sql = "
        SELECT
            DISTINCT `x`.`$key`
        
        FROM
            `xml_data` x

        WHERE
            `x`.`projectID` = '$projectID'
            AND `x`.`reviewID` = '$reviewID'
            AND `x`.`series` = '$series'
            AND `x`.`Category` != ''
        ";
        $cat_sql = "";
        $categories = array();
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $cat_sql .= "COUNT(CASE WHEN `$key` = '".$row[$key]."' 
            THEN `$key` END) AS '".$row[$key]."',";
            $categories[] = $row[$key];
        }
        $cat_sql = substr($cat_sql, 0, -1);

        $sql = "
        SELECT
            COUNT(`x`.`$key`) AS 'total',
            $cat_sql

        FROM
            `xml_data` x

        WHERE
            `x`.`projectID` = '$projectID'
            AND `x`.`reviewID` = '$reviewID'
            AND `x`.`series` = '$series'
            AND `x`.`Category` != ''
        ";
        $chart_data = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            foreach ($categories as $key => $value) {
                $chart_data .= "{name: '".$value."',y: ".$row[$value]."},";
            }
        }
        $chart_data = substr($chart_data, 0, -1);
        return($chart_data);
    }

    /**
    * $id = distinct name for the chart no spaces
    * $data = json data {name: value},{name2: $value2}
    * $title = Chart Title
    */
    public function pieChart($container, $title, $data)
    {
        $pie_chart = "
        <script>
		Highcharts.setOptions({
     	colors: [
     		'#50B432', 
     		'#ED561B', 
     		'#DDDF00', 
     		'#24CBE5', 
     		'#64E572', 
     		'#FF9655', 
     		'#FFF263',
     		'#6AF9C4',
     		'#0026ff'
     		]
    	});
        var chart1; // globally available
        $(function() {
            chart1 = Highcharts.chart('$container', {
	            chart: {
	                plotBackgroundColor: null,
	                plotBorderWidth: null,
	                plotShadow: false,
	                type: 'pie'
	            },
	            title: {
	                text: '$title'
	            },
			    credits: {
			        enabled: false
			    },
	            tooltip: {
	                pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
	            },
	            plotOptions: {
	                pie: {
	                    allowPointSelect: true,
	                    cursor: 'pointer',
	                    dataLabels: {
	                        enabled: true,
	                        format: '<b>{point.name}</b>: {point.percentage:.1f} %',
	                        style: {
	                            color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
	                        }
	                    }
	                }
	            },
	            series: [{
	                name: 'UPDATE name?',
	                colorByPoint: true,
	                data: [
	                ";
	                $pie_chart .= $data;
	                $pie_chart .= "
	                ]
	            }]
	        });
    	});
        </script>
        ";
        return($pie_chart);
    }

    public function generatePieDataSpecial($projectID, $reviewID, $key, $key2, $search)
    {
        $em = $this->em;
        $chart_data = "";
        $sql = "SELECT DISTINCT `series` FROM `xml_data` WHERE `projectID` = '$projectID' 
		AND `reviewID` = '$reviewID' ORDER BY `series` DESC LIMIT 1";
        $series = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $series = $row['series'];
        }

        $found = "0";
        // get category data:
        $sql = "
		SELECT
			DISTINCT `x`.`$key`
		
		FROM
			`xml_data` x

		WHERE
			`x`.`projectID` = '$projectID'
			AND `x`.`reviewID` = '$reviewID'
			AND `x`.`series` = '$series'
			AND `x`.`Category` != ''
			AND `x`.`$key2` = '$search'
		";
        $cat_sql = "";
        $categories = array();
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $cat_sql .= "COUNT(CASE WHEN `$key` = '".$row[$key]."' 
			THEN `$key` END) AS '".$row[$key]."',";
            $categories[] = $row[$key];
            $found = "1";
        }
        $cat_sql = substr($cat_sql, 0, -1);

        $sql = "
		SELECT
			COUNT(`x`.`$key`) AS 'total',
			$cat_sql

		FROM
			`xml_data` x

		WHERE
			`x`.`projectID` = '$projectID'
			AND `x`.`reviewID` = '$reviewID'
			AND `x`.`series` = '$series'
			AND `x`.`Category` != ''
			AND `x`.`$key2` = '$search'
		";
        if ($found == "1") {
            $result = $em->getConnection()->prepare($sql);
            $result->execute();
            $chart_data = "";
            while ($row = $result->fetch()) {
                foreach ($categories as $key => $value) {
                    $chart_data .= "{name: '".$value."',y: ".$row[$value]."},";
                }
            }
            $chart_data = substr($chart_data, 0, -1);
        }
        return($chart_data);
    }

    public function processXml($reviewID, $projectID, $xml_file)
    {
        $em = $this->em;
        $series = "1"; // part of original design. The value is always 1

        $sql = "DELETE FROM `xml_data` WHERE `projectID` = '$projectID' AND `reviewID` = '$reviewID'";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();

        $Page_Label = "";
        $Author = "";
        $Comments = "";
        $Category = "";
        $Comment_Type = "";
        $Discipline = "";
        $Importance = "";
        $Cost_Reduction = "";

        $xml=simplexml_load_file($xml_file);
        foreach ($xml->Markup as $dot) {
            $Page_Label = $dot->Page_Label;
            $Author = $dot->Author;
            $Comments = $dot->Comments;
            $Category = $dot->Category;
            $Comment_Type = $dot->Comment_Type;
            $Discipline = $dot->Discipline;
            $Importance = $dot->Importance;
            $Cost_Reduction = $dot->Cost_Reduction;

            $sql = "INSERT INTO `xml_data` 
            (`projectID`,`reviewID`,`series`,
            `Page_Label`,`Author`,`Category`,`Comments`,
            `Comment_Type`,`Discipline`,`Importance`,`Cost_Reduction`)
            VALUES
            ('$projectID','$reviewID','$series',
            ?,?,?,?,?,?,?,?)
            ";
            $result = $em->getConnection()->prepare($sql);
            $result->bindValue(1, $Page_Label);
            $result->bindValue(2, $Author);
            $result->bindValue(3, $Category);
            $result->bindValue(4, $Comments);
            $result->bindValue(5, $Comment_Type);
            $result->bindValue(6, $Discipline);
            $result->bindValue(7, $Importance);
            $result->bindValue(8, $Cost_Reduction);
            $result->execute();
        }
    }

	public function clientReportGraphsCategory($projects) {
		$em = $this->em;

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
					$data['data1'][$category] = "0";
					$data['data2'][$category][$type] = "0";
				}
		        $result2 = $em->getConnection()->prepare($sql2);
		        $result2->execute();
		        while ($row2 = $result2->fetch()) {
					$category = $row2['Category'];
					$type = $row2['Comment_Type'];
					$data['data1'][$category] = $data['data1'][$category] + 1;
					$data['data2'][$category][$type] = $data['data2'][$category][$type] + 1;
				}
			}
		}
		return($data);
	}

}
