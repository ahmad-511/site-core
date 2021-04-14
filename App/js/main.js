import Ajax from '/App/js/ajax.js';

export function $(selector, elem) {
    elem = elem || document;
    return elem.querySelector(selector);
}

export function $$(selector, elem) {
    elem = elem || document;
    return elem.querySelectorAll(selector);
}

export function errorInResponse(resp, silentMode) {
    silentMode = !!silentMode;
    let msg = '';
    let isError = false;

    if(resp == 'AJAX_TIMEOUT'){
       resp = {
           message: 'Request timeout',
           messageType: 'error'
       };
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
    time = time || 5000;
    
    const msg = document.createElement('div');
    const btnClose = document.createElement('span');

    msg.className = `toast-message ${messageType.toLowerCase()}`;
    message = message.replace(/\n/g, '<br>');
    msg.dir = 'auto';
    msg.innerHTML = `<p>${message}</p>`;
    
    btnClose.innerHTML = '&times;';
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
 
export function showDataEditor(dataEditor, oper){
    dataEditor.classList.remove('search', 'create', 'edit', 'delete');
    dataEditor.classList.add('show', oper);
    document.body.classList.add('no-scroll');
    dataEditor.scroll(0, 0);
}

export function hideDataEditor(dataEditor){
    dataEditor.classList.remove('show', 'search', 'create', 'edit', 'delete');
    document.body.classList.remove('no-scroll');
}

export function resetForm(form, useSearchDefault){
    useSearchDefault = !!useSearchDefault;

    // Normal reset
    form.reset();

    // Reset elements with data-default attribte (used for hidden fields mostly as they don't get affected by normal reset)
    $$('input[data-default]', form).forEach(elem => {
        elem.value = elem.dataset.default;
    });

    // Use overwrite data-default with data-search-default
    if(useSearchDefault){
        $$('input[data-search-default]', form).forEach(elem => {
            elem.value = elem.dataset.searchDefault;
        });
    }
}

export function showInfoCard(infoCard){
    infoCard.classList.add('show');
    document.body.classList.add('no-scroll');
    infoCard.scroll(0, 0);
}

export function hideInfoCard(infoCard){
    infoCard.classList.remove('show');
    document.body.classList.remove('no-scroll');
}

export function resetCard(infoCard){
    $$('.info-data', infoCard).forEach(elem => elem.innerHTML = '&nbsp;');
}

export function updateRecordsStats(container, totalRecords, currentPage, totalPages){
    container.textContent = `Page ${currentPage} of ${totalPages} (${totalRecords} ${plural('Record', totalRecords)})`;
}

export function logout(lang) {
    if(lang){
        lang = '/'+lang;
    }
    
    Ajax('POST', lang+'/api/User/Logout',
        null,
        function (resp) {
            if (errorInResponse(resp)) {
                return false;
            }

            setTimeout(function () {
                document.location.href = resp.redirect;
            }, 2000);
        }
    )
}

export function renderPublicNotifications(){
    Ajax('POST', '/api/Notification/ReadPublic',
    {last_check: localStorage.getItem("notification_check")},
    function (resp) {
        if($('.public-notification')){
            $('.public-notification').remove();
        }

        if (!errorInResponse(resp, true) && resp.data.length > 0) {
            let container = document.createElement('div');
            container.className = 'public-notification';
            document.body.appendChild(container);

            let currGroup = '';
            
            let grp;
            for(let i = 0; i < resp.data.length; i++){
                let r = resp.data[i];
                let importance = r['importance'];

                if(currGroup != importance){
                    currGroup = importance;

                    grp = document.createElement('div');
                    grp.className = `notification-group ${currGroup.toLowerCase()}`;
                    
                    let btnClose = document.createElement('span');
                    btnClose.className = 'notification-close';
                    btnClose.textContent = '×';
                    grp.appendChild(btnClose);
                    
                    let tmpGrp = grp;

                    btnClose.addEventListener('click', e =>{
                        tmpGrp.remove();

                        // Get saved check points
                        // Update only closed group last update time
                        // Save
                        let notifCheck = JSON.parse(localStorage.getItem('notification_check')) || {
                            Low: '"2000-01-01"',
                            Normal: '"2000-01-01"',
                            High: '"2000-01-01"'
                        };

                        notifCheck[importance] = JSON.stringify(new Date());
                        localStorage.setItem('notification_check', JSON.stringify(notifCheck));
                    });

                    container.appendChild(grp);
                }
                
                let m = document.createElement('p');
                m.dir = 'auto';
                m.innerHTML = r['message'];
                grp.appendChild(m);
            }
        }

        setTimeout(function(){
            renderPublicNotifications();
        }, 5000);
    }
)
}

export function getOptionByValue(sel, value){
    return Array.apply(null, sel.options).find(o => o.value == value);
}

export function createOptionsMap(sel){
    const m = new Map();

    Array.from(sel.options).forEach(o => {
        m.set(o.value, o.textContent);
    });

    return m;
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

export function NL2P(str) {
    str = str || '';
    return '<p>' + str.replace(/\r\n/g, "\n").replace(/\r/g, "\n").replace(/\n/g, '</p><p>') + '</p>';
}