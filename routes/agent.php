<?php

foreach (glob(base_path('routes/agent/*.php')) as $routeFile) {
    require $routeFile;
}

