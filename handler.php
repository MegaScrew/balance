<?php
require_once 'function.inc.php';

switch ($_POST['Step']) {
	case '1':
		$file = myReadFile($_FILES);
		$params += $file;
		echo(json_encode($params, JSON_UNESCAPED_UNICODE));

		// echo '<pre>';
		// 	echo 'Step 1';
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
				if ($row[3] == (int)$strTemp) {	
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
		// 	echo 'Step 2';
		// echo '</pre>';	
		break;
	case '3':
		$recordings = json_decode($_POST['recordings'], true);
		$noShipment = [];
		$dealList = getAllDeals('crm.deal.list');

		foreach ($recordings as &$row) {
			foreach ($dealList as &$rowtest) {
				if (strcasecmp($row[2], $rowtest['COMPANY_ID']) == 0) {
					$row[1] = $rowtest['ID'];
					$row[0] = $rowtest['STAGE_ID'];
					$rowtest[3] = 1;
					break;

				}			
			}
		}

		checkArr2(3, $dealList, $noShipment);
		unset($noShipment);

		checkArr(1, $recordings, $recordingsNotFound);					// проверяем найдена или нет активная сделка в направлении Оплата за КГ по ID магазина

		$params = array('step' => 4);

		if (count($recordings) != 0) {
			$params += array('recordings' => $recordings);
		}

		if (count($recordingsNotFound) != 0) {
			$params += array( 'recordingsNotFound' => $recordingsNotFound);
		}
		
		if (count($dealList) != 0) {
			$params += array('noShipment' => $dealList);
		}

		echo(json_encode($params, JSON_UNESCAPED_UNICODE));

		// echo '<pre>';
		// 	echo 'Step 4';
		// echo '</pre>';
		// echo '<pre>';
			// print_r($recordings);
			// print_r($recordingsNotFound);
			// print_r($noShipment);
		// echo '</pre>';
		break;
	case '4':
		$recordings = json_decode($_POST['recordings'], true);
		$temp = issueAnInvoice('crm.deal.update', $recordings);

		$params = array('Step5' => 'finish');
		echo(json_encode($params, JSON_UNESCAPED_UNICODE));

		// echo '<pre>';
		// 	echo 'Step 5 ';
		// 	print_r(count($recordings));
		// 	print_r($temp);
		// echo '</pre>';
		break;
	default:
		echo '<pre>';
			echo 'default';
		echo '</pre>';
		break;
}
?>