<?php
/**
 * Specify files to be included in each page/view
 * Use page/view code as a key, the value is an array of file type => array of file paths
 * Using an astrisk (*) as a key means specified files will be included in all pages
 * Supported file types are: css, js and module (js with type set to module)
 */
return [
    '*'=>[
        'css'=>[
            '/css/style.css'
        ],
        'js'=>[

        ],
        'module'=>[
            
        ]
    ],
    'home'=>[
        'css'=>[

        ],
        'js'=>[

        ],
        'module'=>[

        ]
    ]
];
?>