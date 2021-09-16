var Recordings = null;
var recordingsNotFound = null;
var percent = 0;
var warningStatement = document.getElementById("warningStatement");
var successStatement = document.getElementById("successStatement");
var Step2 = document.getElementById("Step2");
var Step4 = document.getElementById("Step4");
var Step41 = document.getElementById("Step41");
var btnCollapseTwo = document.getElementById("btnCollapseTwo");
var btnheadingThree = document.getElementById("btnheadingThree");

$(document).ready(function () {
        let flag = false;
        // Нажатие кнопки выбор файла
        $('#InputChooseFile').on('change',function(){
            percent = 0;
            // console.log(this.id);
            // получаем имя файла
            var fileName = $(this).val();
            // измените значение "fake path" (в Chrome) на пустую строку
            fileName = fileName.replace("C:\\fakepath\\", "");
            // заменяем надпись "Выберите файл" в label
            $(this).next('.custom-file-label').html(fileName);

            $( "#general-progress" ).css('width', percent + '%');
            $( "#progress-value" ).html(percent + '%');
            $( "#progress" ).prop('hidden', true);
            $( ".fa-spinner" ).prop('hidden', true);
            $( "#general-progress" ).removeClass('progress-bar-animated');
            $( "#general-progress" ).removeClass('bg-success');
            $( "#successStatement" ).prop('hidden', true);
            $( "#cardStep2" ).prop('hidden', true);
            $( "#cardStep4" ).prop('hidden', true);
            $( "#cardStep41" ).prop('hidden', true);
            
            if( this.value ){
                flag = true;
            } else { // Если после выбранного тыкнули еще раз, но дальше cancel
                flag = false;
                $(this).next('.custom-file-label').html("Выберите файл");
            }
        })

        $('#InputChooseFileAdd_Button').click(function () {
            percent = 2;
            $( "#general-progress" ).css('width', percent + '%');
            $( "#progress-value" ).html(percent + '%');
            $( "#progress" ).prop('hidden', false);
            $( ".fa-spinner" ).prop('hidden', false);
            $( "#general-progress" ).addClass('progress-bar-animated');
            $( "#cardStep2" ).prop('hidden', true);
            $( "#cardStep4" ).prop('hidden', true);
            $( "#cardStep41" ).prop('hidden', true);


            if (flag) {
                percent += 4;
                $( "#general-progress" ).css('width', percent + '%');
                $( "#progress-value" ).html(percent + '%');
                $( "#warningStatement" ).prop('hidden', true);
            }else{
                if (!flag) {warningStatement.innerHTML = 'Выбирете файл';} 
                $( "#warningStatement" ).prop('hidden', false);
                // alert('Выбирете файл');
                $( "#progress" ).prop('hidden', true);
                $( ".fa-spinner" ).prop('hidden', true);
                $( "#general-progress" ).removeClass('progress-bar-animated');
                return false;
            }

            // Step 1 reading a file
            //////////////////////////////////////////////////////

            let data = new FormData($('#formdata').get(0));
            $.ajax({
                url         : 'handler.php',
                type        : 'POST', // важно!
                data        : data,
                cache       : false,
                // dataType    : 'json',
                // отключаем обработку передаваемых данных, пусть передаются как есть
                processData : false,
                // отключаем установку заголовка типа запроса. Так jQuery скажет серверу что это строковой запрос
                contentType : false,

                beforeSend : function(){
                    data.set( 'Step', 1 );
                    percent += 2;
                    $( "#general-progress" ).css('width', percent + '%');
                    $( "#progress-value" ).html(percent + '%');
                    successStatement.innerHTML = 'Start';
                },

                complete : function(){

                },

                success : function(respond, status, jqXHR ){
                    // console.log(respond);
                    Recordings = (JSON.parse(respond))['recordings'];
                    percent += 2;
                    $( "#general-progress" ).css('width', percent + '%');
                    $( "#progress-value" ).html(percent + '%');
                    successStatement.innerHTML = 'Step 1 reading a file';
                    $( "#successStatement" ).prop('hidden', false);

                     // Step 2 search for the company ID by internal number;
                    $.ajax({
                        url         : 'handler.php',
                        type        : 'POST', // важно!
                        data        : {'Step' : '2', 'recordings': JSON.stringify(Recordings)},

                        beforeSend : function(){
                            percent += 12;
                            $( "#general-progress" ).css('width', percent + '%');
                            $( "#progress-value" ).html(percent + '%');
                            successStatement.innerHTML = 'Start search for the company ID by internal number';
                        },

                        // функция успешного ответа сервера
                        success     : function(respond, status, jqXHR ){
                            // console.log(respond);
                            Recordings = (JSON.parse(respond))['recordings'];
                            percent += 13;
                            $( "#general-progress" ).css('width', percent + '%');
                            $( "#progress-value" ).html(percent + '%');
                            successStatement.innerHTML = 'Finish search for the company ID by internal number';
                            // console.log(Recordings);
                            if (respond.includes('recordingsNotFound')){
                                RecordingsNotFound = (JSON.parse(respond))['recordingsNotFound'];
                                console.log(RecordingsNotFound);
                                $( "#cardStep2" ).prop('hidden', false);
                                Step2.innerHTML = printTable(RecordingsNotFound);
                            }

                            // Step 3 search for a deal by company ID
                            $.ajax({
                                url         : 'handler.php',
                                type        : 'POST', // важно!
                                data        : {'Step' : '3', 'recordings': JSON.stringify(Recordings)},

                                beforeSend : function(){
                                    percent += 20;
                                    $( "#general-progress" ).css('width', percent + '%');
                                    $( "#progress-value" ).html(percent + '%');
                                    successStatement.innerHTML = 'Start search for a deal by company ID';
                                },

                                // функция успешного ответа сервера
                                success     : function(respond, status, jqXHR ){
                                    // console.log(respond);
                                    
                                    if (respond.includes('recordings')){
                                        Recordings = (JSON.parse(respond))['recordings'];
                                        console.log(Recordings);
                                        $( "#cardStep4" ).prop('hidden', false);
                                        btnCollapseTwo.innerHTML = `Найденные сделки по которым можно обновить аванс`;
                                        Step4.innerHTML = printTableDeals(Recordings);
                                    }

                                    if (respond.includes('recordingsNotFound')){
                                        recordingsNotFound = (JSON.parse(respond))['recordingsNotFound'];
                                        console.log(recordingsNotFound);
                                        $( "#cardStep41" ).prop('hidden', false);
                                        btnheadingThree.innerHTML = `Не найдены сделки в группе стадий "В работе" (аванс обновить нельзя)`;
                                        Step41.innerHTML = printTableDeals(recordingsNotFound);
                                    }

                                    if (!respond.includes('recordings')){
                                        percent += 10;
                                    }

                                    // if (!respond.includes('recordingsNotFound')){
                                    //     percent += 10;
                                    // }

                                    percent += 35;
                                    $( "#general-progress" ).css('width', percent + '%');
                                    $( "#progress-value" ).html(percent + '%');
                                    successStatement.innerHTML = 'Finish search for a deal by company ID';

                                    if (percent == 100) {
                                        $( ".fa-spinner" ).prop('hidden', true);
                                        $( "#progress-value" ).html(percent + '% Finish!');
                                        $( "#general-progress" ).addClass('bg-success');
                                        $( "#general-progress" ).removeClass('progress-bar-animated');    
                                    }
                                },
                            });





                        },
                    })    
                },

                error: function( jqXHR, status, errorThrown ){
                    console.log( 'ОШИБКА AJAX запроса: ' + status, jqXHR, errorThrown);
                    $( "#progress" ).prop('hidden', true);
                    $( ".fa-spinner" ).prop('hidden', true);
                    $( "#general-progress" ).removeClass('progress-bar-animated');
                    $( "#successStatement" ).prop('hidden', true);

                    $( "#warningStatement" ).prop('hidden', false);
                        let messageAJAX = '';
                            if (jqXHR.status === 0) {
                                messageAJAX = 'Неизвестная ошибка:\n' + jqXHR.responseText;
                            } else if (jqXHR.status == 404) {
                                messageAJAX = 'НЕ найдена страница запроса [404]';
                            } else if (jqXHR.status == 500) {
                                messageAJAX = 'НЕ найден домен в запросе [500]';
                            } else if (jqXHR.status == 502) {
                                messageAJAX = 'НЕ найден домен в запросе [502]';
                            } else if (jqXHR.status == 503) {
                                messageAJAX = 'НЕ найден домен в запросе [503]';
                            } else if (jqXHR.status == 504) {
                                messageAJAX = 'НЕ найден домен в запросе [504]';
                            } else if (exception === 'parsererror') {
                                messageAJAX = "Ошибка в коде: \n"+jqXHR.responseText ;
                            } else if (exception === 'timeout') {
                                messageAJAX = 'Не ответил на запрос.';
                            } else if (exception === 'abort') {
                                messageAJAX = 'Прерван запрос Ajax.' ;
                            } else {
                                messageAJAX = 'Неизвестная ошибка:\n' + jqXHR.responseText;
                            }
                            warningStatement.innerHTML = 'ОШИБКА AJAX запроса: ' + messageAJAX;
                        }

            })

        })
})

