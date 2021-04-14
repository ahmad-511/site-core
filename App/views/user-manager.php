<?php

use App\Core\App;
use App\Core\Router;

?>
<section class="data-editor">
    <form id="frmEditor" class="form" novalidate>
        <h1><span id="entityOperation"></span> User <span id="entityId"></span></h1>

        <input type="hidden" id="user_id" data-default="0">
        
        <div class="control-group" searchable>
            <label for="email">Email</label>
            <span class="validity email"></span>
            <input type="email" id="email" required>
        </div>
        <div class="control-group" searchable>
            <label for="display_name">Display Name</label>
            <span class="validity display_name"></span>
            <input type="text" id="display_name" required>
        </div>
        <p class="control-group">
            <label for="password">Password</label>
            <span class="validity password"></span>
            <input type="password" id="password" required autocomplete="new-password">
        </p>

        <p class="form-operations">
            <input type="button" class="button cancel neutral" id="btnCancel" value="Cancel">
            <input type="submit" class="button action" id="btnSubmit" value="No Operation">
        </p>
    </form>
</section>

<section class="info-card">
    <div class="form">
        <h1>
            <span class="info-data" id="info_display_name">&nbsp;</span>
            <button id="btnCloseInfo" class="button close">X</button>
        </h1>

        <table>
            <tr>
                <th>ID</th>
                <td class="info-data" id="info_user_id"></td>
            </tr>
            <tr>
                <th>Email</th>
                <td class="info-data" id="info_email"></td>
            </tr>
        </table>
    </div>
</section>

<section class="data-list">
    <h1><?= App::loc(Router::$ViewCode)?></h1>

    <div class="toolbar">
        <div class="toolbar-group">
            <button class="button with-icon create" id="btnCreate">Create</button>
            <button class="button with-icon edit" id="btnEdit">Edit</button>
            <button class="button with-icon delete" id="btnDelete">Delete</button>
            <button class="button with-icon refresh" id="btnRefresh">Refresh</button>
            <button class="button with-icon info" id="btnInfo">Info</button>
        </div>
        <div class="toolbar-group pagening">
            <button class="button with-icon search" id="btnSearch">&nbsp;</button>
            <input class="page-number" type="number" id="txtPageNumber" min="1" value="1">
            <button class="button previous" id="btnPrevious">&lang;</button>
            <button class="button next" id="btnNext">&rang;</button>
            <div class="current-page" id="dvCurrentPage"></div>
        </div>
    </div>

    <div class="data-grid-wrapper">
        <table class="data-grid" id="tblData">
            <thead>
            <tr>
                <th data-model="user_id">ID</th>
                <th data-model="email" data-class="auto-width">Email</th>
                <th data-model="display_name" data-class="auto-width">Display Name</th>
            </tr>
            </thead>
            <tfoot>
            <tr>
                <td colspan="5" id="tdPageRecords"></td>
            </tr>
            </tfoot>
        </table>
    </div>
</section>

