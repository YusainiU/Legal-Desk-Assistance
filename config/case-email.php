<?php

return [
    'user_minute' => (int) env('CASE_EMAIL_USER_MINUTE', 5),
    'user_daily' => (int) env('CASE_EMAIL_USER_DAILY', 50),
    'firm_daily' => (int) env('CASE_EMAIL_FIRM_DAILY', 500),
];
