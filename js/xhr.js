export default function xhr(options = {}, timeout = 30000) {
    Object.assign({
        method: 'GET',
        url: '',
        body: {},
        header: [],
        callback: function () { }
    }, options);

    const xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function () {
        if (this.readyState == 4) {
            let jsonResponse = this.responseText;

            if (this.getResponseHeader('Content-Type') == 'application/json') {
                try {
                    jsonResponse = JSON.parse(this.responseText)
                } catch (ex) {
                    jsonResponse = 'BAD_JSON_FORMAT';
                }
            }

            if (this.status == 0 && jsonResponse == '') {
                jsonResponse = 'XHR_CANCELED';
            }

            options.callback(jsonResponse, this.status)
        }
    }

    let encodedData
    let isUpload = false
    let isFormData = options.body instanceof FormData

    if (!isFormData) {
        let tmpData = []
        for (let e in options.body) {
            tmpData.push(e + '=' + encodeURIComponent(options.body[e]))
        }

        encodedData = tmpData.join('&');
    }

    options.method = options.method.toUpperCase()

    if (options.method == 'GET') {
        if (isFormData) {
            let tmpData = []
            for (let e of options.body.entries()) {
                tmpData.push(e[0] + '=' + encodeURIComponent(e[1]))
            }

            encodedData = tmpData.join('&');
        }

        options.url = options.url + '?' + encodedData
        encodedData = null
    }

    if (options.method == 'POST') {
        if (isFormData) {
            encodedData = options.body

            // check if there is upload using FormData
            for (let e of options.body.entries()) {
                if (e[1] instanceof File) {
                    isUpload = true
                    break
                }
            }
        }
    }

    xhr.open(options.method, options.url);

    xhr.timeout = timeout || 30000;
    xhr.ontimeout = function () {
        // Request is canceled in this case and already handled from within onreadystatechange handler
        if (this.readyState == 4 && this.status == 0) {
            return;
        }

        options.callback('XHR_TIMEOUT');
    };

    // 'Content-Type' header is set automatically when sending FormData object
    // Other wise we'll use 'application/x-www-form-urlencoded'
    if (!isFormData) {
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
    }

    // Adding headers
    if (options.headers instanceof Array) {
        options.headers.forEach(h => {
            let [n, v] = h.split(':', 2);
            xhr.setRequestHeader(n.trim(), v.trim());
        });
    }

    xhr.send(encodedData)

    return xhr;
}