function printTable(data) {
    let str = '';
    data.forEach(function(item, i, data){
    str = str + `<tr>
                    <td>${i+1}</td>
                    <td><a href="https://rahalcrm.bitrix24.ru/crm/company/details/${item[3]}/" target="_blank">${item[3]}</a></td> 
                    <td>${item[0]}</td>
                    <td>${item[1]}</td>                                                                       
                </tr>`;
    });

    htmlTable = `<div class="text-center">
                    <h3>Записей: ${data.length}</h3>
                    <table class="m-auto table-striped table-bordered table-sm">
                        <thead>
                            <tr>
                                <td>№</td>
                                <td>ID магазина</td>
                                <td>Внутренний номер</td>
                                <td>Баланс</td>
                            </tr>
                        </thead>
                        <tbody>
                            ${str}
                        </tbody>
                       </table>
                </div>`;
    return htmlTable;
}

function printTableDeals(data) {
    let str = '';
    data.forEach(function(item, i, data){
    str = str + `<tr>
                    <td>${i+1}</td>
                    <td><a href="https://rahalcrm.bitrix24.ru/crm/deal/details/${item[3]}/" target="_blank">${item[3]}</a></td>
                    <td><a href="https://rahalcrm.bitrix24.ru/crm/company/details/${item[2]}/" target="_blank">${item[2]}</a></td> 
                    <td>${item[0]}</td>
                    <td>${item[1]}</td>                                                                       
                </tr>`;
    });

     htmlTable = `<div class="text-center">
                    <h3>Записей: ${data.length}</h3>
                    <table class="m-auto table-striped table-bordered table-sm">
                        <thead>
                            <tr>
                                <td>№</td>
                                <td>ID Сделки</td>
                                <td>ID магазина</td>
                                <td>Внутренний номер</td>
                                <td>Баланс</td>
                            </tr>
                        </thead>
                        <tbody>
                            ${str}
                        </tbody>
                    </table>
                </div>`;
    return htmlTable;
}