<script type="module">
    import {$, $$, updateRecordsStats, resetForm, resetCard, errorInResponse, showMessage, showDataEditor, hideDataEditor, showInfoCard, hideInfoCard} from '/App/js/main.js';
    import Validator from '/App/js/Validator.js';
    import TableManager from '/App/js/TableManager.js';
    import Ajax from '/App/js/ajax.js';

    const secDataEditor = $('.data-editor');
    const secInfoCard = $('.info-card'); 
    const tblData = $('#tblData');
    const btnSubmit = $('#btnSubmit');
    const btnCancel = $('#btnCancel');
    const txtPageNumber = $('#txtPageNumber');
    let currentOper = '';
    let currentPage = 1;
    let totalPages = 1;
    let totalRecords = 0;
    let currentSearch = null;

    $('#frmEditor').addEventListener('submit', operationHandler);
    $('#btnCancel').addEventListener('click', operationHandler);
    $('#btnCreate').addEventListener('click', operationHandler);
    $('#btnEdit').addEventListener('click', operationHandler);
    $('#btnDelete').addEventListener('click', operationHandler);
    $('#btnRefresh').addEventListener('click', operationHandler);
    $('#btnPrevious').addEventListener('click', operationHandler);
    $('#btnNext').addEventListener('click', operationHandler);
    $('#txtPageNumber').addEventListener('change', operationHandler);
    $('#btnSearch').addEventListener('click', operationHandler);
    $('#btnInfo').addEventListener('click', operationHandler);
    $('#btnCloseInfo').addEventListener('click', operationHandler);
    
    const validator = new Validator();
    const tblMgr = new TableManager(tblData);

    // Setup validator
    validator.add($('#email'), 'Type in a valid email', $('.validity.email'));
    validator.add($('#password'), 'Type in a password', $('.validity.password'));
    validator.add($('#display_name'), 'Type in a display name', $('.validity.display_name'));

    // Double click to edit
    tblMgr.events.listen('row-double-clicked', row =>{
        $('#btnEdit').click();
    });

    // Handle CRUD operations
    function operationHandler(e) {
        e.preventDefault();

        const btnId = e.target.id;
        let uriParams = {};
        
        btnSubmit.disabled = false;

        switch (btnId) {
            case 'btnCancel':
                hideDataEditor(secDataEditor);
                break;
            
            case 'btnSearch':
                btnSubmit.value = 'Search';
                currentOper = 'Search';

                $('#entityOperation').textContent = btnSubmit.value;
                $('#entityId').textContent = '';
                resetForm($('#frmEditor'));
                validator.clear();
                showDataEditor(secDataEditor, 'search');
                break;

            case 'btnCreate':
                btnSubmit.value = 'Create';
                currentOper = 'Create';

                $('#entityOperation').textContent = btnSubmit.value;
                $('#entityId').textContent = '';
                resetForm($('#frmEditor'));
                validator.clear();
                showDataEditor(secDataEditor, 'create');
                break;

            case 'btnEdit':
                if (!tblMgr.selectedRow) {
                    showMessage('Please select a row first', 'warning');
                    return false;
                }

                btnSubmit.value = 'Edit';
                currentOper = 'Edit';

                fillForm(tblMgr.getCellValue(tblMgr.selectedRow.rowIndex - 1, 'user_id'));
                showDataEditor(secDataEditor, 'edit');
                break;

            case 'btnInfo':
                if (!tblMgr.selectedRow) {
                    showMessage('Please select a row first', 'warning');
                    return false;
                }

                currentOper = 'Read';

                fillCard(tblMgr.getCellValue(tblMgr.selectedRow.rowIndex - 1, 'user_id'));
                showInfoCard(secInfoCard);
                break;

            case 'btnCloseInfo':
                hideInfoCard(secInfoCard);
                break;

            case 'btnDelete':
                if (!tblMgr.selectedRow) {
                    showMessage('Please select a row first', 'warning');
                    return false;
                }

                btnSubmit.value = 'Delete';
                currentOper = 'Delete';

                fillForm(tblMgr.getCellValue(tblMgr.selectedRow.rowIndex - 1, 'user_id'));
                showDataEditor(secDataEditor, 'delete');
               break;

            case 'btnRefresh':
                // Reset search params on Refresh operation
                currentSearch = null;

            // All these commands are Read operation
            case 'btnPrevious':
            case 'btnNext':
            case 'txtPageNumber':
                btnSubmit.value = 'No Operation';
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
                    uriParams.page = page;
                    txtPageNumber.value = page;
                }

            case 'frmEditor':                
                let data = {};

                if(currentOper == 'Search'){
                    currentSearch = JSON.stringify({
                        email: $('#email').value,
                        display_name: $('#display_name').value
                    });

                    currentOper = 'Read';
                }

                if (['Create', 'Edit'].indexOf(currentOper) > -1) {
                    if(!validator.validate(currentOper == 'Edit'?[$('#password')]:null)){
                        showMessage('Some data are missing or invalid', 'warning');
                        return;
                    }

                    data = {
                        user_id: $('#user_id').value,
                        email: $('#email').value,
                        password: $('#password').value,
                        display_name: $('#display_name').value
                    }
                }

                if (currentOper == 'Delete') {
                    data = {user_id: $('#user_id').value};
                }

                // Including search params for Search, Next, Previous and Page Number operations
                if(currentSearch){
                    data.search = currentSearch;
                }

                // Temporarily store current row index and operation until we receive the response
                let dbOper = currentOper;
                let rowIndex = -1;

                if (tblMgr.selectedRow) {
                    // The row index in the table body
                    rowIndex = tblMgr.selectedRow.rowIndex - 1;
                }

                // Convert uri params to query string
                let qs = '';
                
                if(uriParams){
                    qs = '?' + new URLSearchParams(uriParams).toString();
                }
                
                btnSubmit.disabled = true;
                // Send Ajax request
                Ajax('POST', '/api/User/' + dbOper + qs,
                    data,
                    function (resp) {
                        btnSubmit.disabled = false;
                        
                        if (errorInResponse(resp)) {
                            return false;
                        }

                        // Hide the form
                        btnCancel.click();

                        // Handle received data
                        switch (dbOper) {
                            case 'Read':
                                tblMgr.selectedRow = null;
                                
                                // Clear the table before appending rows
                                tblMgr.renderTable(resp.data, true);

                                // Page / Records
                                totalRecords = resp.metaData.total_records;
                                totalPages = Math.ceil(totalRecords / resp.metaData.records_per_page);
                                
                                updateRecordsStats($('#tdPageRecords'), totalRecords, currentPage, totalPages);
                                break;

                            case 'Create':
                                tblMgr.addRow(resp.data);

                                updateRecordsStats($('#tdPageRecords'), ++totalRecords, currentPage, totalPages);
                                break;

                            case 'Edit':
                                tblMgr.updateRow(resp.data, rowIndex);
                                break;
                                
                            case 'Delete':
                                tblMgr.removeRow(rowIndex);

                                updateRecordsStats($('#tdPageRecords'), --totalRecords, currentPage, totalPages);
                                break;
                        }
                    }
                );

                break;
        }
    }

    function fillForm(id) {
        resetForm($('#frmEditor'));
        validator.clear();
        btnSubmit.disabled = true;

        $('#entityId').textContent = '';
        $('#entityOperation').textContent = currentOper;

        Ajax('POST', '/api/User/Read/' + id,
            null,
            function (resp) {
                btnSubmit.disabled = false;
                
                if (errorInResponse(resp)) {
                    return false;
                }

                if(resp.data.length == 0){
                    showMessage('Record is lost', 'warning');
                    btnSubmit.disabled = true;

                    return false;
                }

                let r = resp.data[0];
                $('#entityId').textContent = `#${r.user_id}`;
                $('#user_id').value = r.user_id;
                $('#email').value = r.email;
                $('#password').value = '';
                $('#display_name').value = r.display_name;
            });
    }
    
    function fillCard(id) {
        resetCard(secInfoCard);

        Ajax('POST', '/api/User/Read/' + id,
            null,
            function (resp) {
                if (errorInResponse(resp)) {
                    return false;
                }

                if(resp.data.length == 0){
                    showMessage('Record is lost', 'warning');

                    return false;
                }
                
                let r = resp.data[0];
                $('#info_user_id').textContent = r.user_id;
                $('#info_email').innerHTML = `<a href="mailto:${r.email}">${r.email}</a>`;
                $('#info_display_name').textContent = r.display_name;
            });
    }

    $('#btnRefresh').click();
</script>
