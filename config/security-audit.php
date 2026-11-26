<?php

return [
    'login_retention_days' => max(1, (int) env('LOGIN_AUDIT_RETENTION_DAYS', 90)),
];
