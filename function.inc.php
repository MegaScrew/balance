<?php 
require_once 'crest.php';

/**
* Reading file data
* @param $file - array file from a web form
* @return array
*/
function myReadFile(array $file){	// функция чтения файла
	$recordings = [];
	$i = 0;
	$uploadfile = basename($file['uploaded_file']['name']);
	
	if (move_uploaded_file($file['uploaded_file']['tmp_name'], $uploadfile)) {
	    $result = "Файл был успешно загружен на сервер!";
	} else {
	    $result = "Возможная атака с помощью файловой загрузки!";
	    return array('Error' => $result);
	}

	if (($handle = fopen($uploadfile, "r")) !== FALSE) {
		while (($row = stream_get_line($handle, 1024 * 1024, "\n")) !== false) {
			$columns = explode(';', $row);                // разбиваем строку на две части по символу ;
			$recordings[$i][0] = $columns[0];
			$recordings[$i][1] = $columns[1];
			$recordings[$i][2] = 0;
			$recordings[$i][3] = 0;
			$recordings[$i][4] = 0;
			$i++;
		}

		fclose($handle);
	}
	unlink($uploadfile);

	return array('recordings' => $recordings);
}

/**
* searches for 0 in an array string and moves the found string to a new array before deleting it from the search array
* @param $number - number key in row array
* @param $auto - the array in which we will search
* @param $manual - the array to which we will transfer the found
* @return 0
*/
function checkArr(int $number, array &$auto, array &$manual){      // функция для проверки массива
    $i = 0;
    $arrSplice = [];
    foreach ($auto as &$row){             // перебираем массив что бы убедиться что все ID магазина найдены
        if ($row[$number] == 0) {
            array_push($manual, $row);    // если ID не найден то переносим магазин в ручной разбор
            array_push($arrSplice, $i);         // записываем индекс документа в котором нет ID  магазина   
        }
        $i++;
    }

    for ($i=0; $i < count($arrSplice); $i++) {  // в цикле перебираем и удаляем те документы по котором не смогли найти ID  магазина
        unset($auto[$arrSplice[$i]]);
    }
        
    $auto = array_values($auto);                // переиндексируем массив что бы индексы были по парядку без дыр на тот случай если захотим идти циклом по индексам 
}

/**
* searches for 1 in an array string and moves the found string to a new array before deleting it from the search array
* @param $number - number key in row array
* @param $auto - the array in which we will search
* @param $manual - the array to which we will transfer the found
* @return 0
*/
function checkArr2(int $number, array &$auto, array &$manual){      // функция для проверки массива
    $i = 0;
    $arrSplice = [];
    foreach ($auto as &$row){             // перебираем массив 
        if ($row[$number] != 0) {
            array_push($manual, $row);    
            array_push($arrSplice, $i);         // записываем индекс документа 
        }
        $i++;
    }

    for ($i=0; $i < count($arrSplice); $i++) {  // в цикле перебираем и удаляем  документы 
        unset($auto[$arrSplice[$i]]);
    }
        
    $auto = array_values($auto);                // переиндексируем массив что бы индексы были по парядку без дыр на тот случай если захотим идти циклом по индексам 
}

