<?php
require_once 'function.inc.php';

$recordingsNotFound =[];
switch ($_POST['Step']) {
	case '1':
		$file = myReadFile($_FILES);
		$params = array('step' => 1);
		$params += $file;
		echo(json_encode($params, JSON_UNESCAPED_UNICODE));

		// echo '<pre>';
		// 	echo 'Step 1 ';
		// echo '</pre>';
		break;
	case '2':
		$recordings = json_decode($_POST['recordings'], true);
		$tempRecordings = getBigData('crm.company.list');
		
		$order = array("\n\t", "\t", "  ", "   ");
		$replace = ' ';

		foreach ($recordings as &$row) {
			foreach ($tempRecordings as $rowtest) {
				$strTemp = str_replace($order, $replace, $rowtest['UF_CRM_1594794891']);
				$strTemp = trim($strTemp);
				if ($row[0] == (int)$strTemp) {	
					$row[2] = $rowtest['ID'];
					break;
				}				
			}
		}

		checkArr(2, $recordings, $recordingsNotFound);			// проверяем найден или нет ID магазина 

		$params = array('step' => 2);

		if (count($recordings) != 0) {
			$params += array('recordings' => $recordings);
		}

		if (count($recordingsNotFound) != 0) {
			$params += array( 'recordingsNotFound' => $recordingsNotFound);
		}
		
		echo(json_encode($params, JSON_UNESCAPED_UNICODE));
		
		// echo '<pre>';
		// 	echo 'Step 2 ';
		// 	print_r($tempRecordings
		// echo '</pre>';	
		break;
	case '3':
		$recordings = json_decode($_POST['recordings'], true);
		$noShipment = [];
		$dealList = getAllDeals('crm.deal.list');

		foreach ($recordings as &$row) {
			foreach ($dealList as &$rowtest) {
				if (strcasecmp($row[2], $rowtest['COMPANY_ID']) == 0) {
					$row[3] = $rowtest['ID'];
					$row[4] = $rowtest['STAGE_ID'];
					$rowtest[3] = 1;
					break;

				}			
			}
		}

		checkArr(3, $recordings, $recordingsNotFound);					// проверяем найдена или нет активная сделка в направлении Оплата за КГ по ID магазина

		$params = array('step' => 3);

		if (count($recordings) != 0) {
			$params += array('recordings' => $recordings);
		}

		if (count($recordingsNotFound) != 0) {
			$params += array( 'recordingsNotFound' => $recordingsNotFound);
		}
		
		echo(json_encode($params, JSON_UNESCAPED_UNICODE));

		// echo '<pre>';
		// 	echo 'Step 3 ';
		// 	echo 'recordings';
		// 	print_r($recordings);
		// 	echo 'recordingsNotFound';
		// 	print_r($recordingsNotFound);
		// echo '</pre>';
		break;
	case '4':
		$recordings = json_decode($_POST['recordings'], true);
		$temp = updateBalance('crm.deal.update', $recordings);
		// $temp = fastUpdateDeals($recordings);

		$params = array('step' => 4,'result' => 'finish', 'result' => $temp);
		echo(json_encode($params, JSON_UNESCAPED_UNICODE));

		// echo '<pre>';
		// 	echo 'Step 5 ';
		// 	print_r(count($recordings));
		// 	print_r($temp);
		// echo '</pre>';
		break;
	case '5':
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename = '.$_POST['name'].' c '.$_POST['first_day'].'po'.$_POST['last_day'].'.csv');

		$output = fopen("php://output", "w");

		$pattern = $_POST['pattern'];
		$name = $_POST['name'];
		$name = $name.'_data';
		$data = json_decode($_POST['data'], true);

		getCSV($data, $name, $output, $pattern);

		fclose($output);

		break;
	default:
		echo '<pre>';
			echo 'default';
		echo '</pre>';
		break;
}
?>