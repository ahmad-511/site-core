import xhr from '/App/js/xhr.js';

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

export function plural(word, count){
    if(count < 2){
        return word;
    }
    
    word = word.toString();

    const vowels = ['a', 'e', 'i', 'o', 'u', 'y'];
    let suffix = 's';

    let lastLetter = word.substr(-1, 1);
    const isUpperCase = (lastLetter == lastLetter.toUpperCase());

    lastLetter = lastLetter.toLowerCase();

    if(lastLetter == 'y'){
        const beforeY = word.substr(-2, 1);
        if(vowels.indexOf(beforeY) > -1){
            suffix = 's';
        }else{
            word = word.substr(0, word.length-1);
            suffix = 'ies';
        }
    }else if(['x', 's'].indexOf(lastLetter) > -1 ){
        suffix = 'es';
    }
    
    return word + (isUpperCase?suffix.toUpperCase(): suffix);
}

export function errorInResponse(resp, silentMode) {
    silentMode = !!silentMode;
    let msg = '';
    let isError = false;

    switch(resp){
        case 'XHR_TIMEOUT':
           resp = {
               message: 'Request timeout',
               messageType: 'error'
           };
           break;
        
        case 'BAD_JSON_FORMAT':
            resp = {
                message: 'Bad JSON formt',
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

    if(resp.messageType == 'validation_error'){
        isError = true;

        msg =`${msg}\n- ${Object.values(resp.data).join('\n- ')}`;
    }

    if(resp.messageType == 'reference_error'){
        isError = true;

        msg =`${msg}\n- ${resp.data.map(i => `${i.references} ${plural(i.model, i.references)}`).join('\n- ')}`;
    }

    if (msg && !silentMode) {
        showMessage(msg, resp.messageType);
    }

    return isError;
}

export function showMessage(message, messageType, time) {
    time = time||5000;
    messageType = messageType||'info';
    
    const msg = document.createElement('div');
    const btnClose = document.createElement('span');

    msg.className = `toast-message ${messageType.toLowerCase()}`;
    message = message.toString().replace(/\n/g, '<br>');
    msg.dir = 'auto';
    msg.innerHTML = `<i></i><p class="message-body">${message}</p>`;
    
    btnClose.innerHTML = '✖';
    btnClose.className = 'close';

    btnClose.addEventListener('click', function(){
        msg.classList.remove('show');

        setTimeout(function(){
            msg.remove();
        }, 300);
    });

    msg.appendChild(btnClose);

    document.body.appendChild(msg);

    if(time > 0 ){
        setTimeout(function () {
            msg.classList.remove('show');

            setTimeout(function(){
                msg.remove();
            }, 300);
            
        }, time);
    }

    setTimeout(function(){
        msg.classList.add('show');
    }, 10)
}


export function showDialog(dialog){
    dialog.classList.add('show');
    document.body.classList.add('no-scroll');
    dialog.scroll(0, 0);
}

export function hideDialog(dialog){
    dialog.classList.remove('show');
    document.body.classList.remove('no-scroll');
}
 
export function showDataEditor(dataEditor, oper){
    dataEditor.classList.remove('search', 'create', 'update');
    dataEditor.classList.add(oper);
    showDialog(dataEditor);
}

export function hideDataEditor(dataEditor){
    dataEditor.classList.remove('search', 'create', 'update');
    hideDialog(dataEditor);
}

export function resetForm(form, useSearchDefault){
    useSearchDefault = !!useSearchDefault;

    // Normal reset
    form.reset();

    // Reset elements with data-default attribte (used for hidden fields mostly as they don't get affected by normal reset)
    $$('input[data-default], select[data-default], textarea[data-default]', form).forEach(elem => {
        elem.value = elem.dataset.default;
    });

    // Use overwrite data-default with data-search-default
    if(useSearchDefault){
        $$('input[data-search-default], select[data-search-default], textarea[data-search-default]', form).forEach(elem => {
            elem.value = elem.dataset.searchDefault;
        });
    }
}

export function resetCard(infoCard){
    $$('.info-data', infoCard).forEach(elem => elem.innerHTML = '&nbsp;');
}

export function updateRecordsStats(container, totalRecords, currentPage, totalPages, lang = ''){
    if(totalRecords == 0){
        let msg = 'There are no records to display';

        switch(lang){
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

    switch(lang){
        case 'ar':
            strPage = 'صفحة';
            strOf = 'من';
            strRecords = (totalRecords <3 || totalRecords > 10) ? 'سجل' : 'سجلات';
        break;
    }

    container.textContent = `${strPage} ${currentPage} ${strOf} ${totalPages} (${totalRecords} ${strRecords})`;
}

export function logout(lang) {
    if(lang){
        lang = '/'+lang;
    }
    
    xhr({
        method: 'POST',
        url: `${lang}/api/Account/Logout`,
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

export function getOptionByValue(sel, value){
    return Array.apply(null, sel.options).find(o => o.value == value);
}

export function generateListOptions(selElement, data, valueMember, displayMember, selectedValue, dataProperties){
    selElement.innerHTML = '';
    dataProperties||[];

    data.forEach(item => {
        const display = (typeof displayMember == 'function')? displayMember(item): item[displayMember];
        
        const isSelected =  selectedValue == item[valueMember];
        const op = selElement.appendChild(new Option(display, item[valueMember], false, isSelected));

        if(dataProperties){
            dataProperties.forEach(p => {
                op.dataset[p] = item[p]||'';
            });
        }
    });
}

export function createOptionsMap(sel){
    const m = new Map();

    Array.from(sel.options).forEach(o => {
        m.set(o.value, o.textContent);
    });

    return m;
}

export function generatePropertyList(data){
    const html = Object.entries(data).reduce((acc, [k, v]) => {
        acc.push(`<div><dt>${k}</dt><dd>${v}</dd></div>`);
        return acc;
    }, []).join('');

    return `<dl class="property-list">${html}</dl>`;
}