function printTableNoShipment(data) {        
    let str = '';
    data.forEach(function(item, i, data){
    str = str + `<tr>
                    <td>${i+1}</td>
                    <td><a href="https://rahalcrm.bitrix24.ru/crm/deal/details/${item['ID']}/" target="_blank">${item['ID']}</a></td> 
                    <td>${item['STAGE_ID']}</td>
                    <td><a href="https://rahalcrm.bitrix24.ru/crm/company/details/${item['COMPANY_ID']}/" target="_blank">${item['COMPANY_ID']}</a></td>                                                                   
                </tr>`;
    });

    htmlTable = `<div class="text-center">
                    <h3>Записей: ${data.length}</h3>
                    <table class="m-auto table-striped table-bordered table-sm">
                        <thead>
                            <tr>
                                <td>№</td>
                                <td>ID сделки</td>
                                <td>Стадия</td>
                                <td>ID магазина</td>
                            </tr>
                        </thead>
                        <tbody>
                            ${str}
                        </tbody>
                    </table>
                </div>`;
    return htmlTable;
}

function sleep(ms) {
    ms += new Date().getTime();
    while (new Date() < ms){}
    console.log('next ajax');
}

function updateBalance() {

        let Go = confirm('Вы уверены что хотите обновить баланс у ' + Recordings.length + ' сделок?');
        if (Go) {
            if (percent == 100) {
                percent = 90;
                $( ".fa-spinner" ).prop('hidden', false);
                $( "#progress-value" ).html(percent + '%');
                $( "#general-progress" ).removeClass('bg-success');
                $( "#general-progress" ).addClass('progress-bar-animated');
            }
            
            let total = Recordings.length;          // Всего записей в выборке
            let calls = total;                  // Сколько запросов надо сделать
            let current_call = 0;                // Номер текущего запроса
            let call_count = 0;                  // Счетчик вызовов для соблюдения условия не больше 2-х запросов в секунду
            let batch = 150;                // записей в пачке
            let call_batch = Math.ceil(total / batch);  // сколько запрсов надо сделать            
            let k = Math.floor(5 / call_batch * 100) / 100 ;

            let arData = [];                // Массив для вызова callBatch

            do {
                current_call++;
                arData.push(Recordings[current_call - 1]);

                if ((arData.length == batch) || (current_call == calls)) {
                    call_count++;
                    $.ajax({
                        url         : 'handler.php',
                        type        : 'POST', // важно!
                        data        : {'Step' : '4', 'recordings': JSON.stringify(arData)},

                        beforeSend : function(){
                            percent += k;
                            $( "#general-progress" ).css('width', percent + '%');
                            $( "#progress-value" ).html(percent + '%');
                            successStatement.innerHTML = `Start batch update Balance`;
                        },

                        complete : function(){
                            successStatement.innerHTML = `Finish batch update Balance`;
                        },

                        // функция успешного ответа сервера
                        success     : function(respond, status, jqXHR ){
                            // console.log(respond);
                            percent += k;
                            $( "#general-progress" ).css('width', percent + '%');
                            $( "#progress-value" ).html(percent + '%');
                            successStatement.innerHTML = `Finish batch update Balance`;

                            if (respond.includes('recordings')){
                                Recordings = (JSON.parse(respond))['recordings'];
                                console.log(Recordings);
                            }

                            if (Math.round(percent) >= 100) {
                                $( ".fa-spinner" ).prop('hidden', true);
                                $( "#progress-value" ).html(Math.round(percent) + '% Finish!');
                                $( "#general-progress" ).addClass('bg-success');
                                $( "#general-progress" ).removeClass('progress-bar-animated');    
                            }
                        },
                    });
                    arData.length = 0;
                    sleep(1000);
                }
                if (call_count == 6) {call_count = 0; sleep(180000);}  
            } while (current_call < calls);



            // $.ajax({
            //     url         : 'handler.php',
            //     type        : 'POST', // важно!
            //     data        : {'Step' : '4', 'recordings': JSON.stringify(Recordings)},

            //     beforeSend : function(){
            //         percent += 5;
            //         $( "#general-progress" ).css('width', percent + '%');
            //         $( "#progress-value" ).html(percent + '%');
            //         successStatement.innerHTML = 'Start update Balance';
            //     },

            //     complete : function(){
            //         successStatement.innerHTML = 'Finish update Balance';
            //     },

            //     // функция успешного ответа сервера
            //     success     : function(respond, status, jqXHR ){
            //         // console.log(respond);
            //         percent += 5;
            //         $( "#general-progress" ).css('width', percent + '%');
            //         $( "#progress-value" ).html(percent + '%');
            //         successStatement.innerHTML = 'Finish update Balance';

            //         if (respond.includes('recordings')){
            //             Recordings = (JSON.parse(respond))['recordings'];
            //             console.log(Recordings);
            //         }

            //         if (percent == 100) {
            //             $( ".fa-spinner" ).prop('hidden', true);
            //             $( "#progress-value" ).html(percent + '% Finish!');
            //             $( "#general-progress" ).addClass('bg-success');
            //             $( "#general-progress" ).removeClass('progress-bar-animated');    
            //         }
            //     },
            // });
        }
}

function get_csv(name) {
        let data = null;
        let pattern = 0;
        if (name == 'getCSVStep2') {data = RecordingsNotFound; pattern = 1;}
        if (name == 'getCSVStep4') {data = Recordings; pattern = 2;} 
        if (name == 'getCSVStep41') {data = recordingsNotFound; pattern = 3;}        
        $.ajax({
            url         : 'handler.php',
            type        : 'POST', // важно!
            data        : {'Step' : '5', 'name': name, 'data' : JSON.stringify(data), 'pattern' : pattern},

            // функция успешного ответа сервера
            success     : function(respond, status, jqXHR ){
                // console.log(respond);
                /*
                   * Make CSV downloadable
                   */
                  var downloadLink = document.createElement("a");
                  var fileData = ['\ufeff'+respond];

                  var blobObject = new Blob(fileData,{
                     type: "text/csv;charset=utf-8;"
                   });

                  var url = URL.createObjectURL(blobObject);
                  downloadLink.href = url;
                  downloadLink.download = name+".csv";

                  /*
                   * Actually download CSV
                   */
                  document.body.appendChild(downloadLink);
                  downloadLink.click();
                  document.body.removeChild(downloadLink);
            },
        });
    }       