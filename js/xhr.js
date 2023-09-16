export default function xhr(options = {}) {
    options = Object.assign({
        method: 'GET',
        url: '',
        body: {},
        headers: ['Accept: application/json'],
        responseType: 'json',
        timeout: 30000,
        callback: function () { },
        modifier: function (xhr) {return xhr}??xhr
    }, options)

    let xhr = new XMLHttpRequest()

    xhr.onreadystatechange = function () {
        if (this.readyState == xhr.DONE) {
            let response = this.response

            if (this.status == 0 && this.response === null) {
                response = 'XHR_CANCELED'
            }else if(response === null){
                response = 'BAD_JSON_FORMAT'
            }

            options.callback(response, this.status)
        }
    }

    const bodyType = options.body?.constructor.name
    let encodedBody
    let isSupportedBodyType = ['Document', 'Blob', 'ArrayBuffer', 'Int8Array', 'Uint8ClampedArray', 'Int16Array', 'Uint16Array', 'Int32Array', 'Uint32Array', 'Float32Array', 'Float64Array', 'BigInt64Array', 'BigUint64Array', 'DataView', 'FormData', 'URLSearchParams'].includes(bodyType)

    if (isSupportedBodyType) {
        encodedBody = options.body
    }else{
        if(bodyType == 'String'){
            let params = new URLSearchParams(options.body)
            encodedBody = params.toString()

            const isContentTypeSet = options.headers.find(i => i.toLowerCase().startsWith('content-type'))
            if(!isContentTypeSet){
                options.headers.push('Content-Type: application/x-www-form-urlencoded')
            }

        }else if(bodyType == 'Object'){
            let params = new URLSearchParams()
    
            for (const [k, v] of Object.entries(options.body)) {
                params.append(k, v)
            }
    
            encodedBody = params.toString()

            const isContentTypeSet = options.headers.find(i => i.toLowerCase().startsWith('content-type'))
            if(!isContentTypeSet){
                options.headers.push('Content-Type: application/x-www-form-urlencoded')
            }

        }else if(bodyType == 'Array'){
            // If body type is Array then the only choice is to stringify it
            encodedBody = JSON.stringify(options.body)

            const isContentTypeSet = options.headers.find(i => i.toLowerCase().startsWith('content-type'))
            if(!isContentTypeSet){
                options.headers.push('Content-Type: application/json')
            }
        }
    }

    options.method = options.method.toUpperCase()

    // GET and OPTION requests don't have body (convert body to query string and attach it to the url)
    // Only body type of String, Object, FormData or URLSearchParams cna be attached
    if (['GET', 'OPTIONS'].includes(options.method) && ['String', 'Object', 'FormData', 'URLSearchParams'].includes(bodyType)) {
        options.url = options.url + (options.url.includes('?')?'&': '?') + encodedBody
        
        // Empty request body
        encodedBody = null
    }

    xhr.open(options.method, options.url)

    xhr.timeout = options.timeout || 30000
    xhr.ontimeout = function () {
        // Request is canceled in this case and already handled from within onreadystatechange handler
        if (this.readyState == 4 && this.status == 0) {
            return
        }

        options.callback('XHR_TIMEOUT')
    }

    // Adding headers
    let isRequestedWithSet = false
    let isAcceptSet = false
    let isContentTypeSet = false

    if (options.headers instanceof Array) {
        options.headers.forEach(h => {
            let [n, v] = h.split(':', 2)
            n = n.trim()
            v = v.trim()
            xhr.setRequestHeader(n, v)
           
            n = n.toLowerCase()
            if(n == 'x-requested-with'){
                isRequestedWithSet = true
            }

            if(n == 'accept'){
                isAcceptSet = true
            }

            if(n == 'content-type'){
                isContentTypeSet = true
            }
        })
    }

    if(!isRequestedWithSet){
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
    }

    if(!isSupportedBodyType && !isContentTypeSet){
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
    }

    // Set response type
    // If set then override the response mime type
    switch(options.responseType){
        case '':
        case 'text':
            xhr.responseType = 'text'
            xhr.overrideMimeType('text/plain')
            if(!isAcceptSet){
                xhr.setRequestHeader('Accept', 'text/plain')
            }
            break
        
        case 'html':
            xhr.responseType = 'document'
            xhr.overrideMimeType('text/html')
            if(!isAcceptSet){
                xhr.setRequestHeader('Accept', 'text/html')
            }
            break
            
        case 'xml':
            xhr.responseType = 'document'
            xhr.overrideMimeType('text/xml')
            if(!isAcceptSet){
                xhr.setRequestHeader('Accept', 'text/xml')
            }
            break
            
        case 'json':
            xhr.responseType = 'json'
            xhr.overrideMimeType('application/json')
            if(!isAcceptSet){
                xhr.setRequestHeader('Accept', 'application/json')
            }
            break
        
        default:
            xhr.responseType = options.responseType??''
            if(!isAcceptSet){
                xhr.setRequestHeader('Accept', '*/*')
            }
    }

    // xhr modifier can be used to add necessary event listener before calling send
    xhr = options.modifier(xhr)??xhr

    xhr.send(encodedBody)

    return xhr
}