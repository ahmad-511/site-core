import xhr from '/js/xhr.js'
import plural from '/js/plural.js'

export function $(selector, elem) {
    return (elem || document).querySelector(selector);
}

export function $$(selector, elem) {
    return (elem || document).querySelectorAll(selector);
}

export function nl2br(str) {
    str = str || '';
    return str.replace(/\r\n/g, "\n").replace(/\r/g, "\n").replace(/\n/g, '<br>');
}

export function nl2p(str) {
    str = str || '';
    return '<p>' + str.replace(/\r\n/g, "\n").replace(/\r/g, "\n").replace(/\n/g, '</p><p>') + '</p>';
}

export function errorInResponse(resp, silentMode) {
    silentMode = !!silentMode;
    let msg = '';
    let isError = false;

    switch (resp) {
        case 'XHR_TIMEOUT':
            resp = {
                message: 'Request timeout',
                messageType: 'error'
            };
            break;

        case 'BAD_JSON_FORMAT':
            resp = {
                message: 'Bad JSON format',
                messageType: 'error'
            };
            break;

        case 'XHR_CANCELED':
            resp = {
                message: 'Request canceled',
                messageType: 'error'
            };
            break;
    }

    if (resp.message) {
        msg = resp.message;
    }

    if (['error', 'exception'].indexOf(resp.messageType) > -1) {
        isError = true;
    }

    if (resp.messageType == 'validation_error') {
        isError = true;

        msg = `${msg}\n- ${Object.values(resp.data).join('\n- ')}`;
    }

    if (resp.messageType == 'reference_error') {
        isError = true;

        msg = `${msg}\n&bull; ${resp.data.map(i => `${i.references} ${plural(i.model, i.references)}`).join('\n&bull; ')}`;
    }

    if (msg && !silentMode) {
        showMessage(msg, resp.messageType);
    }

    return isError;
}

export function showMessage(message, messageType, time) {
    time = time || 5000;
    messageType = messageType || 'info';

    const msg = document.createElement('div');
    const btnClose = document.createElement('span');

    msg.className = `toast-message ${messageType.toLowerCase()}`;
    message = message.toString().replace(/\n/g, '<br>');
    msg.dir = 'auto';
    msg.innerHTML = `<i></i><p class="message-body">${message}</p>`;

    btnClose.innerHTML = '✖';
    btnClose.className = 'close';

    btnClose.addEventListener('click', function () {
        msg.classList.remove('show');

        setTimeout(function () {
            msg.remove();
        }, 300);
    });

    msg.appendChild(btnClose);

    document.body.appendChild(msg);

    if (time > 0) {
        setTimeout(function () {
            msg.classList.remove('show');

            setTimeout(function () {
                msg.remove();
            }, 300);

        }, time);
    }

    setTimeout(function () {
        msg.classList.add('show');
    }, 10)
}

export function showModal(modal) {
    if(modal.tagName == 'DIALOG'){
        modal.show()
    }else{
        modal.classList.add('show');
    }

    document.body.classList.add('no-scroll');
    modal.scroll(0, 0);
}

export function hideModal(modal) {
    if(modal.tagName == 'DIALOG'){
        modal.close()
    }else{
        modal.classList.remove('show');
    }

    document.body.classList.remove('no-scroll');
}

export function resetForm(form, useSearchDefault) {
    useSearchDefault = !!useSearchDefault;

    // Normal reset
    form.reset();

    // Reset elements with data-default attribute (used for hidden fields mostly as they don't get affected by normal reset)
    $$('input[data-default], select[data-default], textarea[data-default]', form).forEach(elem => {
        elem.value = elem.dataset.default;
    });

    // Use overwrite data-default with data-search-default
    if (useSearchDefault) {
        $$('input[data-search-default], select[data-search-default], textarea[data-search-default]', form).forEach(elem => {
            elem.value = elem.dataset.searchDefault;
        });
    }
}

export function resetCard(infoCard) {
    $$('.info-data', infoCard).forEach(elem => elem.innerHTML = '&nbsp;');
}

export function updateRecordsStats(container, totalRecords, currentPage, totalPages, lang = '') {
    if (totalRecords == 0) {
        let msg = 'There are no records to display';

        switch (lang) {
            case 'ar':
                msg = 'لا توجد سجلات للعرض';
                break;
        }

        container.textContent = msg;

        return;
    }

    let strPage = 'Page';
    let strOf = 'of';
    let strRecords = plural('Record', totalRecords);

    switch (lang) {
        case 'ar':
            strPage = 'صفحة';
            strOf = 'من';
            strRecords = (totalRecords < 3 || totalRecords > 10) ? 'سجل' : 'سجلات';
            break;
    }

    container.textContent = `${strPage} ${currentPage} ${strOf} ${totalPages} (${totalRecords} ${strRecords})`;
}