/**
* Get id everyone company
* @param $method - Rest API request method 
* @return array
*/
function getBigData(string $method = 'crm.company.list'){

    /***********************************************/
    $params = [
        'order'  => [
            'ID' => 'ASC'
        ],
        'filter' => [
            '>UF_CRM_1594794891' => 0    // Внутренний номер магазина боьше нуля
        ],
        'select' => [
            'ID',                              // ID магазина
            'UF_CRM_1594794891'                // Внутренний номер магазина
        ],  
        'start' => 0
    ];

    $result = CRest::call($method, $params); // Делаем запрос что бы понять сколько записей нам надо будет вытянуть
    while($result['error']=="QUERY_LIMIT_EXCEEDED"){
        sleep(1);
        $result = CRest::callBatch($arData);
        if ($result['error']<>"QUERY_LIMIT_EXCEEDED"){break;}
    } 
    $total = $result['total'];        // Всего записей в выборке
    $calls = ceil($total / 50);       // Сколько запросов надо сделать
    $current_call = 0;                // Номер текущего запроса
    $call_count = 0;                  // Счетчик вызовов для соблюдения условия не больше 2-х запросов в секунду

    sleep(1);                         // Делаем паузу перед основной работай  

    $arData = array();                // Массив для вызова callBatch
    $result = array();                // Массив для результатов вызова callBatch
    $totalResult = array();           // Массив с финальными данными

    /***********Цыкл формирования пакета запросов и выполнение их *********/
    do {
        $current_call++;

        $temp = [                                   // Собираем запрос
            'method' => $method,
            'params' => [
                'order'  => [
                    'ID' => 'ASC'
                ],
                'filter' => [
                    '>UF_CRM_1594794891' => 0    // Внутренний номер магазина боьше нуля
                ],
                'select' => [
                    'ID',                              // ID магазина
                    'UF_CRM_1594794891'                // Внутренний номер магазина
                ],  
                'start' => ($current_call - 1) * 50
            ]
        ];

        array_push($arData, $temp);                 // Сохраняем собранный запрос в массив параметров arData для передачи его в callBatch

        if ((count($arData) == 50) || ($current_call == $calls)) {  // Если в массиве параметров arData 50 запросов или это последний запрос
            
            $call_count++;                                      // При каждом вызове увеличиваем счетчик
            if ($call_count == 2) {                             // Проверяем счетчик вызовов call_count
                sleep(1);                                       // Если да то делаем паузу 1 сек
                $call_count = 0;                                // Сбрасываем счетчик
            }


            $result = CRest::callBatch($arData);                // Вызываем callBatch

            // echo '<pre>';
            //     print_r($result);
            // echo '</pre>';

            while($result['error']=="QUERY_LIMIT_EXCEEDED"){
                sleep(1);
                $result = CRest::callBatch($arData);
                if ($result['error']<>"QUERY_LIMIT_EXCEEDED"){break;}
            }
            // echo '<pre>';
            //     print_r($result);
            // echo '</pre>';
            $resultTemp = $result['result']['result'];          // Убираем лишнее вложение в массиве
            
            foreach ($resultTemp as $company){                  // Перебираем массив что бы 
                foreach ($company as $value) {                  // удобно было с ним работать в дальнейшем
                    array_push($totalResult, $value);           // и сохраняем каждый елемент в totalResult
                }            
            }
            $arData = [];                                       // Очишаем массив параметров arData для callBatch
        }
    } while ($current_call < $calls);                           // Проверяем условие что текущих вызовов меньще чем надо сделать всего


    return $totalResult;
}

/**
* Set new stage deals
* @param $method - Rest API request method 
* @return 0
*/
function updateBalance(string $method = 'crm.deal.update', array $arDeal){
    $total = count($arDeal);          // Всего записей в выборке
    $calls = $total;                  // Сколько запросов надо сделать
    $current_call = 0;                // Номер текущего запроса
    $call_count = 0;                  // Счетчик вызовов для соблюдения условия не больше 2-х запросов в секунду

    sleep(1);                         // Делаем паузу перед основной работай  

    $arData = array();                // Массив для вызова callBatch
    $result = array();                // Массив для результатов вызова callBatch
    $totalResultDeals = array();    // Массив всех выбранных магазинов

    /***********Цыкл формирования пакета запросов и выполнение их *********/
    do {
        $current_call++;

        $temp = [                                   // Собираем запрос
            'method' => $method,
            'params' => [
                'ID' => $arDeal[$current_call-1][3],        // ID сделки
                'fields' => [
                    'UF_CRM_1628167713' => $arDeal[$current_call-1][1],        // новый баланс
                ],
                'start' => -1
            ]
        ];

        array_push($arData, $temp);                 // Сохраняем собранный запрос в массив параметров arData для передачи его в callBatch

        if ((count($arData) == 50) || ($current_call == $calls)) {  // Если в массиве параметров arData 50 запросов или это последний запрос
            
            $call_count++;                                      // При каждом вызове увеличиваем счетчик
            if ($call_count == 3) {                             // Проверяем счетчик вызовов call_count
                sleep(1);                                       // Если да то делаем паузу 1 сек
                $call_count = 0;                                // Сбрасываем счетчик
            }

            // echo '<pre>';
            //     print_r($arData);
            // echo '</pre>';

            $result = CRest::callBatch($arData);                // Вызываем callBatch
            
            while($result['error']=="QUERY_LIMIT_EXCEEDED"){
                sleep(1);
                $result = CRest::callBatch($arData);
                if ($result['error']<>"QUERY_LIMIT_EXCEEDED"){break;}
            }

            $totalResultDeals = $result['result']['result'];          // Убираем лишнее вложение в массиве
            
            // echo '<pre>';
            //     print_r($totalResultDeals);
            // echo '</pre>';

            $arData = [];                                       // Очишаем массив параметров arData для callBatch
        }
    } while ($current_call < $calls);                           // Проверяем условие что текущих вызовов меньще чем надо сделать всего

    // стадии для проведения оплат
    // стало                                        было
    //4                     - Первичные             2
    //C2:6                  - Активные              C2:PREPAYMENT_INVOICE
    //C12:FINAL_INVOICE     - Оплата за КГ          C12:PREPAYMENT_INVOICE
    //C10:6                 - Склады                C10:4

    return $totalResultDeals;
}

