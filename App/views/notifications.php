<?php

use App\Core\App;
use App\Core\Router;

?>

<section class="data-list">
    <h2 class="notifications"><i class="icon-bell"></i> <?= App::loc('Notifications')?></h2>

    <div class="toolbar" id="toolbar">
        <div id="dvPageRecords"></div>
        <div class="toolbar-group pagening">
            <button class="btn btn-blue" id="btnRefresh"><?= App::loc('Refresh')?></button>
            <button class="btn btn-yellow" id="btnPrevious"><i class="icon-chevron-left"></i></button>
            <input class="page-number" type="number" id="txtPageNumber" min="1" value="1">
            <button class="btn btn-yellow" id="btnNext"><i class="icon-chevron-right"></i></button>
        </div>
    </div>

    <div class="notifications-container" id="dvData"></div>
</section>

<script type="module">
    import {$, $$, updateRecordsStats, errorInResponse, showMessage} from '/App/js/main.js';
    import xhr from '/App/js/xhr.js';
    import Template from '/App/js/Template.js';

    const dvData = $('#dvData');
    const txtPageNumber = $('#txtPageNumber');
    let currentOper = '';
    let currentPage = 1;
    let totalPages = 1;
    let totalRecords = 0;

    const lang = '<?= Router::getCurrentLocaleCode()?>';

    const tplNotificationData = new Template(`<?= App::load('/templates/{locale}/notification-item.html') ?>`);

    $('#btnRefresh').addEventListener('click', operationHandler);
    $('#btnPrevious').addEventListener('click', operationHandler);
    $('#btnNext').addEventListener('click', operationHandler);
    $('#txtPageNumber').addEventListener('change', operationHandler);
    
    dvData.addEventListener('click', e => {
        const target = e.target;
        const dvNotif = target.closest('div');
        const id = parseInt(dvNotif.dataset.id);

        if(!id){
            if(target.tagName == 'BUTTON'){
                dvNotif.remove();
            }else{
                dvNotif.classList.add('Read');
            }
            return;
        }

        // Delete notification
        if(target.tagName == 'BUTTON'){
            sendRequest('POST', 'Delete', [], {notification_id: id});
            return
        }else{
            // Don't update already read notifications
            if(dvNotif.classList.contains('Read')){
                return;
            }

            // Update notification status to Read
            sendRequest('POST', 'Update', [], {notification_id: id, notification_status: 'Read'})
        }
    });

    // Handle CRUD operations
    function operationHandler(e) {
        e.preventDefault();

        const btnId = e.currentTarget.id;
        let uriParams = [];
        
        let rideID = null;

        switch (btnId) {
            // All these commands are Read operation
            case 'btnRefresh':
            case 'btnPrevious':
            case 'btnNext':
            case 'txtPageNumber':
                currentOper = 'Read';

                // Pagination
                let page = null;

                if(btnId == 'btnPrevious'){
                    currentPage -= 1;
                    page = currentPage;
                }
                
                if(btnId == 'btnNext'){
                    currentPage += 1;
                    page = currentPage;
                }

                if(btnId == 'txtPageNumber'){
                    currentPage = txtPageNumber.value;
                    page = currentPage;
                }

                if(btnId == 'btnRefresh'){
                    currentPage = 1;
                    totalPages = 1;
                    txtPageNumber.value = currentPage;
                }
                
                if(currentPage < 1){
                    currentPage = 1;
                    txtPageNumber.value = currentPage;

                    showMessage('No more records', 'info');
                    return;
                }

                if(currentPage > totalPages){
                    currentPage = totalPages;
                    txtPageNumber.value = currentPage;

                    showMessage('No more records', 'info');
                    return;
                }

                if(page){
                    uriParams.push('P', page);
                    txtPageNumber.value = page;
                }
                
                let data = {};

                currentOper = 'Read';
                
                // Determine request method
                const method = 'GET';
                
                sendRequest(method, currentOper, uriParams, data);
                break;
        }
    }

    // Send xhr request
    function sendRequest(method = 'GET', dbOper = '', uriParams = [], body = {}){
        // Join uri params
        let routeParams = '';
        
        if(uriParams instanceof Array && uriParams.length){
            routeParams = '/' + uriParams.join('/');
        }
        
        const url = `${lang}/api/Notification/${dbOper}${routeParams}`;

        xhr({
            method,
            url,
            body,
            callback: resp => {                
                if (errorInResponse(resp)) {
                    return false;
                }

                // Handle received data
                switch (dbOper) {
                    case 'Read':
                        // Replace notification link/locale placeholder with current locale code
                        resp.data = resp.data.map(item => {
                            item['notification_link'] = item['notification_link'].replace('{LOCALE}', lang.toUpperCase());

                            return item;
                        });

                        dvData.innerHTML = tplNotificationData.render(resp.data);

                        // Page / Records
                        totalRecords = resp.metaData?resp.metaData.total_records:resp.data.length;
						totalPages = Math.ceil(totalRecords / (resp.metaData?resp.metaData.records_per_page:totalRecords));
                                                
                        updateRecordsStats($('#dvPageRecords'), totalRecords, currentPage, totalPages, '<?= Router::getCurrentLocaleCode()?>');

                        if(totalRecords <= 0){
                            dvData.innerHTML = `<p class="no-data"><?= App::loc('You have no notifications')?></p>`;
                        }

                        break;
                    
                    case 'Update':
                        $(`#notification_${resp.data[0]['notification_id']}`).classList.add('Read');
                        break;

                    case 'Delete':
                        $(`#notification_${resp.data}`).remove();

                        updateRecordsStats($('#dvPageRecords'), --totalRecords, currentPage, totalPages, '<?= Router::getCurrentLocaleCode()?>');
                        
                        if(totalRecords <= 0){
                            dvData.innerHTML = `<p class="no-data"><?= App::loc('You have no notifications')?></p>`;
                        }

                        break;
                }
            }
        });
    }

    const ids = '<?=$params['ids']??''?>';
    const status = '<?=$params['status']??''?>';
    const params = [];
    const body = {};

    if(ids){
        params.push(ids);
    }

    if(status){
        body.search = JSON.stringify({notification_status: status});
    }

    sendRequest('GET', 'Read', params, body);
</script>