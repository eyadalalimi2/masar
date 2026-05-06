<?php

foreach (glob(base_path('routes/pos/*.php')) as $routeFile) {
    require $routeFile;
}