/**
* Get list everyone deal
* @param $method - Rest API request method 
* @return array
*/
function getAllDeals(string $method = 'crm.deal.list'){

    /***********************************************/
    $params = [       
        'order'  => [
            'ID' => 'ASC'
        ],
        'filter' => [
            'CLOSED' => 'N',                                       // Сделка не закрыта                                   
            'STAGE_SEMANTIC_ID' => 'P'  //   P - промежуточная стадия, S - успешная стадия, F - провальная стадия (стадии).
        ],
        'select' => [
            'ID',                              // ID сделки
            'STAGE_ID',
            'COMPANY_ID',                      // ID магазина                
        ],  
        'start' => 0
    ];

    $result = CRest::call($method, $params); // Делаем запрос что бы понять сколько записей нам надо будет вытянуть
    while($result['error']=="QUERY_LIMIT_EXCEEDED"){
        sleep(1);
        $result = CRest::callBatch($arData);
        if ($result['error']<>"QUERY_LIMIT_EXCEEDED"){break;}
    } 
    $total = $result['total'];        // Всего записей в выборке
    $calls = ceil($total / 50);       // Сколько запросов надо сделать
    $current_call = 0;                // Номер текущего запроса
    $call_count = 0;                  // Счетчик вызовов для соблюдения условия не больше 2-х запросов в секунду

    sleep(1);                         // Делаем паузу перед основной работай  

    $arData = array();                // Массив для вызова callBatch
    $result = array();                // Массив для результатов вызова callBatch
    $totalResult = array();           // Массив с финальными данными

    /***********Цыкл формирования пакета запросов и выполнение их *********/
    do {
        $current_call++;

        $temp = [                                   // Собираем запрос
            'method' => $method,
            'params' => [            
                'order'  => [
                    'ID' => 'ASC'
                ], 
                'filter' => [
                    'CLOSED' => 'N',                                       // Сделка не закрыта                                  
                    'STAGE_SEMANTIC_ID' => 'P'  //   P - промежуточная стадия, S - успешная стадия, F - провальная стадия (стадии).
                ],
                'select' => [
                    'ID',                              // ID сделки
                    'STAGE_ID',
                    'COMPANY_ID',                      // ID магазина                       
                ],  
                'start' => ($current_call - 1) * 50
            ]
        ];

        array_push($arData, $temp);                 // Сохраняем собранный запрос в массив параметров arData для передачи его в callBatch

        if ((count($arData) == 50) || ($current_call == $calls)) {  // Если в массиве параметров arData 50 запросов или это последний запрос
            
            $call_count++;                                      // При каждом вызове увеличиваем счетчик
            if ($call_count == 2) {                             // Проверяем счетчик вызовов call_count
                sleep(1);                                       // Если да то делаем паузу 1 сек
                $call_count = 0;                                // Сбрасываем счетчик
            }


            $result = CRest::callBatch($arData);                // Вызываем callBatch
            while($result['error']=="QUERY_LIMIT_EXCEEDED"){
                sleep(1);
                $result = CRest::callBatch($arData);
                if ($result['error']<>"QUERY_LIMIT_EXCEEDED"){break;}
            }

            $resultTemp = $result['result']['result'];          // Убираем лишнее вложение в массиве
            
            foreach ($resultTemp as $company){                  // Перебираем массив что бы 
                foreach ($company as $value) {                  // удобно было с ним работать в дальнейшем
                    array_push($totalResult, $value);           // и сохраняем каждый елемент в totalResult
                }            
            }
            $arData = [];                                       // Очишаем массив параметров arData для callBatch
        }
    } while ($current_call < $calls);                           // Проверяем условие что текущих вызовов меньще чем надо сделать всего


    return $totalResult;
}

