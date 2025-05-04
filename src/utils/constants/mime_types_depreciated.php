<?php
    /**
     * @var array MIME_TYPES_DEPRECIATED Assoc array of depreciated mime-types (keys) with their contemporary counterparts
     */
    define('MIME_TYPES_DEPRECIATED', [
        'application/csv' => 'text/csv',
        'application/x-javascript' => 'application/javascript',
        'text/ecmascript' => 'application/javascript',
        'application/xhtml+xml' => 'application/xhtml+xml', // While still used, the '+xml' suffix is sometimes discouraged for broader compatibility if not strictly XML.
        'application/xml' => 'application/xml', // Similar to above, sometimes more specific types like 'application/rss+xml' are preferred.
        'text/x-json' => 'application/json'
    ]);
?>