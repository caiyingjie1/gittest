<?php
Log::useFiles($app['config']['log.file'], 'info');
Log::useFiles($app['config']['log.error_file'], 'error');