/**
* Write CSV file
* @param $method - Rest API request method 
* @return 0
*/
function getCSV(array $data, string $name = '', &$output, string $pattern = '1'){
    $temp = [];
    $temp2 = [];
    if ((int)$pattern == 1) {
        fputcsv($output, array('Внутренний номер', 'Баланс'), ';');

        $temp = $data;
    }
    
    if ((int)$pattern == 2) {
        fputcsv($output, array('ID Сделки', 'ID магазина', 'Внутренний номер', 'Баланс'), ';');
        
        foreach ($data as $value) {
            $temp2[0] = $value[3];
            $temp2[1] = $value[2];
            $temp2[2] = $value[0];
            $temp2[3] = $value[1];
            array_push($temp, $temp2);
        }
    }
    
    if ((int)$pattern == 3) {
        fputcsv($output, array('ID магазина', 'Внутренний номер', 'Баланс'), ';');
        
        foreach ($data as $value) {
            $temp2[1] = $value[2];
            $temp2[2] = $value[0];
            $temp2[3] = $value[1];
            array_push($temp, $temp2);
        }
    }

    foreach ($temp as $value) {
        fputcsv($output, $value, ";");
    }
}


/*
*Test function batch in batch
*
*/
function fastUpdateDeals(array $arDeal){
    // $hook = 'https://rahalcrm.bitrix24.ru/rest/1644/2rm45f7ep5heth5b/'; //указываем адрес вебхука
    $hook = C_REST_WEB_HOOK_URL;
    $method = 'crm.deal.update'; //указываем метод rest
    $total = count($arDeal); 

    //----------расчеты количества-----------
    $pack_limit = 50;
    $pack_limit_prev = $pack_limit - 1;
    // $requests_value = ceil($total / $pack_limit);       //количество запросов
    $requests_value = count($arDeal);                                  //количество запросов
    $batch_value = ceil($requests_value / $pack_limit); //количество батчей
    $batch_levels = array();
    $counter = $requests_value;
    while ($counter > $pack_limit) {
        $counter = ceil($counter / $pack_limit);
        $batch_levels[] = $counter;                     //количество батчей каждого уровня
    }
    if (!($batch_levels)) {
        $batch_levels[0] = 1; //если все поместится в 1 батч
    }

    echo '<pre>';
        print_r($batch_levels);
    echo '</pre>';

    //----------формируем запросы в батчи-----------
    $e = 1;
    $batches = array();
    $j = 0;
    while ($e <= $batch_value) {
        if ($requests_value > $pack_limit) {
            $inbatch_requests = $pack_limit;
            $requests_value = $requests_value - $pack_limit; //сколько еще запросов осталось запаковать
        }
        else {
            $inbatch_requests = $requests_value;
        }
        //формируем батч
        $batch = array('halt'=>0, 'cmd'=>array());
        $i = 1;
    
        while ($i <= $inbatch_requests) {
            $batch['cmd']['list'.$i] = $method.'?'
                .http_build_query(array(
                    "ID" => $arDeal[$j][2],
                    "fields" => array(
                        "UF_CRM_1628167713" => $arDeal[$j][1]            
                    )
                ));
            $i++;
            $j++;
        }
        $batches[] = $batch;
        $e++;
    }

    echo '<pre>';
        print_r($batches);
    echo '</pre>';

    //----------формируем вложенные батчи-----------
    foreach ($batch_levels as $level => $amount) {
        $extra_batches = array();
        $current_batch = current($batches);
        while ($current_batch) {
            $extra_batch = array('halt'=>0, 'cmd'=>array());
            $counter = 1;
            while ($counter <= $pack_limit and $current_batch) {
                $extra_batch['cmd']['batch'.$counter] = 'batch?'
                    .http_build_query($current_batch);
                $current_batch = next($batches);
                $counter++;
            }
            $extra_batches[] = $extra_batch;
        }
        $batches = $extra_batches;
    }

    echo '<pre>';
        print_r($batches);
    echo '</pre>';

    // ---------основной запрос---------
    $queryUrl = $hook.'batch';
    $queryData = http_build_query($extra_batch);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl,
        CURLOPT_POSTFIELDS => $queryData,
    ));
    $result0 = curl_exec($curl);
    curl_close($curl);
    $result = json_decode($result0, 1);
    $result = $result['result']['result'];

    echo '<pre>';
        echo 'queryData ';
        print_r($queryData);
    echo '</pre>';

    echo '<pre>';
        echo 'result ';
        print_r($result);
    echo '</pre>';

    echo '<pre>';
        echo 'result0 ';
        print_r($result0);
    echo '</pre>';

    // // //---------собираем ответ в общий массив---------
    // foreach ($batch_levels as $level => $amount) {
    //     $result_dump = array();
    //     foreach ($result as $key => $value) {
    //         $result2 = array_values($value['result']);
    //         $result_dump = array_merge($result_dump, $result2);
    //     }
    //     $result = $result_dump;
    // }
    // $all = array();
    // foreach ($result as $key => $value) {
    //     $all = array_merge($all, $value);
    // }
    //echo json_encode($all);
    //return $all;
}
?>