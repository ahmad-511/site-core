<?php
use App\Core\Router;
?>

<h1>Checkout</h1>

<div id="serviceNotAvailable">Service not available</div>
<div id="paypalButtonContainer"></div>

<script type="module">
    import {$, $$, errorInResponse, showMessage} from '/App/js/main.js';
    import Validator from '/App/js/Validator.js';
    import xhr from '/App/js/xhr.js';

    // Export some functions to Global scope to be used from none module script
    window.utils = {$, $$, errorInResponse, showMessage, Validator, xhr};
</script>

<script src="https://www.paypal.com/sdk/js?client-id=<?=CLIENT_ID?>&currency=<?=PAYPAL_CURRENCY?>"></script>

<script>
    window.addEventListener('DOMContentLoaded', (event) => {
        if(checkAvailability()){
            init();
        };
    });

    function checkAvailability(){
        const {$} = window.utils;

        if(!window['paypal']){
            $('#serviceNotAvailable').classList.remove('hidden');
            $('#paymentForm').classList.add('hidden');

            return false;
        }

        $('#serviceNotAvailable').classList.add('hidden');
        $('#paymentForm').classList.remove('hidden');

        return true;
    }

    function init(){
        const {$, $$, errorInResponse, showMessage, Validator, xhr} = window.utils;
        
        paypal.Buttons({
            createOrder: function(data, actions) {
                data = {
                    // Data to be submitted when order before creating the order (pay button clicked)
                }

                return fetch('/<?= Router::getCurrentLocaleCode()?>/api/Order/CreateOrder', {
                    method: 'post',
                    headers: {
                        'content-type': 'application/json'
                    },
                    body: JSON.stringify(data)
                }).then(function(res) {
                    return res.json();
                }).then(function(details) {
                    if(errorInResponse(details)){
                        return null;
                    }

                    let gateway_order_id = details.data.gateway_order_id;
                    return gateway_order_id;
                });
            },

            // Call your server to finalize the transaction (Payment completed)
            onApprove: function(data, actions) {
                // Update user interface if needed

                // Update the server about order opproval
                return fetch('/<?= Router::getCurrentLocaleCode()?>/api/Order/ApproveOrder', {
                        method: 'post',
                        body: JSON.stringify(data)
                    }).then(function(res) {
                        return res.json();
                    }).then(function(details) {
                        if(errorInResponse(details)){
                            return null;
                        }

                        // This error is not well documented on paypal
                        if (details.data.error === 'INSTRUMENT_DECLINED') {
                            return actions.restart();
                        }
                    });
                },

            // Order canceled
            onCancel: function(data){
                // Update user interface if needed

                // Update the server about the order cancelation
                return fetch('/<?= Router::getCurrentLocaleCode()?>/api/Order/CancelOrder', {
                        method: 'post',
                        body: JSON.stringify(data)
                    }).then(function(res) {
                        return res.json();
                    }).then(function(details) {
                        errorInResponse(details);
                });
            },

            // Error occurred somewhere
            onError: function(err){
                 // Update user interface if needed
                showMessage('Something went wrong', 'error');
                console.log(err);
            }
            }).render('#paypalButtonContainer');
    }
</script>