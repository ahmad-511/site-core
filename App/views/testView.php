<h1>Test View</h1>

<script type="module">
    import xhr from './js/xhr.js'

    xhr({
        method: 'PUT',
        url: '/put',
        body: {a:1, b:2},
        callback(data, status){
            console.log(data, status)
        }
    }) 
</script>