export function renderPagination(container, currPage, totalRecords, recordsPerPage, options ={}){
    options = Object.assign({
        buttonsCount: 5,
        displayFirst: true,
        displayLast: true,
        displayPrevious: true,
        displayNext: true
    }, options)
    container.innerHTML = '';

    const pageCount = Math.ceil(totalRecords / recordsPerPage);
    
    let prevPage = +currPage - 1;
    if(prevPage < 1){
        prevPage = 1;
    }

    let nextPage = +currPage + 1;
    if(nextPage > pageCount){
        nextPage = pageCount;
    }

    let pagination = [];
    if(pageCount > 1){
        options.displayFirst && pagination.push(`<a href="#" data-page="1">&laquo;</a>`);
        options.displayPrevious && pagination.push(`<a href="#" data-page="${prevPage}">&lsaquo;</a>`);

        let start = +currPage - Math.ceil(options.buttonsCount / 2);
        if(start < 1){
            start = 1
        }

        let end  = start + (options.buttonsCount - 1);
        if(end > pageCount){
            end = pageCount
        }

        // Make sure we always have pages buttons as specified in buttonsCount
        if(end - start < (options.buttonsCount - 1)){
            start -= (options.buttonsCount - 1) - (end - start);
            if(start < 1){
                start = 1;
            }
        }

        for(let i = start; i <= end; i++){
            pagination.push(`<a href="#" class="${i == currPage?'current-page':''}" data-page="${i}">${i}</a>`);       
        }

        options.displayNext && pagination.push(`<a href="#" data-page="${nextPage}">&rsaquo;</a>`);
        options.displayNext && pagination.push(`<a href="#" data-page="${pageCount}">&raquo;</a>`);

        container.innerHTML = pagination.join('');
    }
}

export function logout(all_devices, lang) {
    if (lang) {
        lang = '/' + lang;
    }

    xhr({
        method: 'POST',
        url: `${lang}/api/Account/Logout`,
        body: {
            all_devices: !!all_devices?1:0
        },
        callback: resp => {
            if (errorInResponse(resp)) {
                return false;
            }

            setTimeout(function () {
                document.location.href = resp.redirect;
            }, 2000);
        }
    });
}

export function getOptionByValue(sel, value) {
    return Array.apply(null, sel.options).find(o => o.value == value);
}

export function generateListOptions(selElement, data, valueMember, displayMember, selectedValue, dataProperties) {
    selElement.innerHTML = '';
    dataProperties || [];

    data.forEach(item => {
        const display = (typeof displayMember == 'function') ? displayMember(item) : item[displayMember];

        const isSelected = selectedValue == item[valueMember];
        const op = selElement.appendChild(new Option(display, item[valueMember], false, isSelected));

        if (dataProperties) {
            dataProperties.forEach(p => {
                op.dataset[p] = item[p] || '';
            });
        }
    });
}

export function createOptionsMap(sel) {
    const m = new Map();

    Array.from(sel.options).forEach(o => {
        m.set(o.value, o.textContent);
    });

    return m;
}

export function generatePropertyList(data) {
    const html = Object.entries(data).reduce((acc, [k, v]) => {
        acc.push(`<div><dt>${k}</dt><dd>${v}</dd></div>`);
        return acc;
    }, []).join('');

    return `<dl class="property-list">${html}</dl>`;
}

export function markRequired(){
    $$('[required]').forEach(inp => {
        if(inp.labels){
            inp.labels[0]?.classList.add('required');
        }
    });
}

export function addShowPassword(){
    $$('input[type=password]').forEach(inp => {
        const showIcon = document.createElement('i');
        showIcon.className = 'icon-eye show-password';
        inp.insertAdjacentElement('afterend', showIcon);

        showIcon.addEventListener('click', e => {
            inp.type = inp.type == 'password'? 'text': 'password';
            showIcon.className = (inp.type == 'password'? 'icon-eye': 'icon-eye-blocked') + ' show-password';
        });
    });
}

export function dateToInputString(dt, withTime = false){
    if(!(dt instanceof Date)){
        return dt
    }

    let d = new Date(dt)
    let strDate = `${d.getFullYear().toString().padStart(2, 0)}-${(d.getMonth() + 1).toString().padStart(2, 0)}-${d.getDate().toString().padStart(2, 0)}`

    if(withTime){
        strDate += ` ${d.getHours().toString().padStart(2, 0)}:${d.getMinutes().toString().padStart(2, 0)}:${d.getSeconds().toString().padStart(2, 0)}`
    }

    return strDate
}