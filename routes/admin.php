<?php

foreach (glob(base_path('routes/admin/*.php')) as $routeFile) {
    require $routeFile;
}

