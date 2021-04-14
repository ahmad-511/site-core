export default function Ajax(method, url, data, callback){
    method  =method || 'GET'
    url = url || ''
    data = data || {}
    callback = callback || function(){}

    const xhr = new XMLHttpRequest()
    xhr.onreadystatechange = function(){
        if(this.readyState == 4){
            const jsonResponse = JSON.parse(this.responseText)
            callback(jsonResponse, this.status)
        }
    }

    let encodedData
    let isUpload = false
    let isFormData = data instanceof FormData
    
    if(!isFormData){
        let tmpData = []
        for(let e in data){
            tmpData.push(e +'='+ encodeURIComponent(data[e]))
        }
        
        encodedData = tmpData.join('&');
    }

    method = method.toUpperCase()

    if(method == 'GET'){
        if(isFormData){
            let tmpData = []
            for(let e of data.entries()){
                tmpData.push(e[0] +'='+ encodeURIComponent(e[1]))
            }

            encodedData = tmpData.join('&');
        }

        url = url + '?' + encodedData
        encodedData = null
    }

    if(method == 'POST'){
        if(isFormData){
            encodedData = data
            
            // check if there is upload using FormData
            for(let e of data.entries()){
                if (e[1] instanceof File){
                    isUpload = true
                    break
                }
            }
        }
    }

    xhr.open(method, url);

    xhr.timeout = 20000;
    xhr.ontimeout = function(){
        callback('AJAX_TIMEOUT');
    };

    if(isUpload){
        //xhr.setRequestHeader('Content-Type', 'multipart/form-data')
    }else{
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
    }

    xhr.send(encodedData)

    return xhr;
}