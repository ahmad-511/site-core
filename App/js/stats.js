import {$, $$, errorInResponse} from '/App/js/main.js';
import xhr from '/App/js/xhr.js';

const stats = [
    'countries-total',
    'countries-active',
    'countries-disabled',
    'locations-total',
    'locations-active',
    'locations-disabled',
    'makers-total',
    'makers-active',
    'makers-disabled',
    'accounts-total',
    'accounts-active',
    'accounts-pending',
    'accounts-verifying',
    'accounts-verifying-photo',
    'accounts-warned',
    'cars-total',
    'cars-active',
    'cars-suspended',
    'cars-verifying',
    'rides-total',
    'rides-pending',
    'rides-standby',
    'rides-finished',
    'ratings-total',
    'ratings-verifying',
    'ratings-published',
    'ratings-offending',
    'reports-total',
    'reports-verifying',
    'reports-confirmed',
    'reports-rejected',
    'reports-handled',
    'my-profile-status',
    'my-profile-mobile-verification',
    'my-profile-email-verification',
    'my-profile-personal-photo-verification',
    'my-cars-total',
    'my-cars-active',
    'my-cars-suspended',
    'my-cars-verifying',
    'my-rides-total',
    'my-rides-pending',
    'my-rides-standby',
    'my-rides-finished',
    'my-ride-requests-total',
    'my-ride-requests-pending',
    'my-ride-requests-accepted',
    'my-ride-requests-rejected',
    'conversations-total',
    'conversations-today',
    'my-conversations-total',
    'my-conversations-new',
    'notifications-total',
    'notifications-new',
    'notifications-read',
    'my-ratings-total',
    'my-ratings-received',
    'my-ratings-given',
    'my-ratings-verifying',
    'my-ratings-published',
    'my-ratings-offending',
    'my-reports-total',
    'my-reports-verifying',
    'my-reports-confirmed',
    'my-reports-rejected',
    'my-reports-handled',
    'search-total',
    'search-scheduled'
];

export default function refreshStats(lang){
    xhr({
        method: 'GET',
        url: `${lang}/api/Dashboard/Stats`,
        callback: resp => {
            // Clear stats
            stats.forEach( id => {
                const s = $(`.stats-${id}`);
                if(s){
                    s.textContent = '-';
                }
            });
            
            if (errorInResponse(resp, true)) {
                setTimeout(refreshStats, 5000, lang);

                return false;
            }

            // Update stats figures
            resp.data.forEach(({id, stats}) => {
                const s = $(`.stats-${id}`);
                if(s){
                    s.textContent = stats;
                }
            });

            setTimeout(refreshStats, 5000, lang);
        }
    });
}